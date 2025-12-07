<?php

declare(strict_types=1);

namespace HP\Helmet\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Promise\PromiseInterface;

use function React\Promise\resolve;

/**
 * @phpstan-type Middleware callable(ServerRequestInterface, callable(ServerRequestInterface): PromiseInterface<mixed>): PromiseInterface<mixed>
 */
final class MiddlewareDispatcher
{
    /** @var list<Middleware> */
    private array $middlewares;

    /** @var callable */
    private $finalHandler;

    /**
     * @param list<Middleware> $middlewares
     * @param callable(ServerRequestInterface): mixed $finalHandler
     */
    public function __construct(array $middlewares, callable $finalHandler)
    {
        $this->middlewares  = $middlewares;
        $this->finalHandler = $finalHandler;
    }

    /**
     * @return PromiseInterface<mixed>
     */
    public function __invoke(ServerRequestInterface $request): PromiseInterface
    {
        $finalHandler = $this->finalHandler;

        $pipeline = \array_reduce(
            \array_reverse($this->middlewares),
            /**
             * @param callable(ServerRequestInterface): PromiseInterface<mixed> $next
             * @param Middleware                                                 $current
             * @return callable(ServerRequestInterface): PromiseInterface<mixed>
             */
            static function (callable $next, callable $current): callable {
                return static function (ServerRequestInterface $request) use ($current, $next): PromiseInterface {
                    return $current($request, $next);
                };
            },
            /**
             * @return PromiseInterface<mixed>
             */
            static function (ServerRequestInterface $request) use ($finalHandler): PromiseInterface {
                $result = $finalHandler($request);

                if ($result instanceof PromiseInterface) {
                    return $result;
                }

                if (!$result instanceof ResponseInterface) {
                    throw new \TypeError(
                        'Final handler must return a ResponseInterface or PromiseInterface'
                    );
                }

                return resolve($result);
            }
        );

        return $pipeline($request);
    }
}
