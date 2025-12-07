<?php

declare(strict_types=1);

namespace HP\Helmet\Middleware\Security\Helmet;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Promise\PromiseInterface;

use function React\Promise\resolve;

final class ContentSecurityPolicyMiddleware
{
    /** @var array<string, mixed> */
    private array $options;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * @return PromiseInterface<mixed>
     */
    public function __invoke(ServerRequestInterface $request, callable $next): PromiseInterface
    {
        /** @var array<string, array<int, string>|string|null> $directives */
        $directives = $this->options['directives'] ?? $this->getDefaultCspDirectives();
        $reportOnly = (bool)($this->options['reportOnly'] ?? false);

        $parts = [];

        foreach ($directives as $name => $value) {
            if ($value === null) {
                continue;
            }

            $iterable = \is_iterable($value) ? $value : [$value];

            $vals = [];
            foreach ($iterable as $v) {
                $vals[] = (string)$v;
            }

            if ($vals === []) {
                $parts[] = $name;
            } else {
                $parts[] = $name . ' ' . \implode(' ', $vals);
            }
        }

        $headerName  = $reportOnly ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy';
        $headerValue = \implode(';', $parts);

        return resolve($next($request))->then(
        /**
         * @param mixed $response
         */
            static function ($response) use ($headerName, $headerValue): ResponseInterface {
                if (!$response instanceof ResponseInterface) {
                    throw new \TypeError('Expected ResponseInterface in CSP middleware.');
                }

                if ($headerValue === '') {
                    return $response;
                }

                return $response->withHeader($headerName, $headerValue);
            }
        );
    }

    /**
     * @return array<string, array<int, string>|null>
     */
    private function getDefaultCspDirectives(): array
    {
        return [
            'default-src'               => ["'self'"],
            'base-uri'                  => ["'self'"],
            'font-src'                  => ["'self'", 'https:', 'data:'],
            'form-action'               => ["'self'"],
            'frame-ancestors'           => ["'self'"],
            'img-src'                   => ["'self'", 'data:'],
            'object-src'                => ["'none'"],
            'script-src'                => ["'self'"],
            'script-src-attr'           => ["'none'"],
            'style-src'                 => ["'self'", 'https:', "'unsafe-inline'"],
            'upgrade-insecure-requests' => [],
        ];
    }
}
