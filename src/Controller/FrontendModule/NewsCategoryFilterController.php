<?php

declare(strict_types=1);

namespace KlapprothKoch\ContaoEventNewsCategories\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule('news_category_filter', category: 'miscellaneous', template: 'frontend_module/news_category_filter')]
class NewsCategoryFilterController extends AbstractFrontendModuleController
{
    public function __construct(private readonly Connection $connection)
    {
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $allowedIds = StringUtil::deserialize($model->allowedNewsCategories, true);

        if (!empty($allowedIds)) {
            $placeholders = implode(',', array_fill(0, count($allowedIds), '?'));
            $rows = $this->connection->fetchAllAssociative(
                "SELECT id, name, cssClass FROM tl_news_category WHERE id IN ($placeholders) ORDER BY name",
                array_values($allowedIds)
            );
        } else {
            $rows = $this->connection->fetchAllAssociative(
                'SELECT id, name, cssClass FROM tl_news_category ORDER BY name'
            );
        }

        $activeClass = $request->query->get('category', '');
        $basePath = $request->getBaseUrl() . $request->getPathInfo();
        $currentParams = $request->query->all();

        $categories = [];
        foreach ($rows as $row) {
            $params = $currentParams;
            unset($params['page']);
            $params['category'] = $row['cssClass'];
            $categories[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'cssClass' => $row['cssClass'],
                'url' => $basePath . '?' . http_build_query($params),
                'active' => $row['cssClass'] === $activeClass,
            ];
        }

        $allParams = $currentParams;
        unset($allParams['category'], $allParams['page']);
        $allUrl = $basePath . (!empty($allParams) ? '?' . http_build_query($allParams) : '');

        $template->categories = $categories;
        $template->activeCategory = $activeClass;
        $template->allUrl = $allUrl;

        return $template->getResponse();
    }
}
