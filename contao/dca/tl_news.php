<?php

declare(strict_types=1);

use Contao\CoreBundle\DataContainer\PaletteManipulator;

PaletteManipulator::create()
    ->addLegend('categories_legend', 'expert_legend', PaletteManipulator::POSITION_BEFORE)
    ->addField('newsCategories', 'categories_legend', PaletteManipulator::POSITION_APPEND)
    ->addField('standort', 'categories_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_news');

$GLOBALS['TL_DCA']['tl_news']['fields']['newsCategories'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['multiple' => true, 'tl_class' => 'clr'],
    'sql' => ['type' => 'blob', 'notnull' => false],
];

$GLOBALS['TL_DCA']['tl_news']['fields']['standort'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => static function(): array {
        $connection = \Contao\System::getContainer()->get('database_connection');
        $rows = $connection->fetchAllAssociative('SELECT id, name FROM tl_standort ORDER BY name');
        $options = ['' => '-'];
        foreach ($rows as $row) {
            $options[$row['id']] = $row['name'];
        }
        return $options;
    },
    'eval' => ['tl_class' => 'w50'],
    'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0, 'notnull' => false],
];
