<?php

declare(strict_types=1);

namespace Emchundberger\ContaoNewsCategories\EventListener;

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
        error_log("=== NewsListListener::countItems START ===");
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return false;
        }

        $categoryClass = $request->query->get('category', '');
        $standortId = $request->query->get('standort', '');

        error_log("=== FILTER DEBUG ===");
        error_log("categoryClass: " . $categoryClass);
        error_log("standortId: " . $standortId);

        $qb = $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('tl_news')
            ->where('pid IN (:archives)')
            ->andWhere("published = '1'")
            ->andWhere("(start = '' OR start <= :time)")
            ->andWhere("(stop = '' OR stop > :time)")
            ->setParameter('archives', $newsArchives, ArrayParameterType::INTEGER)
            ->setParameter('time', time());

        if ($categoryClass !== '') {

            $category = $this->findCategoryByClass('tl_news_category', $categoryClass);

            if (null !== $category) {

                $qb->andWhere('newsCategories REGEXP :pattern')
                    ->setParameter(
                        'pattern',
                        $this->buildSerializedPattern($category['id'])
                    );

            }
        }

        if ($standortId !== '') {

            $qb->andWhere('standort = :standort')
                ->setParameter('standort', (int) $standortId);

        }


        if (true === $featured) {

            $qb->andWhere("featured = '1'");

        } elseif (false === $featured) {

            $qb->andWhere("featured != '1'");

        }

        // DEBUG
        error_log('=== COUNT SQL ===');
        error_log($qb->getSQL());
        error_log(print_r($qb->getParameters(), true));


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
        $standortId = $request->query->get('standort', '');
        error_log('standortId = [' . $standortId . ']');

        $qb = $this->connection->createQueryBuilder()
            ->select('id')
            ->from('tl_news')
            ->where('pid IN (:archives)')
            ->andWhere("published = '1'")
            ->andWhere("(start = '' OR start <= :time)")
            ->andWhere("(stop = '' OR stop > :time)")
            ->orderBy('date', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->setParameter('archives', $newsArchives, ArrayParameterType::INTEGER)
            ->setParameter('time', time());


        if ($categoryClass !== '') {

            $category = $this->findCategoryByClass('tl_news_category', $categoryClass);

            if (null !== $category) {

                $qb->andWhere('newsCategories REGEXP :pattern')
                    ->setParameter(
                        'pattern',
                        $this->buildSerializedPattern($category['id'])
                    );

            }
        }

        if ($standortId !== '') {

            $qb->andWhere('standort = :standort')
                ->setParameter('standort', (int) $standortId);

        }



        if (true === $featured) {

            $qb->andWhere("featured = '1'");

        } elseif (false === $featured) {

            $qb->andWhere("featured != '1'");

        }
        error_log('=== FETCH SQL ===');
        error_log($qb->getSQL());
        error_log(print_r($qb->getParameters(), true));

        $ids = $qb->fetchFirstColumn();
        error_log('IDS: ' . print_r($ids, true));

        if (empty($ids)) {
            return [];
        }


        return NewsModel::findMultipleByIds($ids, ['order' => 'date DESC']);
    }



    #[AsHook('parseArticles')]
    public function addCategoriesToTemplate(Template $template, array $row, Module $module): void
    {

        // News Kategorien
        $ids = StringUtil::deserialize($row['newsCategories'] ?? '', true);


        if (!empty($ids)) {

            $rows = $this->connection->fetchAllAssociative(
                'SELECT name, cssClass FROM tl_news_category WHERE id IN (?)',
                [$ids],
                [ArrayParameterType::INTEGER]
            );


            $template->newsCategories = $rows;

        } else {

            $template->newsCategories = [];

        }


        $standortId = $row['standort'] ?? null;


        if ($standortId) {

            $standort = $this->connection->fetchAssociative(
                'SELECT name FROM tl_standort WHERE id = ?',
                [$standortId]
            );


            $template->standort = $standort['name'] ?? '';

        } else {

            $template->standort = '';

        }

    }



    private function findCategoryByClass(string $table, string $cssClass): ?array
    {
        $row = $this->connection->fetchAssociative(
            "SELECT id FROM {$table} WHERE cssClass = ?",
            [$cssClass]
        );


        return $row ?: null;
    }



    private function buildSerializedPattern(int|string $id): string
    {
        return 's:[0-9]+:"' . (int) $id . '";';
    }
}