<?php

declare(strict_types=1);

namespace HP\Helmet\Middleware\Security\Helmet;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Promise\PromiseInterface;

use function React\Promise\reject;
use function React\Promise\resolve;

/**
 * (HelmetOptions phpstan types omitted here for brevity – keep what you already have)
 *
 * @phpstan-type Middleware callable(ServerRequestInterface, callable(ServerRequestInterface): PromiseInterface<mixed>): PromiseInterface<mixed>
 */
final class HelmetMiddleware
{

    /** @var list<Middleware> */
    private array $middlewares;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(array $options = [])
    {
        $this->middlewares = $this->buildMiddlewareStackFromOptions($options);
    }

    /**
     * @param ServerRequestInterface $request
     * @param callable $next
     * @return PromiseInterface<mixed>
     */
    public function __invoke(ServerRequestInterface $request, callable $next): PromiseInterface
    {
        try {
            /** @var callable(ServerRequestInterface): PromiseInterface<mixed> $pipeline */
            $pipeline = \array_reduce(
                \array_reverse($this->middlewares),
                static function (callable $nextCallable, callable $current): callable {
                    return static function (ServerRequestInterface $request) use ($current, $nextCallable): PromiseInterface {
                        return $current($request, $nextCallable);
                    };
                },
                static function (ServerRequestInterface $request) use ($next): PromiseInterface {
                    $result = $next($request);

                    if ($result instanceof PromiseInterface) {
                        return $result;
                    }

                    if (!$result instanceof ResponseInterface) {
                        throw new \TypeError(
                            'Next middleware must return a ResponseInterface or PromiseInterface'
                        );
                    }

                    return resolve($result);
                }
            );

            return $pipeline($request);
        } catch (\Throwable $e) {
            return reject($e);
        }
    }


    /**
     * @param array<string, mixed> $options
     * @return list<Middleware>
     */
    private function buildMiddlewareStackFromOptions(array $options): array
    {
        $result = [];

        // contentSecurityPolicy
        $cspOption = $options['contentSecurityPolicy'] ?? true;
        if ($cspOption !== false) {
            $cspConfig = \is_array($cspOption) ? $cspOption : [];
            $result[] = new ContentSecurityPolicyMiddleware($cspConfig);
        }

        // crossOriginEmbedderPolicy
        $coepOption = $options['crossOriginEmbedderPolicy'] ?? false;
        if ($coepOption) {
            $coepConfig = \is_array($coepOption) ? $coepOption : [];
            $result[] = new CrossOriginEmbedderPolicyMiddleware($coepConfig);
        }

        // crossOriginOpenerPolicy
        $coopOption = $options['crossOriginOpenerPolicy'] ?? true;
        if ($coopOption !== false) {
            $coopConfig = \is_array($coopOption) ? $coopOption : [];
            $result[] = new CrossOriginOpenerPolicyMiddleware($coopConfig);
        }

        // crossOriginResourcePolicy
        $corpOption = $options['crossOriginResourcePolicy'] ?? true;
        if ($corpOption !== false) {
            $corpConfig = \is_array($corpOption) ? $corpOption : [];
            $result[] = new CrossOriginResourcePolicyMiddleware($corpConfig);
        }

        // originAgentCluster
        $oacOption = $options['originAgentCluster'] ?? true;
        if ($oacOption !== false) {
            $result[] = new OriginAgentClusterMiddleware();
        }

        // referrerPolicy
        $referrerOption = $options['referrerPolicy'] ?? true;
        if ($referrerOption !== false) {
            $refConfig = \is_array($referrerOption) ? $referrerOption : [];
            $result[] = new ReferrerPolicyMiddleware($refConfig);
        }

        // strictTransportSecurity / hsts
        if (\array_key_exists('strictTransportSecurity', $options) && \array_key_exists('hsts', $options)) {
            throw new InvalidArgumentException(
                'Strict-Transport-Security option specified twice; remove either `hsts` or `strictTransportSecurity`.'
            );
        }

        $hstsOption = $options['strictTransportSecurity'] ?? $options['hsts'] ?? true;
        if ($hstsOption !== false) {
            $hstsConfig = \is_array($hstsOption) ? $hstsOption : [];
            $result[] = new StrictTransportSecurityMiddleware($hstsConfig);
        }

        // xContentTypeOptions / noSniff
        if (\array_key_exists('xContentTypeOptions', $options) && \array_key_exists('noSniff', $options)) {
            throw new InvalidArgumentException(
                'X-Content-Type-Options specified twice; remove either `noSniff` or `xContentTypeOptions`.'
            );
        }
        $xContentOption = $options['xContentTypeOptions'] ?? $options['noSniff'] ?? true;
        if ($xContentOption !== false) {
            $result[] = new XContentTypeOptionsMiddleware();
        }

        // xDnsPrefetchControl / dnsPrefetchControl
        if (\array_key_exists('xDnsPrefetchControl', $options) && \array_key_exists('dnsPrefetchControl', $options)) {
            throw new InvalidArgumentException(
                'X-DNS-Prefetch-Control specified twice; remove either `dnsPrefetchControl` or `xDnsPrefetchControl`.'
            );
        }

        $dnsOption = $options['xDnsPrefetchControl'] ?? $options['dnsPrefetchControl'] ?? true;
        if ($dnsOption !== false) {
            $dnsConfig = \is_array($dnsOption) ? $dnsOption : [];
            $result[] = new XDnsPrefetchControlMiddleware($dnsConfig);
        }

        // xDownloadOptions / ieNoOpen
        if (\array_key_exists('xDownloadOptions', $options) && \array_key_exists('ieNoOpen', $options)) {
            throw new InvalidArgumentException(
                'X-Download-Options specified twice; remove either `ieNoOpen` or `xDownloadOptions`.'
            );
        }
        $downloadOption = $options['xDownloadOptions'] ?? $options['ieNoOpen'] ?? true;
        if ($downloadOption !== false) {
            $result[] = new XDownloadOptionsMiddleware();
        }

        // xFrameOptions / frameguard
        if (\array_key_exists('xFrameOptions', $options) && \array_key_exists('frameguard', $options)) {
            throw new InvalidArgumentException(
                'X-Frame-Options specified twice; remove either `frameguard` or `xFrameOptions`.'
            );
        }

        $frameOption = $options['xFrameOptions'] ?? $options['frameguard'] ?? true;
        if ($frameOption !== false) {
            $frameConfig = \is_array($frameOption) ? $frameOption : [];
            $result[] = new XFrameOptionsMiddleware($frameConfig);
        }

        // xPermittedCrossDomainPolicies / permittedCrossDomainPolicies
        if (\array_key_exists('xPermittedCrossDomainPolicies', $options)
            && \array_key_exists('permittedCrossDomainPolicies', $options)
        ) {
            throw new InvalidArgumentException(
                'X-Permitted-Cross-Domain-Policies specified twice; remove either `permittedCrossDomainPolicies` or `xPermittedCrossDomainPolicies`.'
            );
        }

        $permittedOption =
            $options['xPermittedCrossDomainPolicies'] ?? $options['permittedCrossDomainPolicies'] ?? true;
        if ($permittedOption !== false) {
            $permConfig = \is_array($permittedOption) ? $permittedOption : [];
            $result[] = new XPermittedCrossDomainPoliciesMiddleware($permConfig);
        }

        // xPoweredBy / hidePoweredBy
        if (\array_key_exists('xPoweredBy', $options) && \array_key_exists('hidePoweredBy', $options)) {
            throw new InvalidArgumentException(
                'X-Powered-By option specified twice; remove either `hidePoweredBy` or `xPoweredBy`.'
            );
        }

        $poweredOption = $options['xPoweredBy'] ?? $options['hidePoweredBy'] ?? true;
        if ($poweredOption !== false) {
            $poweredConfig = \is_array($poweredOption) ? $poweredOption : [];
            $result[] = new XPoweredByMiddleware($poweredConfig);
        }

        // xXssProtection / xssFilter
        if (\array_key_exists('xXssProtection', $options) && \array_key_exists('xssFilter', $options)) {
            throw new InvalidArgumentException(
                'X-XSS-Protection specified twice; remove either `xssFilter` or `xXssProtection`.'
            );
        }
        $xssOption = $options['xXssProtection'] ?? $options['xssFilter'] ?? true;
        if ($xssOption !== false) {
            $result[] = new XXssProtectionMiddleware();
        }

        return $result;
    }
}
