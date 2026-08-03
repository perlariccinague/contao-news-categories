<?php

declare(strict_types=1);

namespace Emchundberger\ContaoNewsCategories\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\NewsBundle\ContaoNewsBundle;
use Emchundberger\ContaoNewsCategories\ContaoNewsCategoriesBundle;

class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(ContaoNewsCategoriesBundle::class)
                ->setLoadAfter([
                    ContaoCoreBundle::class,
                    ContaoNewsBundle::class,
                ]),
        ];
    }
}