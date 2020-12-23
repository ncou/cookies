<?php

declare(strict_types=1);

namespace Chiron\Cookies;

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
use Chiron\Http\Config\HttpConfig;
use Chiron\Cookies\Config\CookiesConfig;

//https://github.com/flarum/core/blob/master/src/Http/CookieFactory.php

/**
 * Create a new Cookie instance using the default config values.
 * If the basepath is defined in the http config, it will be appened to the cookie path value.
 */
// TODO : utiliser cette classe dans la classe CoolieCollection pour créer le nouveau Cookie::class qu'on ajoutera ensuite dans la collection !!!!
// TODO : créer une nouvelle classe de "Facade" pour cette classe de CookieFactory
final class CookieFactory
{
    private $cookiesConfig;
    private $httpConfig;

    public function __construct(CookiesConfig $cookiesConfig, HttpConfig $httpConfig)
    {
        $this->cookiesConfig = $cookiesConfig;
        $this->httpConfig = $httpConfig;
    }

    /**
     * Factory method to create Cookie instances.
     *
     * @param string $name Cookie name
     * @param string|array $value Value of the cookie
     * @param int|\DateTime|\DateTimeImmutable|null $expires Expiration time and date
     *
     * @return Cookie
     */
    // TODO : le paramétre $value doit être de type ?string
    // TODO utiliser le "cookie_name" / "cookie_age" dans le csrfConfig le "cookie_path" via httpConfig et utiliser dans le cookieConfig : "cookie_domain" / "cookie_httponly" / "samesite" / "secure"
    // TODO : permettre de passer null à la $value du cookie. cad faire un typehint du paramétre à '?string' qui sera emplacé par une chaine vide
    public function create(string $name, $value = '', $expires): Cookie
    {
        // TODO : utiliser les 2 fichiers de config pour initialiser les valeurs par défaut du cookie => path/domain/samesite/httponly/secure
        $options = [
            'expires'  => $expires,
            'path'     => $this->httpConfig->getBasePath(), // TODO : il faut vérifier que cette méthode getBasePath() retourne bien à minima '/' même si c'est vide dans la config !!! il faudra surement toujours avoir un caractéer '/' à la fin de cette chaine !!!! vérifier si il y a bien une méthode normalizePath() dans la classe Cookie::class
            //'domain'   => $this->cookiesConfig->isCookieSecure(),
            //'secure'   => $this->cookiesConfig->isCookieSecure(),
            //'httponly' => $this->cookiesConfig->getHttpOnly(),
            //'samesite' => $this->cookiesConfig->getSameSite()
        ];
        // TODO : utiliser un code du style suivant avec la request :
        //$this->path = $config['cookie.path'] ?? $url->getPath() ?: '/';
        //$this->secure = $config['cookie.secure'] ?? $url->getScheme() === 'https';

        return Cookie::create($name, $value, $options);
    }
}
