<?php

declare(strict_types=1);

namespace HP\Helmet\Middleware\Security\Helmet;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Promise\PromiseInterface;

use function React\Promise\resolve;

/**
 * @phpstan-type FrameOptions array{action?: 'DENY'|'SAMEORIGIN'|string}
 */
final class XFrameOptionsMiddleware
{
    /** @var FrameOptions */
    private array $options;

    /**
     * @param FrameOptions $options
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
        $action = \strtoupper((string)($this->options['action'] ?? 'SAMEORIGIN'));

        $value = $action === 'DENY' ? 'DENY' : 'SAMEORIGIN';

        return resolve($next($request))->then(
            static function (ResponseInterface $response) use ($value): ResponseInterface {
                return $response->withHeader('X-Frame-Options', $value);
            }
        );
    }
}
