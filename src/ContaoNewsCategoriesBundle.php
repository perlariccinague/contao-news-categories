<?php

declare(strict_types=1);

namespace KlapprothKoch\ContaoNewsCategories;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ContaoNewsCategoriesBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}