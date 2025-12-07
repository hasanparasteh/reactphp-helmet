<?php

declare(strict_types=1);

namespace HP\Helmet\Middleware\Security\Helmet;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Promise\PromiseInterface;

use function React\Promise\resolve;

/**
 * @phpstan-type HstsOptions array{
 *     maxAge?: int,
 *     includeSubDomains?: bool,
 *     preload?: bool
 * }
 */
final class StrictTransportSecurityMiddleware
{
    /** @var HstsOptions */
    private array $options;

    /**
     * @param HstsOptions $options
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
        $maxAge           = (int)($this->options['maxAge'] ?? 15552000); // 180 days
        $includeSubDomain = (bool)($this->options['includeSubDomains'] ?? true);
        $preload          = (bool)($this->options['preload'] ?? false);

        $parts = ["max-age={$maxAge}"];

        if ($includeSubDomain) {
            $parts[] = 'includeSubDomains';
        }

        if ($preload) {
            $parts[] = 'preload';
        }

        $value = \implode('; ', $parts);

        return resolve($next($request))->then(
            static function (ResponseInterface $response) use ($value): ResponseInterface {
                return $response->withHeader('Strict-Transport-Security', $value);
            }
        );
    }
}
