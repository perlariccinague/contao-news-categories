<?php

declare(strict_types=1);

namespace KlapprothKoch\ContaoEventNewsCategories\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Connection;

class CategoryListener
{
    public function __construct(private ?Connection $connection = null)
    {
    }

    private function getConnection(): Connection
    {
        return $this->connection ??= System::getContainer()->get('database_connection');
    }

    #[AsCallback(table: 'tl_news_category', target: 'fields.cssClass.save')]
    public function generateNewsCssClass(string $value, DataContainer $dc): string
    {
        return $this->generateCssClass($value, (string) ($dc->activeRecord?->name ?? ''));
    }

    #[AsCallback(table: 'tl_event_category', target: 'fields.cssClass.save')]
    public function generateEventCssClass(string $value, DataContainer $dc): string
    {
        return $this->generateCssClass($value, (string) ($dc->activeRecord?->name ?? ''));
    }

    #[AsCallback(table: 'tl_news_archive', target: 'fields.allowedNewsCategories.options')]
    public function getAllNewsCategories(): array
    {
        return $this->fetchCategoryOptions('tl_news_category');
    }

    #[AsCallback(table: 'tl_calendar', target: 'fields.allowedEventCategories.options')]
    public function getAllEventCategories(): array
    {
        return $this->fetchCategoryOptions('tl_event_category');
    }

    #[AsCallback(table: 'tl_news', target: 'fields.newsCategories.options')]
    public function getNewsCategories(DataContainer $dc): array
    {
        return $this->fetchFilteredCategoryOptions('tl_news_category', 'tl_news_archive', 'allowedNewsCategories', $dc);
    }

    #[AsCallback(table: 'tl_calendar_events', target: 'fields.eventCategories.options')]
    public function getEventCategories(DataContainer $dc): array
    {
        return $this->fetchFilteredCategoryOptions('tl_event_category', 'tl_calendar', 'allowedEventCategories', $dc);
    }

    private function generateCssClass(string $value, string $name): string
    {
        if ('' !== $value) {
            return $value;
        }

        return StringUtil::generateAlias($name);
    }

    private function fetchFilteredCategoryOptions(string $categoryTable, string $archiveTable, string $archiveField, DataContainer $dc): array
    {
        $pid = $dc->activeRecord?->pid ?? (int) Input::get('pid');

        if ($pid) {
            $archive = $this->getConnection()->fetchAssociative(
                "SELECT {$archiveField} FROM {$archiveTable} WHERE id = ?",
                [(int) $pid]
            );

            if ($archive !== false && !empty($archive[$archiveField])) {
                $allowed = StringUtil::deserialize($archive[$archiveField], true);

                if (!empty($allowed)) {
                    $placeholders = implode(',', array_fill(0, \count($allowed), '?'));
                    $rows = $this->getConnection()->fetchAllAssociative(
                        "SELECT id, name FROM {$categoryTable} WHERE id IN ({$placeholders}) ORDER BY name",
                        array_map('intval', $allowed)
                    );

                    $options = [];
                    foreach ($rows as $row) {
                        $options[$row['id']] = $row['name'];
                    }

                    return $options;
                }
            }
        }

        return $this->fetchCategoryOptions($categoryTable);
    }

    private function fetchCategoryOptions(string $table): array
    {
        $rows = $this->getConnection()->fetchAllAssociative(
            "SELECT id, name FROM {$table} ORDER BY name"
        );

        $options = [];
        foreach ($rows as $row) {
            $options[$row['id']] = $row['name'];
        }

        return $options;
    }
}
