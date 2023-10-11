<?php declare(strict_types=1);

namespace Dne\AdminDarkMode\Service;

use Shopware\Core\Framework\Plugin\Util\AssetService;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class AdminDarkModeAssetService extends AssetService
{
    private AdminDarkModeCompiler $compiler;

    public function __construct(...$args)
    {
        parent::__construct(...$args);

        foreach ($args as $arg) {
            if (!$arg instanceof AdminDarkModeCompiler) {
                continue;
            }

            $this->compiler = $arg;
        }
    }

    public function copyAssets(BundleInterface $bundle, bool $force = false): void
    {
        parent::copyAssets($bundle, $force);

        $this->compiler->compileAssets($bundle);
    }
}
