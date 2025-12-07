<?php

declare(strict_types=1);

namespace HP\Helmet\Middleware\Security\Helmet;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Promise\PromiseInterface;

use function React\Promise\resolve;

/**
 * @phpstan-type PolicyOptions array{policy?: string|null}
 */
final class CrossOriginEmbedderPolicyMiddleware
{
    /** @var PolicyOptions */
    private array $options;

    /**
     * @param PolicyOptions $options
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
        if (\array_key_exists('policy', $this->options)) {
            $policy = $this->options['policy'];
        } else {
            $policy = 'require-corp';
        }

        return resolve($next($request))->then(
            static function (ResponseInterface $response) use ($policy): ResponseInterface {
                if ($policy === null) {
                    return $response;
                }

                return $response->withHeader('Cross-Origin-Embedder-Policy', $policy);
            }
        );
    }
}
