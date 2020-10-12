<?php

declare(strict_types=1);

namespace Chiron\Cookies\Provider;

use Chiron\Bootload\ServiceProvider\ServiceProviderInterface;
use Chiron\Container\BindingInterface;
use Chiron\Cookies\CookieCollection;
use Psr\Http\Message\ServerRequestInterface;
use Chiron\Core\Exception\ScopeException;
use Closure;

final class CookieCollectionServiceProvider implements ServiceProviderInterface
{
    public function register(BindingInterface $container): void
    {
        // This SHOULDN'T BE a singleton(), use a basic bind() to ensure Request instance is fresh !
        $container->bind(CookieCollection::class, Closure::fromCallable([$this, 'cookieCollection']));
    }

    private function cookieCollection(ServerRequestInterface $request): CookieCollection
    {
        $cookieCollection = $request->getAttribute(CookieCollection::ATTRIBUTE);

        if ($cookieCollection === null) {
            throw new ScopeException('Unable to resolve CookieCollection, invalid request scope.');
        }

        return $cookieCollection;
    }
}
