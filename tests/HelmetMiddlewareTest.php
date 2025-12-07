<?php

declare(strict_types=1);

namespace HP\Helmet\Tests;

use HP\Helmet\Http\MiddlewareDispatcher;
use HP\Helmet\Middleware\Security\Helmet\HelmetMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use React\Http\Message\Response;
use React\Promise\PromiseInterface;

use function React\Promise\resolve;

final class HelmetMiddlewareTest extends TestCase
{
    public function testHelmetAddsSecurityHeaders(): void
    {
        $helmet = new HelmetMiddleware();

        $psr17 = new Psr17Factory();
        $request = $psr17->createServerRequest('GET', 'https://example.com/');

        $final = static fn() => new Response(200, [], 'ok');

        $dispatcher = new MiddlewareDispatcher([$helmet], $final);

        /** @var PromiseInterface $promise */
        $promise = $dispatcher($request);

        $response = null;
        $promise->then(static function ($res) use (&$response): void {
            $response = $res;
        });

        // resolve the promise (synchronously ok here because there's no async I/O)
        resolve(null);

        self::assertNotNull($response);
        self::assertTrue($response->hasHeader('Content-Security-Policy'));
        self::assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
    }
}
