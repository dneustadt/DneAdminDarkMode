<?php declare(strict_types=1);

namespace Dne\AdminDarkMode;

use Dne\AdminDarkMode\DependencyInjection\AdminDarkModeCompilerPass;
use Dne\AdminDarkMode\Service\AdminDarkModeCompiler;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
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
        /** @var AdminDarkModeCompiler $compiler */
        $compiler = $this->container->get(AdminDarkModeCompiler::class);
        $compiler->compileBundleAssets();
    }
}
