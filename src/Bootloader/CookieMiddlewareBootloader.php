<?php

declare(strict_types=1);

namespace Chiron\Cookies\Bootloader;

use Chiron\Bootload\AbstractBootloader;
use Chiron\Http\Http;
use Chiron\Cookies\Middleware\CollectCookiesMiddleware;
use Chiron\Cookies\Middleware\EncryptCookiesMiddleware;
use Chiron\Cookies\Config\CookiesConfig;

final class CookieMiddlewareBootloader extends AbstractBootloader
{
    public function boot(Http $http, CookiesConfig $config): void
    {
        if ($config->getEncrypt() === true) {
            $http->addMiddleware(EncryptCookiesMiddleware::class, Http::MAX - 10);
        }

        $http->addMiddleware(CollectCookiesMiddleware::class, Http::MAX - 20);
    }
}
