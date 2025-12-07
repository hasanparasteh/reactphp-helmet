<?php

declare(strict_types=1);

namespace HP\Helmet\Middleware\Security\Helmet;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Promise\PromiseInterface;

use function React\Promise\resolve;

final class XDownloadOptionsMiddleware
{
    /**
     * @param ServerRequestInterface $request
     * @param callable(ServerRequestInterface): PromiseInterface<ResponseInterface> $next
     *
     * @return PromiseInterface<ResponseInterface>
     */
    public function __invoke(ServerRequestInterface $request, callable $next): PromiseInterface
    {
        return resolve($next($request))->then(
            static function (ResponseInterface $response): ResponseInterface {
                return $response->withHeader('X-Download-Options', 'noopen');
            }
        );
    }
}
