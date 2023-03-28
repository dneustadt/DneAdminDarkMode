<?php declare(strict_types=1);

namespace Dne\AdminDarkMode\Service;

use League\Flysystem\FilesystemInterface;
use Sabberworm\CSS\CSSList\Document;
use Sabberworm\CSS\OutputFormat;
use Sabberworm\CSS\Parser;
use Sabberworm\CSS\Property\Selector;
use Sabberworm\CSS\Rule\Rule;
use Sabberworm\CSS\RuleSet\DeclarationBlock;
use Sabberworm\CSS\Value\Color;
use Sabberworm\CSS\Value\CSSFunction;
use Sabberworm\CSS\Value\RuleValueList;
use Sabberworm\CSS\Value\Size;
use Sabberworm\CSS\Value\ValueList;
use Shopware\Core\Framework\Parameter\AdditionalBundleParameters;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\KernelPluginLoader\KernelPluginLoader;
use Shopware\Core\Framework\Update\Event\UpdatePostFinishEvent;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class AdminDarkModeCompiler implements EventSubscriberInterface
{
    private const DARK_MODE_COMMENT = '/* DneAdminDarkMode START */';

    private FilesystemInterface $filesystem;

    private KernelInterface $kernel;

    private KernelPluginLoader $pluginLoader;

    private ParameterBagInterface $parameterBag;

    public function __construct(
        FilesystemInterface $filesystem,
        KernelInterface $kernel,
        KernelPluginLoader $pluginLoader,
        ParameterBagInterface $parameterBag
    ) {
        $this->filesystem = $filesystem;
        $this->kernel = $kernel;
        $this->pluginLoader = $pluginLoader;
        $this->parameterBag = $parameterBag;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UpdatePostFinishEvent::class => 'compileBundleAssets',
        ];
    }

    public function compileBundleAssets(): void
    {
        foreach ($this->kernel->getBundles() as $bundle) {
            $this->compileAssets($bundle);

            if ($bundle instanceof Plugin) {
                foreach ($this->getAdditionalBundles($bundle) as $additionalBundle) {
                    $this->compileAssets($additionalBundle);
                }
            }
        }
    }

    public function compileAssets(BundleInterface $bundle): void
    {
        $bundleName = mb_strtolower($bundle->getName());

        if ($bundleName === 'dneadmindarkmode') {
            return;
        }

        $dir = sprintf(
            'bundles/%s/%s',
            $bundleName,
            $bundleName === 'administration' ? 'static/css' : 'administration/css'
        );

        if (!$this->filesystem->has($dir)) {
            return;
        }

        $directoryListing = $this->filesystem->listContents($dir);
        $colorRegex = '/#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})\b|rgb\([^)]*\)|rgba\([^)]*\)|hsl\([^)]*\)|hsla\([^)]*\)/';

        foreach ($directoryListing as $file) {
            if ($file['type'] !== 'file' || !str_ends_with($file['path'], '.css')) {
                continue;
            }

            [$css] = explode(self::DARK_MODE_COMMENT, $this->filesystem->read($file['path']));

            if (!preg_match($colorRegex, $css)) {
                continue;
            }

            $this->filesystem->put(
                $file['path'],
                $css . $this->enrichCss($css)
            );
        }
    }

    private function enrichCss(string $css): string
    {
        $document = (new Parser($css))->parse();
        $newDocument = new Document();

        foreach ($document->getContents() as $ruleSet) {
            if (!$ruleSet instanceof DeclarationBlock) {
                continue;
            }

            $selectors = $this->filterSelectors($ruleSet);
            if (empty($selectors)) {
                continue;
            }

            $newBlock = new DeclarationBlock();

            foreach ($ruleSet->getRules() as $rule) {
                $value = $rule->getValue();
                $newRule = new Rule($rule->getRule());

                if ($value instanceof Color && $color = $this->handleColorValue($value, $rule)) {
                    $newRule->setValue($color);
                    $newRule->setIsImportant($rule->getIsImportant());
                    $newBlock->addRule($newRule);
                }

                if ($value instanceof Color || (!$value instanceof RuleValueList && !$value instanceof CSSFunction)) {
                    continue;
                }

                $foundColor = $this->handleValueList($value, $rule);

                if (!$foundColor) {
                    continue;
                }

                $this->handleColorInShorthands($value, $newRule);

                $newRule->setValue($value);
                $newBlock->addRule($newRule);
            }

            if (empty($newBlock->getRules())) {
                continue;
            }

            $newBlock->setSelectors($selectors);
            $newDocument->append($newBlock);
        }

        return self::DARK_MODE_COMMENT . $newDocument->render(OutputFormat::createCompact());
    }

    private function handleValueList(ValueList $valueList, Rule $rule, bool &$foundColor = false): bool
    {
        $components = $valueList->getListComponents();

        if (!is_array($components)) {
            return false;
        }

        $newComponents = [];
        foreach ($components as $component) {
            if ($component instanceof Color && $color = $this->handleColorValue($component, $rule)) {
                $newComponents[] = $color;
                $foundColor = true;

                continue;
            }

            if ($component instanceof RuleValueList || $component instanceof CSSFunction) {
                $this->handleValueList($component, $rule, $foundColor);
            }

            $newComponents[] = $component;
        }

        $valueList->setListComponents($newComponents);

        return $foundColor;
    }

    private function handleColorInShorthands(ValueList $valueList, Rule $rule): void
    {
        if (!\in_array($rule->getRule(), ['background', 'border'], true) || $valueList instanceof CSSFunction) {
            return;
        }

        $colors = array_values(array_filter($valueList->getListComponents(), function ($component): bool {
            return $component instanceof Color;
        }));

        if (\count($colors) !== 1) {
            return;
        }

        switch ($rule->getRule()) {
            case 'background':
                $rule->setRule('background-color');
                $valueList->setListComponents($colors);

                break;
            case 'border':
                $rule->setRule('border-color');
                $valueList->setListComponents($colors);

                break;
        }
    }

    private function handleColorValue(Color $color, Rule $rule): ?Color
    {
        $components = $color->getListComponents();

        if ($rule->getRule() === 'box-shadow') {
            $shadowColor = ['r' => new Size(0), 'g' => new Size(0), 'b' => new Size(0)];

            if (isset($components['a']) && $components['a'] instanceof Size) {
                $shadowColor['a'] = new Size($components['a']->getSize());
            }

            return new Color($shadowColor);
        }

        $r = $components['r'] ?? null;
        $g = $components['g'] ?? null;
        $b = $components['b'] ?? null;

        if (!$r instanceof Size || !$g instanceof Size || !$b instanceof Size) {
            $h = $components['h'] ?? null;
            $s = $components['s'] ?? null;
            $l = $components['l'] ?? null;

            if (!$h instanceof Size || !$s instanceof Size || !$l instanceof Size) {
                return null;
            }

            $color->setListComponents(array_merge($components, $this->darken($h->getSize(), $s->getSize(), $l->getSize(), true)));

            return $color;
        }

        [$hue, $saturation, $lightness] = $this->rgb2hsl($r->getSize(), $g->getSize(), $b->getSize());

        $color->setListComponents(array_merge($components, $this->darken($hue, $saturation, $lightness)));

        return $color;
    }

    private function rgb2hsl(float $r, float $g, float $b): array
    {
        $r /= 255;
        $g /= 255;
        $b /= 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        $d = $max - $min;
        $h = $s = 0;
        if((float) $d !== 0.0) {
            $s = $d / (1 - abs((2 * $l) - 1));
            switch($max) {
                case $r:
                    $h = 60 * fmod((($g - $b) / $d), 6);
                    if ($b > $g) {
                        $h += 360;
                    }

                    break;
                case $g:
                    $h = 60 * (($b - $r) / $d + 2);

                    break;
                case $b:
                    $h = 60 * (($r - $g) / $d + 4);

                    break;
            }
        }

        return [round($h), round($s * 100), round($l * 100)];
    }

    private function darken(float $hue, float $saturation, float $lightness, bool $hsl = false): array
    {
        $lightnessIncrement = ($lightness / 100) * 10;
        $lightness = min(100 - $lightness + $lightnessIncrement, 100);

        if ($hue + $saturation === 0.0) {
            $hue = 210.0;
            $saturation = 10.0;
        }

        if ($hsl) {
            return [
                'h' => new Size($hue),
                's' => new Size($saturation, '%'),
                'l' => new Size($lightness, '%'),
            ];
        }

        return $this->hsl2rgb($hue, $saturation, $lightness);
    }

    private function hsl2rgb(float $h, float $s, float $l): array
    {
        $h /= 360;
        $s /= 100;
        $l /= 100;

        $r = $g = $b = $l;

        $v = ($l <= 0.5) ? ($l * (1.0 + $s)) : ($l + $s - $l * $s);
        if ($v > 0) {
            $m = $l + $l - $v;
            $sv = ($v - $m) / $v;
            $h *= 6.0;
            $sextant = floor($h);
            $fract = $h - $sextant;
            $vsf = $v * $sv * $fract;
            $mid1 = $m + $vsf;
            $mid2 = $v - $vsf;

            switch ($sextant) {
                case 0:
                    $r = $v;
                    $g = $mid1;
                    $b = $m;

                    break;
                case 1:
                    $r = $mid2;
                    $g = $v;
                    $b = $m;

                    break;
                case 2:
                    $r = $m;
                    $g = $v;
                    $b = $mid1;

                    break;
                case 3:
                    $r = $m;
                    $g = $mid2;
                    $b = $v;

                    break;
                case 4:
                    $r = $mid1;
                    $g = $m;
                    $b = $v;

                    break;
                case 5:
                    $r = $v;
                    $g = $m;
                    $b = $mid2;

                    break;
            }
        }

        $r *= 255;
        $g *= 255;
        $b *= 255;

        return [
            'r' => new Size($r),
            'g' => new Size($g),
            'b' => new Size($b),
        ];
    }

    private function filterSelectors(DeclarationBlock $block): array
    {
        $filteredSelectors = [];
        foreach ($block->getSelectors() as $selector) {
            $selector = $selector instanceof Selector ? $selector->getSelector() : $selector;

            if (str_starts_with($selector, '.sw-admin-menu')) {
                continue;
            }

            if (str_starts_with($selector, '.sw-sales-channel-menu')) {
                continue;
            }

            if (str_starts_with($selector, '.sw-version')) {
                continue;
            }

            if (str_starts_with($selector, '.sw-alert--system')) {
                continue;
            }

            if (str_starts_with($selector, '.sw-tooltip')) {
                continue;
            }

            if (str_starts_with($selector, '.sw-arrow-field')) {
                continue;
            }

            if (\in_array($selector, [
                '.sw-modal',
                '.sw-data-grid.is--scroll-x .sw-data-grid__cell--selection:before',
                '.sw-data-grid.is--scroll-x .sw-data-grid__cell--actions:before',
                '.sw-login .sw-login__image-headlines',
                '.sw-login .sw-login__badge svg',
                '.sw-cms-list-item .sw-cms-list-item__image',
                '.sw-cms-list-item .sw-cms-list-item__is-default',
                '.sw-cms-create-wizard__step-3 .sw-cms-create-wizard__page-preview .sw-cms-create-wizard__preview_image',
            ], true)) {
                continue;
            }

            if ($selector === ':root') {
                $filteredSelectors[] = ':root[dark-theme="true"]';

                continue;
            }

            $filteredSelectors[] = sprintf(
                '[dark-theme="true"] %s',
                $selector
            );
        }

        return $filteredSelectors;
    }

    /**
     * @return array<BundleInterface>
     */
    private function getAdditionalBundles(Plugin $bundle): array
    {
        $params = new AdditionalBundleParameters(
            $this->pluginLoader->getClassLoader(),
            $this->pluginLoader->getPluginInstances(),
            $this->parameterBag->all()
        );

        return $bundle->getAdditionalBundles($params);
    }
}
