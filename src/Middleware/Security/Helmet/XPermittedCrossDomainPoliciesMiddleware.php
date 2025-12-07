<?php

declare(strict_types=1);

namespace HP\Helmet\Middleware\Security\Helmet;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Promise\PromiseInterface;

use function React\Promise\resolve;

/**
 * @phpstan-type PermittedPolicies array{policy?: string}
 */
final class XPermittedCrossDomainPoliciesMiddleware
{
    /** @var PermittedPolicies */
    private array $options;

    /**
     * @param PermittedPolicies $options
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
        $policy = $this->options['policy'] ?? 'none';

        return resolve($next($request))->then(
            static function (ResponseInterface $response) use ($policy): ResponseInterface {
                return $response->withHeader('X-Permitted-Cross-Domain-Policies', $policy);
            }
        );
    }
}
