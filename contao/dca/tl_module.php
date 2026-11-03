<?php

declare(strict_types=1);

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_module']['palettes']['news_category_filter'] = '{title_legend},name,headline,type;{config_legend},allowedNewsCategories;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

$GLOBALS['TL_DCA']['tl_module']['fields']['allowedNewsCategories'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'options_callback' => static function (): array {
        $connection = \Contao\System::getContainer()->get('database_connection');
        $rows = $connection->fetchAllAssociative('SELECT id, name FROM tl_news_category ORDER BY name');
        $options = [];
        foreach ($rows as $row) {
            $options[$row['id']] = $row['name'];
        }
        return $options;
    },
    'eval' => ['multiple' => true, 'tl_class' => 'clr'],
    'sql' => ['type' => 'blob', 'notnull' => false],
];

PaletteManipulator::create()
    ->addField(
        'enableProjectCategories',
        'config_legend',
        PaletteManipulator::POSITION_APPEND
    )
    ->applyToPalette('newslist', 'tl_module');
$GLOBALS['TL_DCA']['tl_module']['fields']['enableProjectCategories'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'clr'],
    'sql' => ['type' => 'string', 'length' => 1, 'default' => ''],
];
