<?php declare(strict_types=1);

namespace Dne\AdminDarkMode\Service;

use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemOperator;
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
use Shopware\Core\Framework\Plugin\Util\AssetService;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class AdminDarkModeAssetService extends AssetService
{
    private FilesystemOperator $filesystem;

    public function __construct(...$args)
    {
        parent::__construct(...$args);

        foreach ($args as $arg) {
            if (!$arg instanceof FilesystemOperator) {
                continue;
            }

            $this->filesystem = $arg;
        }
    }

    public function copyAssets(BundleInterface $bundle): void
    {
        parent::copyAssets($bundle);

        $bundleName = mb_strtolower($bundle->getName());

        if ($bundleName === 'dneadmindarkmode') {
            return;
        }

        $dir = sprintf(
            'bundles/%s/%s',
            $bundleName,
            $bundleName === 'administration' ? 'static/css' : 'administration/css'
        );

        if (!$this->filesystem->directoryExists($dir)) {
            return;
        }

        $directoryListing = $this->filesystem->listContents($dir)->getIterator();

        /** @var FileAttributes $file */
        foreach ($directoryListing as $file) {
            if (!$file->isFile() || !str_ends_with($file->path(), '.css')) {
                continue;
            }

            $this->filesystem->write(
                $file->path(),
                $this->enrichCss($this->filesystem->read($file->path()))
            );
        }
    }

    private function enrichCss(string $css): string
    {
        $document = (new Parser($css))->parse();

        foreach ($document->getContents() as $ruleSet) {
            if (!$ruleSet instanceof DeclarationBlock) {
                continue;
            }

            if ($this->hasIgnoredSelector($ruleSet)) {
                continue;
            }

            $newBlock = new DeclarationBlock();

            foreach ($ruleSet->getRules() as $rule) {
                $value = $rule->getValue();
                $newRule = new Rule($rule->getRule());

                if ($value instanceof Color && $color = $this->handleColorValue(clone $value, $rule)) {
                    $newRule->setValue($color);
                    $newRule->setIsImportant($rule->getIsImportant());
                    $newBlock->addRule($newRule);
                }

                if ($value instanceof Color || (!$value instanceof RuleValueList && !$value instanceof CSSFunction)) {
                    continue;
                }

                $value = clone $value;
                $foundColor = $this->handleValueList($value, $rule);

                if (!$foundColor) {
                    continue;
                }

                $newRule->setValue($value);
                $newBlock->addRule($newRule);
            }

            if (empty($newBlock->getRules())) {
                continue;
            }

            $originalSelectors = $ruleSet->getSelectors();
            $newSelectors = [];
            foreach ($originalSelectors as $originalSelector) {
                $selector = $originalSelector instanceof Selector ? $originalSelector->getSelector() : $originalSelector;

                if ($selector === ':root') {
                    $newSelectors[] = ':root[dark-theme="true"]';

                    continue;
                }

                $newSelectors[] = sprintf(
                    '[dark-theme="true"] %s',
                    $selector
                );
            }

            $newBlock->setSelectors($newSelectors);
            $document->append($newBlock);
        }

        return $document->render(OutputFormat::createCompact());
    }

    private function handleValueList(ValueList $valueList, Rule $rule, bool &$foundColor = false): bool
    {
        $components = $valueList->getListComponents();

        if (!is_array($components)) {
            return false;
        }

        $newComponents = [];
        foreach ($components as $component) {
            if ($component instanceof Color && $color = $this->handleColorValue(clone $component, $rule)) {
                $newComponents[] = $color;
                $foundColor = true;

                continue;
            }

            if ($component instanceof RuleValueList || $component instanceof CSSFunction) {
                $component = clone $component;
                $this->handleValueList($component, $rule, $foundColor);
            }

            $newComponents[] = $component;
        }

        $valueList->setListComponents($newComponents);

        return $foundColor;
    }

    private function handleColorValue(Color $color, Rule $rule): ?Color
    {
        if ($rule->getRule() === 'box-shadow') {
            return new Color(['r' => new Size(10), 'g' => new Size(10), 'b' => new Size(10)]);
        }

        $components = $color->getListComponents();
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

    private function hasIgnoredSelector(DeclarationBlock $block): bool
    {
        foreach ($block->getSelectors() as $selector) {
            $selector = $selector instanceof Selector ? $selector->getSelector() : $selector;

            if (str_starts_with($selector, '.sw-admin-menu')) {
                return true;
            }

            if (str_starts_with($selector, '.sw-sales-channel-menu')) {
                return true;
            }

            if (str_starts_with($selector, '.sw-version')) {
                return true;
            }

            if ($selector === '.sw-modal') {
                return true;
            }
        }

        return false;
    }
}