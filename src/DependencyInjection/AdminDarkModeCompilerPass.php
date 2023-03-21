<?php declare(strict_types=1);

namespace Dne\AdminDarkMode\DependencyInjection;

use Dne\AdminDarkMode\Service\AdminDarkModeAssetService;
use Dne\AdminDarkMode\Service\AdminDarkModeCompiler;
use Shopware\Core\Framework\Plugin\Util\AssetService;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AdminDarkModeCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition(AssetService::class)
            ->setClass(AdminDarkModeAssetService::class)
            ->addArgument(new Reference(AdminDarkModeCompiler::class));
    }
}
