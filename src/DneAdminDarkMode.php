<?php declare(strict_types=1);

namespace Dne\AdminDarkMode;

use Dne\AdminDarkMode\DependencyInjection\AdminDarkModeCompilerPass;
use Shopware\Core\Framework\Plugin;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DneAdminDarkMode extends Plugin
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new AdminDarkModeCompilerPass());
    }
}
