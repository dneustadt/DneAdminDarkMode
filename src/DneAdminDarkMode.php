<?php declare(strict_types=1);

namespace Dne\AdminDarkMode;

use Dne\AdminDarkMode\DependencyInjection\AdminDarkModeCompilerPass;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
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

    public function uninstall(UninstallContext $uninstallContext): void
    {
        if (!$uninstallContext->keepUserData()) {
            $this->assetsInstall();
        }
    }

    private function assetsInstall(): void
    {
        $application = new Application($this->container->get('kernel'));
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'assets:install',
        ]);

        $application->run($input, new NullOutput());
    }
}
