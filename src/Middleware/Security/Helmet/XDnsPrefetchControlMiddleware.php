<?php

declare(strict_types=1);

namespace HP\Helmet\Middleware\Security\Helmet;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Promise\PromiseInterface;

use function React\Promise\resolve;

/**
 * @phpstan-type DnsPrefetchOptions array{allow?: bool}
 */
final class XDnsPrefetchControlMiddleware
{
    /** @var DnsPrefetchOptions */
    private array $options;

    /**
     * @param DnsPrefetchOptions $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * @param ServerRequestInterface $request
     * @param callable(ServerRequestInterface): PromiseInterface<ResponseInterface> $next
     *
     * @return PromiseInterface<ResponseInterface>
     */
    public function __invoke(ServerRequestInterface $request, callable $next): PromiseInterface
    {
        $allow = (bool)($this->options['allow'] ?? false);
        $value = $allow ? 'on' : 'off';

        return resolve($next($request))->then(
            static function (ResponseInterface $response) use ($value): ResponseInterface {
                return $response->withHeader('X-DNS-Prefetch-Control', $value);
            }
        );
    }
}
