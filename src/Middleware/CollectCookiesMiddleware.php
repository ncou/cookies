<?php

declare(strict_types=1);

namespace Chiron\Cookies\Middleware;

use Chiron\CryptEngine;
use Chiron\Http\Cookie\CookiesManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Chiron\Cookies\CookieCollection;

// TODO : il faudra utiliser la clés qui est stockée dans APP_KEY et surtout utiliser la fonction hex2bin pour décoder cette chaine de caractére et l'utiliser comme une clés de bytes. Il faudra donc vérifier que la clés de byte fait bien 32 bytes une fois décodée via hex2bin et surtout pour utiliser hex2bin il faut vérifier que la chaine est bien de type hexa et que la longueur est un multiple de 2 (cad "even") car sinon on aura un warning dans la méthode hex2bin et elle retournera false au lien de décoder la chaine.
//=> https://stackoverflow.com/questions/41194159/how-to-catch-hex2bin-warning

//https://github.com/cakephp/cakephp/blob/42353085a8911745090024e2a4f43215d38d6af0/src/Http/Middleware/EncryptedCookieMiddleware.php#L170

//https://github.com/cakephp/cakephp/blob/42353085a8911745090024e2a4f43215d38d6af0/src/Utility/CookieCryptTrait.php#L53

final class CollectCookiesMiddleware implements MiddlewareInterface
{
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
        // Collection used to aggregates user cookies.
        /*
        $collection = new CookieCollection(
            $this->config->resolveDomain($request->getUri()),
            $request->getUri()->getScheme() === 'https'
        );*/

        // Collection used to aggregates user cookies.
        $cookies = new CookieCollection();

        $response = $handler->handle($request->withAttribute(CookieCollection::ATTRIBUTE, $cookies));

        return $this->collectCookies($response, $cookies);
    }

    /**
     * Collect outcoming cookies.
     *
     * @param Response         $response
     * @param CookieCollection $cookies
     *
     * @return ResponseInterface
    */
    private function collectCookies(ResponseInterface $response, CookieCollection $cookies): ResponseInterface
    {
        // TODO : voir si on garde ce if !!!! éventuellement vérifier ce qui se passe si on a un Set-Cookie avec une valeur égale à un tableau vide, il ne faudrait pas faire un emit de ce header !!! Eventuellement remonter ce if dans la méthode process() et remplacer le test du empty par un test sur le count === 0.
        if (empty($cookies)) {
            return $response;
        }

        $header = [];
        foreach ($cookies as $cookie) {
            $header[] = $cookie->toHeaderValue();
        }

        return $response->withHeader('Set-Cookie', $header);
    }
}
