<?php

declare(strict_types=1);

namespace Chiron\Cookies\Bootloader;

use Chiron\Core\Container\Bootloader\AbstractBootloader;
use Chiron\Http\Http;
use Chiron\Cookies\Middleware\CollectCookiesMiddleware;
use Chiron\Cookies\Middleware\EncryptCookiesMiddleware;
use Chiron\Cookies\Config\CookiesConfig;

final class CookieMiddlewareBootloader extends AbstractBootloader
{
    public function boot(Http $http, CookiesConfig $config): void
    {
        if ($config->getEncrypt() === true) {
            $http->addMiddleware(EncryptCookiesMiddleware::class, Http::PRIORITY_MAX - 10);
        }

        $http->addMiddleware(CollectCookiesMiddleware::class, Http::PRIORITY_MAX - 20);
    }
}
