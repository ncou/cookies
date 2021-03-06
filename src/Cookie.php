<?php

declare(strict_types=1);

namespace Chiron\Cookies;

use Cake\Utility\Hash;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

// NORMALIZE DOMAIN :
//https://github.com/delight-im/PHP-Cookie/blob/master/src/Cookie.php#L540
//https://github.com/guzzle/guzzle/blob/master/src/Cookie/SetCookie.php#L339

//https://github.com/php-http/message/blob/master/src/Cookie.php

// TODO : ajouter dans la documentation que dans le cookie name, les caractéres espaces, points et signe plus sont remplacés par des underscore par PHP (notamment lors de la récupération dans $_COOKIE) => PHP replaces dots and spaces in cookie names with underscores.

//https://github.com/slimphp/Slim-Psr7/blob/master/src/Cookies.php

//https://github.com/dflydev/dflydev-fig-cookies/blob/master/src/Dflydev/FigCookies/StringUtil.php

//https://github.com/narrowspark/framework/blob/81f39d7371715ee20aa888a8934c36c536e3d69e/src/Viserio/Component/Cookie/SetCookie.php
//https://github.com/narrowspark/framework/blob/81f39d7371715ee20aa888a8934c36c536e3d69e/src/Viserio/Component/Cookie/AbstractCookie.php
//https://github.com/narrowspark/framework/blob/81f39d7371715ee20aa888a8934c36c536e3d69e/src/Viserio/Component/Cookie/Cookie.php
//https://github.com/narrowspark/framework/blob/81f39d7371715ee20aa888a8934c36c536e3d69e/src/Viserio/Component/Cookie/RequestCookies.php
//https://github.com/narrowspark/framework/blob/81f39d7371715ee20aa888a8934c36c536e3d69e/src/Viserio/Component/Cookie/ResponseCookies.php

//https://github.com/php-http/message/blob/master/src/Cookie.php
//https://github.com/brick/http/blob/master/src/Cookie.php

// TODO : utiliser une validation du nom du cookie, ainsi qu'une validation du contenu du cookie => https://github.com/narrowspark/framework/blob/81f39d7371715ee20aa888a8934c36c536e3d69e/src/Viserio/Component/Cookie/Traits/CookieValidatorTrait.php

//https://github.com/yiisoft/cookies/blob/master/src/Cookie.php

// TODO : il faut ajouter la gestion du max-age.

// TODO : attention normalement si on choisi samesite = None il faut vérifier que secure est bien à true !!!! => https://developer.mozilla.org/fr/docs/Web/HTTP/Headers/Set-Cookie/SameSite
// https://github.com/spiral/cookies/blob/c35f0640992226fe444756664aa784ae4792de1c/src/Cookie/SameSite.php#L45

//https://github.com/cakephp/cakephp/blob/master/src/Http/Cookie/Cookie.php

//https://github.com/symfony/http-foundation/blob/master/Cookie.php
//https://github.com/spiral/cookies/blob/master/src/Cookie.php
//https://github.com/paragonie/PHP-Cookie/blob/master/src/Cookie.php

/**
 * Cookie object to build a cookie and turn it into a header value
 *
 * An HTTP cookie (also called web cookie, Internet cookie, browser cookie or
 * simply cookie) is a small piece of data sent from a website and stored on
 * the user's computer by the user's web browser while the user is browsing.
 *
 * Cookies were designed to be a reliable mechanism for websites to remember
 * stateful information (such as items added in the shopping cart in an online
 * store) or to record the user's browsing activity (including clicking
 * particular buttons, logging in, or recording which pages were visited in
 * the past). They can also be used to remember arbitrary pieces of information
 * that the user previously entered into form fields such as names, and preferences.
 *
 * Cookie objects are immutable, and you must re-assign variables when modifying
 * cookie objects:
 *
 * ```
 * $cookie = $cookie->withValue('0');
 * ```
 *
 * @link https://tools.ietf.org/html/draft-ietf-httpbis-rfc6265bis-03
 * @link https://en.wikipedia.org/wiki/HTTP_cookie
 * @see \Cake\Http\Cookie\CookieCollection for working with collections of cookies.
 * @see \Cake\Http\Response::getCookieCollection() for working with response cookies.
 */
// TODO : passer la classe en final et virer les champs protected !!!!
class Cookie
{
    /**
     * Expires attribute format.
     *
     * @var string
     */
    public const EXPIRES_FORMAT = 'D, d-M-Y H:i:s T';

    /**
     * SameSite attribute value: Lax
     *
     * @var string
     */
    public const SAMESITE_LAX = 'Lax';

    /**
     * SameSite attribute value: Strict
     *
     * @var string
     */
    public const SAMESITE_STRICT = 'Strict';

    /**
     * SameSite attribute value: None
     *
     * @var string
     */
    public const SAMESITE_NONE = 'None';

    /**
     * Valid values for "SameSite" attribute.
     *
     * @var string[]
     */
    public const SAMESITE_VALUES = [
        self::SAMESITE_LAX,
        self::SAMESITE_STRICT,
        self::SAMESITE_NONE,
    ];

    /**
     * Cookie name
     *
     * @var string
     */
    protected $name = '';

    /**
     * Raw Cookie value.
     *
     * @var string|array
     */
    protected $value = '';

    /**
     * Whether or not a JSON value has been expanded into an array.
     *
     * @var bool
     */
    protected $isExpanded = false;

    /**
     * Expiration time
     *
     * @var \DateTime|\DateTimeImmutable|null
     */
    protected $expiresAt;

    /**
     * Path
     *
     * @var string
     */
    protected $path = '/';

    /**
     * Domain
     *
     * @var string
     */
    protected $domain = '';

    /**
     * Secure
     *
     * @var bool
     */
    protected $secure = false;

    /**
     * HTTP only
     *
     * @var bool
     */
    protected $httpOnly = false;

    /**
     * Samesite
     *
     * @var string|null
     */
    protected $sameSite = null;

    /**
     * Default attributes for a cookie.
     *
     * @var array
     * @see \Cake\Cookie\Cookie::setDefaults()
     */
    protected static $defaults = [
        'expires' => null,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => false,
        'samesite' => null,
    ];

    /**
     * Constructor
     *
     * The constructors args are similar to the native PHP `setcookie()` method.
     * The only difference is the 3rd argument which excepts null or an
     * DateTime or DateTimeImmutable object instead an integer.
     *
     * @link http://php.net/manual/en/function.setcookie.php
     * @param string $name Cookie name
     * @param string|array $value Value of the cookie
     * @param \DateTime|\DateTimeImmutable|null $expiresAt Expiration time and date
     * @param string|null $path Path
     * @param string|null $domain Domain
     * @param bool|null $secure Is secure
     * @param bool|null $httpOnly HTTP Only
     * @param string|null $sameSite Samesite
     */
    // TODO : le paramétre $value doit être de type ?string
    public function __construct(
        string $name,
        $value = '',
        ?DateTimeInterface $expiresAt = null,
        ?string $path = null,
        ?string $domain = null,
        ?bool $secure = null,
        ?bool $httpOnly = null,
        ?string $sameSite = null
    ) {
        $this->validateName($name);
        $this->name = $name;

        $this->_setValue($value);

        $this->domain = $domain ?? static::$defaults['domain'];
        $this->httpOnly = $httpOnly ?? static::$defaults['httponly'];
        $this->path = $path ?? static::$defaults['path'];
        $this->secure = $secure ?? static::$defaults['secure'];
        if ($sameSite === null) {
            $this->sameSite = static::$defaults['samesite'];
        } else {
            $this->validateSameSiteValue($sameSite);
            $this->sameSite = $sameSite;
        }

        if ($expiresAt) {
            $expiresAt = $expiresAt->setTimezone(new DateTimeZone('GMT'));
        } else {
            $expiresAt = static::$defaults['expires'];
        }
        $this->expiresAt = $expiresAt;
    }

    /**
     * Set default options for the cookies.
     *
     * Valid option keys are:
     *
     * - `expires`: Can be a UNIX timestamp or `strtotime()` compatible string or `DateTimeInterface` instance or `null`.
     * - `path`: A path string. Defauts to `'/'`.
     * - `domain`: Domain name string. Defaults to `''`.
     * - `httponly`: Boolean. Defaults to `false`.
     * - `secure`: Boolean. Defaults to `false`.
     * - `samesite`: Can be one of `self::SAMESITE_LAX`, `self::SAMESITE_STRICT`,
     *    `self::SAMESITE_NONE` or `null`. Defaults to `null`.
     *
     * @param array $options Default options.
     * @return void
     */
    public static function setDefaults(array $options): void
    {
        if (isset($options['expires'])) {
            $options['expires'] = static::dateTimeInstance($options['expires']);
        }
        if (isset($options['samesite'])) {
            static::validateSameSiteValue($options['samesite']);
        }

        static::$defaults = $options + static::$defaults;
    }

    /**
     * Factory method to create Cookie instances.
     *
     * @param string $name Cookie name
     * @param string|array $value Value of the cookie
     * @param array $options Cookies options.
     * @return static
     * @see \Cake\Cookie\Cookie::setDefaults()
     */
    // TODO : il faudrait pas mettre une valeur par défault à '' pour le paramétre $value ????
    // TODO : le paramétre $value doit être de type ?string
    public static function create(string $name, $value, array $options = [])
    {
        $options += static::$defaults;
        $options['expires'] = static::dateTimeInstance($options['expires']);

        return new static(
            $name,
            $value,
            $options['expires'],
            $options['path'],
            $options['domain'],
            $options['secure'],
            $options['httponly'],
            $options['samesite']
        );
    }

    /**
     * Converts non null expiry value into DateTimeInterface instance.
     *
     * @param mixed $expires Expiry value.
     * @return \DateTimeInterface|null
     */
    protected static function dateTimeInstance($expires): ?DateTimeInterface
    {
        if ($expires === null) {
            return $expires;
        }

        if ($expires instanceof DateTimeInterface) {
            /** @psalm-suppress UndefinedInterfaceMethod */
            return $expires->setTimezone(new DateTimeZone('GMT'));
        }

        if (!is_string($expires) && !is_int($expires)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid type `%s` for expires. Expected an string, integer or DateTime object.',
                getTypeName($expires)
            ));
        }

        if (!is_numeric($expires)) {
            $expires = strtotime($expires) ?: null;
        }

        if ($expires !== null) {
            $expires = new DateTimeImmutable('@' . (string)$expires);
        }

        return $expires;
    }

    /**
     * Create Cookie instance from "set-cookie" header string.
     *
     * @param string $cookie Cookie header string.
     * @param array $defaults Default attributes.
     * @return static
     * @see \Cake\Cookie\Cookie::setDefaults()
     */
    public static function createFromHeaderString(string $cookie, array $defaults = [])
    {
        if (strpos($cookie, '";"') !== false) {
            $cookie = str_replace('";"', '{__cookie_replace__}', $cookie);
            $parts = str_replace('{__cookie_replace__}', '";"', explode(';', $cookie));
        } else {
            $parts = preg_split('/\;[ \t]*/', $cookie);
        }

        [$name, $value] = explode('=', array_shift($parts), 2);
        $data = [
                'name' => urldecode($name),
                'value' => urldecode($value),
            ] + $defaults;

        foreach ($parts as $part) {
            if (strpos($part, '=') !== false) {
                [$key, $value] = explode('=', $part);
            } else {
                $key = $part;
                $value = true;
            }

            $key = strtolower($key);
            $data[$key] = $value;
        }

        if (isset($data['max-age'])) {
            $data['expires'] = time() + (int)$data['max-age'];
            unset($data['max-age']);
        }

        if (isset($data['samesite'])) {
            // Ignore invalid value when parsing headers
            // https://tools.ietf.org/html/draft-west-first-party-cookies-07#section-4.1
            if (!in_array($data['samesite'], self::SAMESITE_VALUES, true)) {
                unset($data['samesite']);
            }
        }

        $name = (string)$data['name'];
        $value = (string)$data['value'];
        unset($data['name'], $data['value']);

        return Cookie::create(
            $name,
            $value,
            $data
        );
    }

    /**
     * Returns a header value as string
     *
     * @return string
     */
    public function toHeaderValue(): string
    {
        $value = $this->value;
        if ($this->isExpanded) {
            /** @psalm-suppress PossiblyInvalidArgument */
            $value = $this->_flatten($this->value);
        }
        $headerValue = [];
        /** @psalm-suppress PossiblyInvalidArgument */
        // TODO : il faudrait soit faire un rawurlencode sur $this->name, soit à minima modifier certains caractéres : https://github.com/symfony/symfony/blob/5.x/src/Symfony/Component/HttpFoundation/Cookie.php#L267
        $headerValue[] = sprintf('%s=%s', $this->name, rawurlencode($value));

        if ($this->expiresAt) {
            $headerValue[] = sprintf('expires=%s', $this->getFormattedExpires());
        }
        if ($this->path !== '') {
            $headerValue[] = sprintf('path=%s', $this->path);
        }
        if ($this->domain !== '') {
            $headerValue[] = sprintf('domain=%s', $this->domain);
        }
        if ($this->sameSite) {
            $headerValue[] = sprintf('samesite=%s', $this->sameSite);
        }
        if ($this->secure) {
            $headerValue[] = 'secure';
        }
        if ($this->httpOnly) {
            $headerValue[] = 'httponly';
        }

        return implode('; ', $headerValue);
    }

    /**
     * @inheritDoc
     */
    public function withName(string $name)
    {
        $this->validateName($name);
        $new = clone $this;
        $new->name = $name;

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function getId(): string
    {
        return "{$this->name};{$this->domain};{$this->path}";
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Validates the cookie name
     *
     * @param string $name Name of the cookie
     * @return void
     * @throws \InvalidArgumentException
     *
     * @see https://tools.ietf.org/html/rfc6265#section-4.1.1 Rules for 'set-cookie' header.
     * @see https://tools.ietf.org/html/rfc2616#section-2.2 Rules for naming header.
     */
    // TODO : utiliser cette méthode pour valider le nom : https://github.com/zendframework/zend-http/blob/master/src/Header/HeaderValue.php#L69
    // TODO : autre exemple : https://github.com/delight-im/PHP-Cookie/blob/master/src/Cookie.php#L482
    // TODO : autre exemple : https://github.com/guzzle/guzzle/blob/master/src/Cookie/SetCookie.php#L385
    // TODO : déplacer ce controle dans une classe Header::isValidName() ????
    // https://github.com/ventoviro/windwalker-packages/blob/13def63ace1c49befde2200c614c6543f7002ea4/packages/http/src/Helper/HeaderHelper.php#L63
    // TODO : utiliser une regex pour valider le nom du cookie => https://github.com/yiisoft/cookies/blob/master/src/Cookie.php#L35     /   https://developer.mozilla.org/fr/docs/Web/HTTP/Headers/Set-Cookie
    protected function validateName(string $name): void
    {
        // Header name should be at least one character long.
        // Invalid characters are: control characters (0-31;127), space, tab and the following: ()<>@,;:\"/?={}
        if (! preg_match('/^[a-zA-Z0-9\'`#$%&*+.^_|~!-]+$/', $name)) { // TODO : déplacer ce contrôle dans la classe Header::class sous le nom isValidToken ???
            throw new InvalidArgumentException('The cookie name cannot be empty or contains invalid characters.');
        }
    }

    protected function validateName_OLD(string $name): void
    {
        // Header name should be at least one character long.
        if ($name === '') {
            throw new InvalidArgumentException('The cookie name cannot be empty.');
        }

        // Invalid characters are: control characters (0-31;127), space, tab and the following: ()<>@,;:\"/?={}
        if (preg_match('/[\x00-\x20\x22\x28-\x29\x2c\x2f\x3a-\x40\x5c\x7b\x7d\x7f]/', $name)) {
            //throw new InvalidArgumentException(sprintf('The cookie name `%s` contains invalid characters.', $name));
            throw new InvalidArgumentException(sprintf('The cookie name `%s` contains invalid characters.', $name));
        }
    }

    /**
     * @inheritDoc
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Gets the cookie value as a string.
     *
     * This will collapse any complex data in the cookie with json_encode()
     *
     * @return mixed
     * @deprecated 4.0.0 Use {@link getScalarValue()} instead.
     */
    public function getStringValue()
    {
        return $this->getScalarValue();
    }

    /**
     * @inheritDoc
     */
    public function getScalarValue()
    {
        if ($this->isExpanded) {
            /** @psalm-suppress PossiblyInvalidArgument */
            return $this->_flatten($this->value);
        }

        return $this->value;
    }

    /**
     * @inheritDoc
     */
    // TODO : permettre de passer null à la valeur du cookie. cad faire un typehint du paramétre à '?string'
    public function withValue($value)
    {
        $new = clone $this;
        $new->_setValue($value);

        return $new;
    }

    /**
     * Setter for the value attribute.
     *
     * @param string|array $value The value to store.
     * @return void
     */
    protected function _setValue($value): void
    {
        $this->isExpanded = is_array($value);
        $this->value = $value;
    }

    /**
     * @inheritDoc
     */
    public function withPath(string $path)
    {
        $new = clone $this;
        $new->path = $path;

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @inheritDoc
     */
    public function withDomain(string $domain)
    {
        $new = clone $this;
        $new->domain = $domain;

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * @inheritDoc
     */
    public function isSecure(): bool
    {
        return $this->secure;
    }

    /**
     * @inheritDoc
     */
    public function withSecure(bool $secure)
    {
        $new = clone $this;
        $new->secure = $secure;

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withHttpOnly(bool $httpOnly)
    {
        $new = clone $this;
        $new->httpOnly = $httpOnly;

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function isHttpOnly(): bool
    {
        return $this->httpOnly;
    }

    /**
     * @inheritDoc
     */
    public function withExpiry($dateTime)
    {
        $new = clone $this;
        $new->expiresAt = $dateTime->setTimezone(new DateTimeZone('GMT'));

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function getExpiry()
    {
        return $this->expiresAt;
    }

    /**
     * @inheritDoc
     */
    public function getExpiresTimestamp(): ?int
    {
        if (!$this->expiresAt) {
            return null;
        }

        return (int)$this->expiresAt->format('U');
    }

    /**
     * @inheritDoc
     */
    public function getFormattedExpires(): string
    {
        if (!$this->expiresAt) {
            return '';
        }

        return $this->expiresAt->format(static::EXPIRES_FORMAT);
    }

    /**
     * @inheritDoc
     */
    public function isExpired($time = null): bool
    {
        $time = $time ?: new DateTimeImmutable('now', new DateTimeZone('UTC'));
        if (!$this->expiresAt) {
            return false;
        }

        return $this->expiresAt < $time;
    }

    /**
     * @inheritDoc
     */
    public function withNeverExpire()
    {
        $new = clone $this;
        $new->expiresAt = new DateTimeImmutable('2038-01-01');

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withExpired()
    {
        $new = clone $this;
        $new->expiresAt = new DateTimeImmutable('1970-01-01 00:00:01');

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function getSameSite(): ?string
    {
        return $this->sameSite;
    }

    /**
     * @inheritDoc
     */
    public function withSameSite(?string $sameSite)
    {
        if ($sameSite !== null) {
            $this->validateSameSiteValue($sameSite);
        }

        $new = clone $this;
        $new->sameSite = $sameSite;

        return $new;
    }

    /**
     * Check that value passed for SameSite is valid.
     *
     * @param string $sameSite SameSite value
     * @return void
     * @throws \InvalidArgumentException
     */
    protected static function validateSameSiteValue(string $sameSite)
    {
        if (!in_array($sameSite, self::SAMESITE_VALUES, true)) {
            throw new InvalidArgumentException(
                'Samesite value must be either of: ' . implode(', ', self::SAMESITE_VALUES)
            );
        }
    }

    /**
     * Checks if a value exists in the cookie data.
     *
     * This method will expand serialized complex data,
     * on first use.
     *
     * @param string $path Path to check
     * @return bool
     */
    public function check(string $path): bool
    {
        if ($this->isExpanded === false) {
            /** @psalm-suppress PossiblyInvalidArgument */
            $this->value = $this->_expand($this->value);
        }

        /** @psalm-suppress PossiblyInvalidArgument */
        return Hash::check($this->value, $path);
    }

    /**
     * Create a new cookie with updated data.
     *
     * @param string $path Path to write to
     * @param mixed $value Value to write
     * @return static
     */
    public function withAddedValue(string $path, $value)
    {
        $new = clone $this;
        if ($new->isExpanded === false) {
            /** @psalm-suppress PossiblyInvalidArgument */
            $new->value = $new->_expand($new->value);
        }

        /** @psalm-suppress PossiblyInvalidArgument */
        $new->value = Hash::insert($new->value, $path, $value);

        return $new;
    }

    /**
     * Create a new cookie without a specific path
     *
     * @param string $path Path to remove
     * @return static
     */
    public function withoutAddedValue(string $path)
    {
        $new = clone $this;
        if ($new->isExpanded === false) {
            /** @psalm-suppress PossiblyInvalidArgument */
            $new->value = $new->_expand($new->value);
        }

        /** @psalm-suppress PossiblyInvalidArgument */
        $new->value = Hash::remove($new->value, $path);

        return $new;
    }

    /**
     * Read data from the cookie
     *
     * This method will expand serialized complex data,
     * on first use.
     *
     * @param string $path Path to read the data from
     * @return mixed
     */
    public function read(?string $path = null)
    {
        if ($this->isExpanded === false) {
            /** @psalm-suppress PossiblyInvalidArgument */
            $this->value = $this->_expand($this->value);
        }

        if ($path === null) {
            return $this->value;
        }

        /** @psalm-suppress PossiblyInvalidArgument */
        return Hash::get($this->value, $path);
    }

    /**
     * Checks if the cookie value was expanded
     *
     * @return bool
     */
    public function isExpanded(): bool
    {
        return $this->isExpanded;
    }

    /**
     * @inheritDoc
     */
    public function getOptions(): array
    {
        $options = [
            'expires' => (int)$this->getExpiresTimestamp(),
            'path' => $this->path,
            'domain' => $this->domain,
            'secure' => $this->secure,
            'httponly' => $this->httpOnly,
        ];

        if ($this->sameSite !== null) {
            $options['samesite'] = $this->sameSite;
        }

        return $options;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'value' => $this->getScalarValue(),
        ] + $this->getOptions();
    }

    /**
     * Implode method to keep keys are multidimensional arrays
     *
     * @param array $array Map of key and values
     * @return string A json encoded string.
     */
    protected function _flatten(array $array): string
    {
        return json_encode($array);
    }

    /**
     * Explode method to return array from string set in CookieComponent::_flatten()
     * Maintains reading backwards compatibility with 1.x CookieComponent::_flatten().
     *
     * @param string $string A string containing JSON encoded data, or a bare string.
     * @return string|array Map of key and values
     */
    protected function _expand(string $string)
    {
        $this->isExpanded = true;
        $first = substr($string, 0, 1);
        if ($first === '{' || $first === '[') {
            $ret = json_decode($string, true);

            return $ret ?? $string;
        }

        $array = [];
        foreach (explode(',', $string) as $pair) {
            $key = explode('|', $pair);
            if (!isset($key[1])) {
                return $key[0];
            }
            $array[$key[0]] = $key[1];
        }

        return $array;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toHeaderValue();
    }
}
