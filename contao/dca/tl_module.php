<?php

declare(strict_types=1);

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
