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

//https://github.com/narrowspark/framework/blob/81f39d7371715ee20aa888a8934c36c536e3d69e/src/Viserio/Component/Cookie/Middleware/AddQueuedCookiesToResponseMiddleware.php

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
        // TODO : préinitialiser le path / domain / secure value via les infos de la request !!!!!
        // Collection used to aggregates user cookies.
        /*
        $collection = new CookieQueue(
            $this->config->resolveDomain($request->getUri()),
            $request->getUri()->getScheme() === 'https'
        );*/


        //https://github.com/spiral/framework/blob/master/src/Cookies/src/Middleware/CookiesMiddleware.php#L54

        // Collection used to aggregates user cookies.
        $cookies = new CookieCollection();
        // The following controller could populate the cookies collection.
        $response = $handler->handle($request->withAttribute(CookieCollection::ATTRIBUTE, $cookies)); // TODO : utiliser plutot une constante ATTRIBUTE directement dans cette classe de middleware ????

        return $this->collectCookies($response, $cookies); // TODO : renommer la méthode en aggregateCookies() ou injectCookies()
    }

    /**
     * Collect outcoming cookies.
     *
     * @param Response         $response
     * @param CookieCollection $cookies
     *
     * @return ResponseInterface
    */
    // TODO : renommer la méthode en aggregateCookies() ou injectCookies() ou addCookiesToResponse()
    private function collectCookies(ResponseInterface $response, CookieCollection $cookies): ResponseInterface
    {
        foreach ($cookies as $cookie) {
            $header = $cookie->toHeaderValue();
            // Add the collected cookies to the response.
            $response = $response->withAddedHeader('Set-Cookie', $header); // TODO : utiliser un cast (string) cookie pour récupérer le header et simplifier le code.
        }

        return $response;
    }

    // TODO : code à virer !!!!
    private function collectCookies_OLD(ResponseInterface $response, CookieCollection $cookies): ResponseInterface
    {
        // TODO : voir si on garde ce if !!!! éventuellement vérifier ce qui se passe si on a un Set-Cookie avec une valeur égale à un tableau vide, il ne faudrait pas faire un emit de ce header !!! Eventuellement remonter ce if dans la méthode process() et remplacer le test du empty par un test sur le count === 0.
        if (count($cookies) === 0) {
            return $response;
        }

        $header = [];
        foreach ($cookies as $cookie) {
            $header[] = $cookie->toHeaderValue();
        }

        return $response->withHeader('Set-Cookie', $header);
    }
}
