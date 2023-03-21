<?php declare(strict_types=1);

namespace Dne\AdminDarkMode;

use Dne\AdminDarkMode\DependencyInjection\AdminDarkModeCompilerPass;
use Dne\AdminDarkMode\Service\AdminDarkModeAssetService;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Plugin\Util\AssetService;
use Shopware\Core\Kernel;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DneAdminDarkMode extends Plugin
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new AdminDarkModeCompilerPass());
    }

    public function postUpdate(UpdateContext $updateContext): void
    {
        if ($updateContext->getPlugin()->isActive()) {
            $this->assetsInstall();
        }
    }

    public function activate(ActivateContext $activateContext): void
    {
        $this->assetsInstall();
    }

    private function assetsInstall(): void
    {
        /** @var Kernel $kernel */
        $kernel = $this->container->get('kernel');
        /** @var AdminDarkModeAssetService $assetService */
        $assetService = $this->container->get(AssetService::class);
        $assetService->darkModeCompile = true;

        foreach ($kernel->getBundles() as $bundle) {
            $assetService->copyAssetsFromBundle($bundle->getName());
        }

        $assetService->darkModeCompile = false;
    }
}
