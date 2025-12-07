<?php

declare(strict_types=1);

namespace HP\Helmet\Middleware\Security\Helmet;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Promise\PromiseInterface;

use function React\Promise\resolve;

/**
 * @phpstan-type XPoweredByOptions array{serverValue?: string|null}
 */
final class XPoweredByMiddleware
{
    /** @var XPoweredByOptions */
    private array $options;

    /**
     * @param XPoweredByOptions $options
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
        if (\array_key_exists('serverValue', $this->options)) {
            $serverValue = $this->options['serverValue'];
        } else {
            $serverValue = 'secure';
        }

        return resolve($next($request))->then(
            static function (ResponseInterface $response) use ($serverValue): ResponseInterface {
                if ($response->hasHeader('X-Powered-By')) {
                    $response = $response->withoutHeader('X-Powered-By');
                }

                if ($serverValue === null) {
                    if ($response->hasHeader('Server')) {
                        $response = $response->withoutHeader('Server');
                    }

                    return $response;
                }

                return $response->withHeader('Server', $serverValue);
            }
        );
    }
}
