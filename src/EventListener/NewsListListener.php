<?php

declare(strict_types=1);

namespace KlapprothKoch\ContaoEventNewsCategories\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Model\Collection;
use Contao\Module;
use Contao\NewsModel;
use Contao\StringUtil;
use Contao\Template;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;

class NewsListListener
{
    public function __construct(
        private readonly Connection $connection,
        private readonly RequestStack $requestStack,
    ) {
    }

    #[AsHook('newsListCountItems')]
    public function countItems(array $newsArchives, ?bool $featured, object $module): int|false
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return false;
        }

        $categoryClass = $request->query->get('category', '');
        if ('' === $categoryClass) {
            return false;
        }

        $category = $this->findCategoryByClass('tl_news_category', $categoryClass);
        if (null === $category) {
            return 0;
        }

        $qb = $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('tl_news')
            ->where('pid IN (:archives)')
            ->andWhere("published = '1'")
            ->andWhere("(start = '' OR start <= :time)")
            ->andWhere("(stop = '' OR stop > :time)")
            ->andWhere('newsCategories REGEXP :pattern')
            ->setParameter('archives', $newsArchives, ArrayParameterType::INTEGER)
            ->setParameter('time', time())
            ->setParameter('pattern', $this->buildSerializedPattern($category['id']));

        if (true === $featured) {
            $qb->andWhere("featured = '1'");
        } elseif (false === $featured) {
            $qb->andWhere("featured != '1'");
        }

        return (int) $qb->fetchOne();
    }

    #[AsHook('newsListFetchItems')]
    public function fetchItems(array $newsArchives, ?bool $featured, int $limit, int $offset, object $module): Collection|array|false
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return false;
        }

        $categoryClass = $request->query->get('category', '');
        if ('' === $categoryClass) {
            return false;
        }

        $category = $this->findCategoryByClass('tl_news_category', $categoryClass);
        if (null === $category) {
            return [];
        }

        $qb = $this->connection->createQueryBuilder()
            ->select('id')
            ->from('tl_news')
            ->where('pid IN (:archives)')
            ->andWhere("published = '1'")
            ->andWhere("(start = '' OR start <= :time)")
            ->andWhere("(stop = '' OR stop > :time)")
            ->andWhere('newsCategories REGEXP :pattern')
            ->orderBy('date', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->setParameter('archives', $newsArchives, ArrayParameterType::INTEGER)
            ->setParameter('time', time())
            ->setParameter('pattern', $this->buildSerializedPattern($category['id']));

        if (true === $featured) {
            $qb->andWhere("featured = '1'");
        } elseif (false === $featured) {
            $qb->andWhere("featured != '1'");
        }

        $ids = $qb->fetchFirstColumn();

        if (empty($ids)) {
            return [];
        }

        return NewsModel::findMultipleByIds($ids, ['order' => 'date DESC']);
    }

    #[AsHook('parseArticles')]
    public function addCategoriesToTemplate(Template $template, array $row, Module $module): void
    {
        $ids = StringUtil::deserialize($row['newsCategories'] ?? '', true);

        if (empty($ids)) {
            $template->newsCategories = [];
            return;
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT name, cssClass FROM tl_news_category WHERE id IN (?)',
            [$ids],
            [ArrayParameterType::INTEGER]
        );

        $template->newsCategories = $rows;
    }

    private function findCategoryByClass(string $table, string $cssClass): ?array
    {
        $row = $this->connection->fetchAssociative(
            "SELECT id FROM {$table} WHERE cssClass = ?",
            [$cssClass]
        );

        return $row ?: null;
    }

    /**
     * Builds a REGEXP pattern that safely matches a category ID inside a PHP-serialized array.
     * Example for ID 5: s:[0-9]+:"5"; — matches s:1:"5"; but not s:2:"51";
     */
    private function buildSerializedPattern(int|string $id): string
    {
        return 's:[0-9]+:"' . (int) $id . '";';
    }
}
