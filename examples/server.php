<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use HP\Helmet\Http\MiddlewareDispatcher;
use HP\Helmet\Middleware\Security\Helmet\HelmetMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Loop;
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;

$loop = Loop::get();

$helmet = new HelmetMiddleware([
    'contentSecurityPolicy' => [
        'directives' => [
            'default-src' => ["'self'"],
            'script-src'  => ["'self'", 'https://cdn.example.com'],
        ],
    ],
    'referrerPolicy' => ['policy' => 'no-referrer'],
    'xPoweredBy'     => ['serverValue' => 'hp-helmet'],
]);

$finalHandler = static function (ServerRequestInterface $request): Response {
    return new Response(
        200,
        ['Content-Type' => 'text/plain'],
        "Hello from HP Helmet\n"
    );
};

$dispatcher = new MiddlewareDispatcher(
    [
        $helmet,
    ],
    $finalHandler
);

$server = new HttpServer($loop, $dispatcher);

$socket = new SocketServer('0.0.0.0:8080', [], $loop);
$server->listen($socket);

echo "Server running at http://0.0.0.0:8080\n";

$loop->run();
