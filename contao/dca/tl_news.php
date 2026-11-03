<?php

declare(strict_types=1);

use Contao\CoreBundle\DataContainer\PaletteManipulator;

PaletteManipulator::create()
    ->addLegend('categories_legend', 'expert_legend', PaletteManipulator::POSITION_BEFORE)
    ->addField('newsCategories', 'categories_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_news');

$GLOBALS['TL_DCA']['tl_news']['fields']['newsCategories'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['multiple' => true, 'tl_class' => 'clr'],
    'sql' => ['type' => 'blob', 'notnull' => false],
];

