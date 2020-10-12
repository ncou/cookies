<?php

declare(strict_types=1);

namespace Chiron\Cookies\Bootloader;

use Chiron\Bootload\AbstractBootloader;
use Chiron\Http\MiddlewareQueue;
use Chiron\Cookies\Middleware\CollectCookiesMiddleware;
use Chiron\Cookies\Middleware\EncryptCookiesMiddleware;
use Chiron\Cookies\Config\CookiesConfig;

final class CookieMiddlewareBootloader extends AbstractBootloader
{
    public function boot(MiddlewareQueue $middlewares, CookiesConfig $config): void
    {
        if ($config->getEncrypt() === true) {
            $middlewares->addMiddleware(EncryptCookiesMiddleware::class, MiddlewareQueue::PRIORITY_MAX - 10);
        }

        $middlewares->addMiddleware(CollectCookiesMiddleware::class, MiddlewareQueue::PRIORITY_MAX - 20);
    }
}
