<?php declare(strict_types=1);

namespace Dne\AdminDarkMode\DependencyInjection;

use Dne\AdminDarkMode\Service\AdminDarkModeAssetService;
use Shopware\Core\Framework\Plugin\Util\AssetService;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AdminDarkModeCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition(AssetService::class)->setClass(AdminDarkModeAssetService::class)->setPublic(true);
    }
}
