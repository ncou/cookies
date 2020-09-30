<?php

declare(strict_types=1);

namespace Chiron\Cookies\Facade;

use Chiron\Core\Facade\AbstractFacade;

final class CookieManager extends AbstractFacade
{
    /**
     * {@inheritdoc}
     */
    protected static function getFacadeAccessor(): string
    {
        return \Chiron\Cookies\CookieManager::class;
    }
}
