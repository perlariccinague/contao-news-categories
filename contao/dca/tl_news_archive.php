<?php

declare(strict_types=1);

use Contao\CoreBundle\DataContainer\PaletteManipulator;

PaletteManipulator::create()
    ->addLegend('categories_legend', 'protected_legend', PaletteManipulator::POSITION_BEFORE)
    ->addField('allowedNewsCategories', 'categories_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_news_archive');

$GLOBALS['TL_DCA']['tl_news_archive']['fields']['allowedNewsCategories'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'options_callback' => [\Emchundberger\ContaoNewsCategories\DataContainer\CategoryListener::class, 'getAllNewsCategories'],
    'eval' => ['multiple' => true, 'tl_class' => 'clr'],
    'sql' => ['type' => 'blob', 'notnull' => false],
];