<?php

declare(strict_types=1);

namespace Chiron\Cookies;

//https://github.com/narrowspark/framework/blob/81f39d7371715ee20aa888a8934c36c536e3d69e/src/Viserio/Component/Cookie/AbstractCookieCollector.php
//https://github.com/narrowspark/framework/blob/81f39d7371715ee20aa888a8934c36c536e3d69e/src/Viserio/Component/Cookie/CookieJar.php

//https://github.com/cakephp/cakephp/blob/master/src/Http/Cookie/CookieCollection.php
//https://github.com/spiral/framework/blob/master/src/Cookies/src/CookieQueue.php

//https://github.com/yiisoft/cookies/blob/master/src/CookieCollection.php

//https://github.com/illuminate/cookie/blob/8831c0de69f44a79c5aa63b356990441c5d5b4a7/CookieJar.php

//https://github.com/php-http/message/blob/master/src/CookieJar.php
//https://github.com/venta/framework/blob/master/src/Http/src/CookieJar.php

use ArrayIterator;
use Countable;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use IteratorAggregate;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Traversable;

// TODO :créer une méthode make() dans cette classe !!! cf classe CookieJar de Laravel

// TODO : il manque une méthode pour "expirer" le cookie c'est à dire lui mettre une ancienne date pour qu'il soit supprimé par le browser lors de l'envoi dans la response.

// TODO : renommer la classe en CookieQueue::class et ajouter une méthode "queue" et "unqueue" et "getQueuedCookies", comme c'est fait dans Laravel !!! cf     https://github.com/laravel/framework/blob/ff5e8c55100fd416ec0c0ff23db583a61ce7a3fb/src/Illuminate/Cookie/CookieJar.php

// TODO : créer une facade pour cette classe qui serait nommée "Cookie" ca permettrait de chainer les appels de maniére logique Cookie::queue(Cookie::make('name', 'value'));

/**
 * Cookie Collection
 *
 * Provides an immutable collection of cookies objects. Adding or removing
 * to a collection returns a *new* collection that you must retain.
 */
// TODO : faire passer la classe en final et virer les protriétés protected.
// TODO : enlever le caractére "immuable" de cette collection car ca ne fonctionnera pas dans notre cas (on veut utiliser cette collection via un attribut de la request elle doit donc être modifiable sous forme de singleton et donc pas etre immuable)

// TODO : implémenter les méthodes : isNotEmpty(); isEmpty();

// TODO : ajouter une méthode pour utiliser des "options" par défault pour créer le cookie ????

// TODO : virer l'utilisation du "clone()" !!!
// TODO : utiliser la classe CookieFactory::class ici pour créer le cookie correctement initialisé dans la méthode add($name, $value) !!!!
class CookieCollection implements IteratorAggregate, Countable
{
    public const ATTRIBUTE = '__cookieCollection__';

    /**
     * Cookie objects
     *
     * @var Cookie[]
     */
    protected $cookies = [];

    /**
     * Constructor
     *
     * @param Cookie[] $cookies Array of cookie objects
     */
    public function __construct(array $cookies = [])
    {
        $this->checkCookies($cookies);
        foreach ($cookies as $cookie) {
            $this->cookies[$cookie->getId()] = $cookie;
        }
    }

    /**
     * Create a Cookie Collection from an array of Set-Cookie Headers
     *
     * @param array $header The array of set-cookie header values.
     * @param array $defaults The defaults attributes.
     * @return static
     */
    // TODO : remonter la méthode createFromHeaderString directement dans la classe CookieCollection, voir même directement dans la classe CookieFactory !!!!
    public static function createFromHeader(array $header, array $defaults = [])
    {
        $cookies = [];
        foreach ($header as $value) {
            try {
                $cookies[] = Cookie::createFromHeaderString($value, $defaults);
            } catch (Exception $e) { // TODO : il faut plutot faire le try/catch sur Throwable
                // Don't blow up on invalid cookies
            }
        }

        return new static($cookies);
    }

    /**
     * Create a new collection from the cookies in a ServerRequest
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request to extract cookie data from
     * @return static
     */
    // TODO : méthode à virer ???? Elle ne semble pas utilisée !!! Eventuellement la déplacer dans le cookieFactory
    public static function createFromServerRequest(ServerRequestInterface $request)
    {
        $data = $request->getCookieParams();
        $cookies = [];
        foreach ($data as $name => $value) {
            $cookies[] = new Cookie($name, $value); // TODO : utiliser la méthode Cookie::create() ????
        }

        return new static($cookies);
    }

    /**
     * Get the number of cookies in the collection.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->cookies);
    }

    /**
     * Add a cookie and get an updated collection.
     *
     * Cookies are stored by id. This means that there can be duplicate
     * cookies if a cookie collection is used for cookies across multiple
     * domains. This can impact how get(), has() and remove() behave.
     *
     * @param Cookie $cookie Cookie instance to add.
     * @return static
     */
    public function add(Cookie $cookie): self
    {
        $this->cookies[$cookie->getId()] = $cookie;

        return $this;
    }

    // TODO : méthode à virer !!!!
    public function add_SAVE(Cookie $cookie)
    {
        $new = clone $this;
        $new->cookies[$cookie->getId()] = $cookie;

        return $new;
    }

    /**
     * Get the first cookie by name.
     *
     * @param string $name The name of the cookie.
     * @return Cookie
     * @throws \InvalidArgumentException If cookie not found.
     */
    public function get(string $name): Cookie
    {
        $key = mb_strtolower($name);
        foreach ($this->cookies as $cookie) {
            if (mb_strtolower($cookie->getName()) === $key) {
                return $cookie;
            }
        }

        throw new InvalidArgumentException(
            sprintf(
                'Cookie %s not found. Use has() to check first for existence.',
                $name
            )
        );
    }

    /**
     * Check if a cookie with the given name exists
     *
     * @param string $name The cookie name to check.
     * @return bool True if the cookie exists, otherwise false.
     */
    public function has(string $name): bool
    {
        $key = mb_strtolower($name);
        foreach ($this->cookies as $cookie) {
            if (mb_strtolower($cookie->getName()) === $key) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create a new collection with all cookies matching $name removed.
     *
     * If the cookie is not in the collection, this method will do nothing.
     *
     * @param string $name The name of the cookie to remove.
     * @return static
     */
    public function remove(string $name)
    {
        $new = clone $this;
        $key = mb_strtolower($name);
        foreach ($new->cookies as $i => $cookie) {
            if (mb_strtolower($cookie->getName()) === $key) {
                unset($new->cookies[$i]);
            }
        }

        return $new;
    }

    /**
     * Checks if only valid cookie objects are in the array
     *
     * @param Cookie[] $cookies Array of cookie objects
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function checkCookies(array $cookies): void
    {
        foreach ($cookies as $index => $cookie) {
            if (!$cookie instanceof Cookie) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Expected `%s[]` as $cookies but instead got `%s` at index %d',
                        static::class,
                        getTypeName($cookie),
                        $index
                    )
                );
            }
        }
    }

    /**
     * Gets the iterator
     *
     * @return Cookie[]
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->cookies);
    }

    /**
     * Add cookies that match the path/domain/expiration to the request.
     *
     * This allows CookieCollections to be used as a 'cookie jar' in an HTTP client
     * situation. Cookies that match the request's domain + path that are not expired
     * when this method is called will be applied to the request.
     *
     * @param \Psr\Http\Message\RequestInterface $request The request to update.
     * @param array $extraCookies Associative array of additional cookies to add into the request. This
     *   is useful when you have cookie data from outside the collection you want to send.
     * @return \Psr\Http\Message\RequestInterface An updated request.
     */
    public function addToRequest(RequestInterface $request, array $extraCookies = []): RequestInterface
    {
        $uri = $request->getUri();
        $cookies = $this->findMatchingCookies(
            $uri->getScheme(),
            $uri->getHost(),
            $uri->getPath() ?: '/'
        );
        $cookies = array_merge($cookies, $extraCookies);
        $cookiePairs = [];
        foreach ($cookies as $key => $value) {
            $cookie = sprintf('%s=%s', rawurlencode($key), rawurlencode($value));
            $size = strlen($cookie);
            if ($size > 4096) {
                triggerWarning(sprintf(
                    'The cookie `%s` exceeds the recommended maximum cookie length of 4096 bytes.',
                    $key
                ));
            }
            $cookiePairs[] = $cookie;
        }

        if (empty($cookiePairs)) {
            return $request;
        }

        return $request->withHeader('Cookie', implode('; ', $cookiePairs));
    }

    /**
     * Find cookies matching the scheme, host, and path
     *
     * @param string $scheme The http scheme to match
     * @param string $host The host to match.
     * @param string $path The path to match
     * @return array An array of cookie name/value pairs
     */
    protected function findMatchingCookies(string $scheme, string $host, string $path): array
    {
        $out = [];
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        foreach ($this->cookies as $cookie) {
            if ($scheme === 'http' && $cookie->isSecure()) {
                continue;
            }
            if (strpos($path, $cookie->getPath()) !== 0) {
                continue;
            }
            $domain = $cookie->getDomain();
            $leadingDot = substr($domain, 0, 1) === '.';
            if ($leadingDot) {
                $domain = ltrim($domain, '.');
            }

            if ($cookie->isExpired($now)) {
                continue;
            }

            $pattern = '/' . preg_quote($domain, '/') . '$/';
            if (!preg_match($pattern, $host)) {
                continue;
            }

            $out[$cookie->getName()] = $cookie->getValue();
        }

        return $out;
    }

    /**
     * Create a new collection that includes cookies from the response.
     *
     * @param \Psr\Http\Message\ResponseInterface $response Response to extract cookies from.
     * @param \Psr\Http\Message\RequestInterface $request Request to get cookie context from.
     * @return static
     */
    public function addFromResponse(ResponseInterface $response, RequestInterface $request)
    {
        $uri = $request->getUri();
        $host = $uri->getHost();
        $path = $uri->getPath() ?: '/';

        $cookies = static::createFromHeader(
            $response->getHeader('Set-Cookie'),
            ['domain' => $host, 'path' => $path]
        );
        $new = clone $this;
        foreach ($cookies as $cookie) {
            $new->cookies[$cookie->getId()] = $cookie;
        }
        $new->removeExpiredCookies($host, $path);

        return $new;
    }

    /**
     * Remove expired cookies from the collection.
     *
     * @param string $host The host to check for expired cookies on.
     * @param string $path The path to check for expired cookies on.
     * @return void
     */
    protected function removeExpiredCookies(string $host, string $path): void
    {
        $time = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $hostPattern = '/' . preg_quote($host, '/') . '$/';

        foreach ($this->cookies as $i => $cookie) {
            if (!$cookie->isExpired($time)) {
                continue;
            }
            $pathMatches = strpos($path, $cookie->getPath()) === 0;
            $hostMatches = preg_match($hostPattern, $cookie->getDomain());
            if ($pathMatches && $hostMatches) {
                unset($this->cookies[$i]);
            }
        }
    }
}
