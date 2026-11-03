<?php

declare(strict_types=1);

use Contao\ArrayUtil;

// Insert news categories module after news module in the content group
$contentKeys = array_keys($GLOBALS['BE_MOD']['content']);
$newsPos = array_search('news', $contentKeys, true);

ArrayUtil::arrayInsert(
    $GLOBALS['BE_MOD']['content'],
    $newsPos !== false ? $newsPos + 1 : \count($GLOBALS['BE_MOD']['content']),
    [
        'news_categories' => [
            'tables' => ['tl_news_category'],
        ],
    ]
);