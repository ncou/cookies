<?php

declare(strict_types=1);

namespace Chiron\Cookies\Middleware;

use Chiron\CryptEngine;
use Chiron\Http\Cookie\CookiesManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Chiron\Cookies\Config\CookiesConfig;

use Chiron\Cookies\CookieCollection;
use Chiron\Encrypter\Encrypter;

// TODO : il faudra utiliser la clés qui est stockée dans APP_KEY et surtout utiliser la fonction hex2bin pour décoder cette chaine de caractére et l'utiliser comme une clés de bytes. Il faudra donc vérifier que la clés de byte fait bien 32 bytes une fois décodée via hex2bin et surtout pour utiliser hex2bin il faut vérifier que la chaine est bien de type hexa et que la longueur est un multiple de 2 (cad "even") car sinon on aura un warning dans la méthode hex2bin et elle retournera false au lien de décoder la chaine.
//=> https://stackoverflow.com/questions/41194159/how-to-catch-hex2bin-warning

//https://github.com/cakephp/cakephp/blob/42353085a8911745090024e2a4f43215d38d6af0/src/Http/Middleware/EncryptedCookieMiddleware.php#L170

//https://github.com/cakephp/cakephp/blob/42353085a8911745090024e2a4f43215d38d6af0/src/Utility/CookieCryptTrait.php#L53

//https://github.com/narrowspark/framework/blob/81f39d7371715ee20aa888a8934c36c536e3d69e/src/Viserio/Component/Cookie/Middleware/EncryptedCookiesMiddleware.php

final class EncryptCookiesMiddleware implements MiddlewareInterface
{
    /**
     * The names of the cookies which bypass encryption.
     *
     * @var array
     */
    private $bypassed;

    private $encrypter;

    /**
     * Set up a encrypt cookie middleware with the given password key and an array of bypassed cookie names.
     *
     * @param CookiesConfig $config
     * @param Encrypter $encrypter
     */
    // TODO : passer plutot en paramétre de cette classe un CookiesConfig qui se charge d'initialiser les cookies à bypasser + dire si l'encryption est active + eventuellement le domain !!!!
    public function __construct(Encrypter $encrypter, CookiesConfig $config)
    {
        $this->encrypter = $encrypter;
        $this->bypassed = $config->getExcluded();
    }

    /**
     * Start the session, delegate the request processing and add the session cookie to the response.
     *
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $this->withDecryptedCookies($request);
        $response = $handler->handle($request);

        return $this->withEncryptedCookies($response);
    }

    /**
     * Decrypt the non bypassed cookie values attached to the given request and return a new request with those values.
     *
     * @param ServerRequestInterface $request
     *
     * @return ServerRequestInterface
     */
    private function withDecryptedCookies(ServerRequestInterface $request): ServerRequestInterface
    {
        $cookies = $request->getCookieParams();

        // TODO : réutiliser le tableau des cookies au lieu d'initialiser un tableau $decrypted = [] !!!!!!!!!!!
        $decrypted = [];
        foreach ($cookies as $name => $value) {
            // TODO : faire plutot un "continue" si le cookie est dans la liste $bypassed pour simplifier le if. + créer une méthode isBypassed() pour simplifier le code et faire le test du in_array
            $decrypted[$name] = in_array($name, $this->bypassed) ? $value : $this->decrypt($value);
        }

        return $request->withCookieParams($decrypted);
    }

    /**
     * Encode cookies from a response's Set-Cookie header.
     *
     * @param ResponseInterface $response The response to encode cookies in.
     *
     * @return ResponseInterface Updated response with encoded cookies.
     */
    private function withEncryptedCookies(ResponseInterface $response): ResponseInterface
    {
        if (! $response->hasHeader('Set-Cookie')) {
            return $response;
        }

        $cookies = CookieCollection::createFromHeader($response->getHeader('Set-Cookie'));
        $header = [];
        foreach ($cookies as $cookie) {
            // TODO : faire plutot un "continue" si le cookie est dans la liste $bypassed pour simplifier le if. + créer une méthode isBypassed() pour simplifier le code et faire le test du in_array
            if (! in_array($cookie->getName(), $this->bypassed)) {
                $value = $this->encrypt($cookie->getValue());
                $cookie = $cookie->withValue($value);
            }

            $header[] = $cookie->toHeaderValue();
        }

        return $response->withHeader('Set-Cookie', $header);



/*

        $cookies = CookiesManager::parseSetCookieHeader($response->getHeader('Set-Cookie'));

        // remove all the cookies
        $response = $response->withoutHeader('Set-Cookie');

        //$header = [];
        foreach ($cookies as $name => $cookie) {
            if (! in_array($name, $this->bypassed)) {
                $cookie['value'] = $this->encrypt($cookie['value']);
            }

            //$cookiesManager->set($name, $value);
            // add again all the cookies (and some are now encrypted)
            $response = $response->withAddedHeader('Set-Cookie', CookiesManager::toHeader($name, $cookie));
        }

        return $response;

        */

    }

    /**
     * Encrypt the given value using the key.
     *
     * @param string $value
     *
     * @return string
     */
    private function encrypt(string $value): string
    {
        return $this->encrypter->encrypt($value);
    }

    /**
     * Decrypt the given value using the key.
     * Return default to blank string when the key is wrong or the cypher text has been modified.
     *
     * @param string $value
     *
     * @return string
     */
    // TODO : il faudrait surement gérer le cas ou la valeur est un tableau :
    //https://github.com/spiral/framework/blob/master/src/Cookies/src/Middleware/CookiesMiddleware.php#L136
    //https://github.com/cakephp/cakephp/blob/42353085a8911745090024e2a4f43215d38d6af0/src/Utility/CookieCryptTrait.php#L100
    //
    private function decrypt(string $value): string
    {
        try {
            return $this->encrypter->decrypt($value);
        } catch (\Throwable $t) {
            // @TODO : Add a silent log message if there is an error in the cookie decrypt function.
            return '';
        }
    }
}
