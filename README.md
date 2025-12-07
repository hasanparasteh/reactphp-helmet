# Helmet – Security Headers for ReactPHP

A fully modular, ReactPHP-native re-implementation of **Helmet.js**.
Each security feature is implemented as a separate middleware class, and `HelmetMiddleware` acts as
the aggregator—just like the real Helmet.

## ✨ Features

* CSP (Content Security Policy)
* Cross-Origin Policies (COOP / COEP / CORP)
* Strict-Transport-Security (HSTS)
* Referrer-Policy
* X-Frame-Options
* X-Content-Type-Options
* X-DNS-Prefetch-Control
* X-Download-Options
* X-Permitted-Cross-Domain-Policies
* X-Powered-By removal
* X-XSS-Protection (disabled by default, following Helmet.js)
* All middleware is **async**, **non-blocking**, and designed for **ReactPHP HTTP servers**

# 📦 Installation

```
composer require hp/helmet
```

# 🚀 Usage with ReactPHP

```php
use HP\Helmet\Middleware\Security\Helmet\HelmetMiddleware;
use HP\Helmet\Http\MiddlewareDispatcher;
use React\Http\HttpServer;
use React\Http\Message\Response;

$helmet = new HelmetMiddleware([
    'contentSecurityPolicy' => [
        'directives' => [
            "default-src" => ["'self'"],
            "script-src"  => ["'self'", "https://cdn.example.com"],
        ]
    ],
    'referrerPolicy' => ['policy' => 'no-referrer'],
    'xPoweredBy' => true
]);

$dispatcher = new MiddlewareDispatcher(
    [$helmet],
    fn() => new Response(200, ['Content-Type' => 'text/plain'], "Hello secure world")
);

$server = new HttpServer($dispatcher);
```

# ⚙️ Configuration Options (Full Documentation)

Configuration follows Helmet.js semantics as closely as possible.

## 1. `contentSecurityPolicy`

Enable or configure CSP.

### Example

```php
'contentSecurityPolicy' => [
    'directives' => [
        "default-src" => ["'self'"],
        "script-src" => ["'self'", "cdn.example.com"],
    ],
    'reportOnly' => false
]
```

### Options

| Key          | Type               | Default | Description                                                     |
|--------------|--------------------|---------|-----------------------------------------------------------------|
| `directives` | array<string,array | string  | null>                                                           | Helmet defaults | CSP rule set                                                    |
| `reportOnly` | bool               | false   | Sets `Content-Security-Policy-Report-Only` instead of enforcing |

### Default CSP Directives

```
default-src 'self';
base-uri 'self';
font-src 'self' https: data:;
form-action 'self';
frame-ancestors 'self';
img-src 'self' data:;
object-src 'none';
script-src 'self';
script-src-attr 'none';
style-src 'self' https: 'unsafe-inline';
upgrade-insecure-requests;
```

## 2. `crossOriginEmbedderPolicy`

Controls resource isolation (COEP).

### Example

```php
'crossOriginEmbedderPolicy' => [
    'policy' => 'require-corp'
]
```

### Options

| Key      | Type   | Default |
|----------|--------|---------|
| `policy` | string | null    | `"require-corp"` |

Produces:

```
Cross-Origin-Embedder-Policy: require-corp
```

## 3. `crossOriginOpenerPolicy`

Isolation protection (COOP).

### Example

```php
'crossOriginOpenerPolicy' => [
    'policy' => 'same-origin'
]
```

### Options

| Key      | Type   | Default |
|----------|--------|---------|
| `policy` | string | null    | `"same-origin"` |

Produces:

```
Cross-Origin-Opener-Policy: same-origin
```

## 4. `crossOriginResourcePolicy`

Restrict which origins can load your resources (CORP).

### Example

```php
'crossOriginResourcePolicy' => [
    'policy' => 'same-origin'
]
```

### Options

| Key      | Type   | Default |
|----------|--------|---------|
| `policy` | string | null    | `"same-origin"` |

## 5. `originAgentCluster`

Enables browser origin-keyed agent clusters.

### Example

```php
'originAgentCluster' => true
```

Produces:

```
Origin-Agent-Cluster: ?1
```

## 6. `referrerPolicy`

### Example

```php
'referrerPolicy' => [
    'policy' => 'no-referrer'
]
```

### Options

| Key      | Type   | Default |
|----------|--------|---------|
| `policy` | string | null    | `"no-referrer"` |

## 7. `strictTransportSecurity` / `hsts`

HSTS config.

Example:

```php
'strictTransportSecurity' => [
    'maxAge' => 31536000,
    'includeSubDomains' => true,
    'preload' => false
]
```

Options:

| Key                 | Type | Default               |
|---------------------|------|-----------------------|
| `maxAge`            | int  | `15552000` (180 days) |
| `includeSubDomains` | bool | `true`                |
| `preload`           | bool | `false`               |

Produces:

```
Strict-Transport-Security: max-age=15552000; includeSubDomains
```

Aliases:

* `hsts`
* `strictTransportSecurity`
  (Only one allowed—both → error)

## 8. `xContentTypeOptions` / `noSniff`

Control MIME type sniffing.

Examples:

```php
'xContentTypeOptions' => true
// or
'noSniff' => true
```

Output:

```
X-Content-Type-Options: nosniff
```

Alias rules:

* Only **one** of `xContentTypeOptions` or `noSniff` allowed.

## 9. `xDnsPrefetchControl` / `dnsPrefetchControl`

Example:

```php
'dnsPrefetchControl' => ['allow' => false]
```

Options:

| Key     | Type | Default |
|---------|------|---------|
| `allow` | bool | `false` |

Output:

```
X-DNS-Prefetch-Control: off
```

## 10. `xDownloadOptions` / `ieNoOpen`

Prevents file download attacks in IE.

Enable:

```php
'xDownloadOptions' => true
```

Output:

```
X-Download-Options: noopen
```

## 11. `xFrameOptions` / `frameguard`

Example:

```php
'xFrameOptions' => [
    'action' => 'DENY'
]
```

Options:

| Key      | Type     | Default        |
|----------|----------|----------------|
| `action` | `"DENY"` | `"SAMEORIGIN"` | `"SAMEORIGIN"` |

Output:

```
X-Frame-Options: SAMEORIGIN
```

## 12. `xPermittedCrossDomainPolicies`

Example:

```php
'xPermittedCrossDomainPolicies' => [
    'policy' => 'none'
]
```

Options:

| Key      | Type   | Default  |
|----------|--------|----------|
| `policy` | string | `"none"` |

Output:

```
X-Permitted-Cross-Domain-Policies: none
```

## 13. `xPoweredBy` / `hidePoweredBy`

True = remove “X-Powered-By”.

Example:

```php
'xPoweredBy' => true
```

Removes:

```
X-Powered-By: PHP/8.x
```

If you **disable**:

```php
'xPoweredBy' => false
```

It will NOT remove the header.

## 14. `xXssProtection` / `xssFilter`

Modern Helmet disables this (it's deprecated/broken in browsers).

Example:

```php
'xXssProtection' => true
```

Always outputs:

```
X-XSS-Protection: 0
```

Alias rules same as Helmet.js.

# 🧩 Full Option Map

| Helmet.js Option               | HP Helmet Option              | Default             |
|--------------------------------|-------------------------------|---------------------|
| contentSecurityPolicy          | contentSecurityPolicy         | enabled             |
| crossOriginOpenerPolicy        | crossOriginOpenerPolicy       | enabled             |
| crossOriginEmbedderPolicy      | crossOriginEmbedderPolicy     | disabled            |
| crossOriginResourcePolicy      | crossOriginResourcePolicy     | enabled             |
| originAgentCluster             | originAgentCluster            | enabled             |
| referrerPolicy                 | referrerPolicy                | enabled             |
| strictTransportSecurity / hsts | strictTransportSecurity       | enabled             |
| noSniff                        | xContentTypeOptions           | enabled             |
| dnsPrefetchControl             | xDnsPrefetchControl           | enabled             |
| ieNoOpen                       | xDownloadOptions              | enabled             |
| frameguard                     | xFrameOptions                 | enabled             |
| permittedCrossDomainPolicies   | xPermittedCrossDomainPolicies | enabled             |
| hidePoweredBy                  | xPoweredBy                    | enabled             |
| xssFilter                      | xXssProtection                | enabled (sets to 0) |

# 🧱 Architecture Overview

```
HelmetMiddleware
   ↳ ContentSecurityPolicyMiddleware
   ↳ CrossOriginOpenerPolicyMiddleware
   ↳ CrossOriginEmbedderPolicyMiddleware
   ↳ CrossOriginResourcePolicyMiddleware
   ↳ OriginAgentClusterMiddleware
   ↳ ReferrerPolicyMiddleware
   ↳ StrictTransportSecurityMiddleware
   ↳ XContentTypeOptionsMiddleware
   ↳ XDnsPrefetchControlMiddleware
   ↳ XDownloadOptionsMiddleware
   ↳ XFrameOptionsMiddleware
   ↳ XPermittedCrossDomainPoliciesMiddleware
   ↳ XPoweredByMiddleware
   ↳ XXssProtectionMiddleware
```

Each sub-middleware:

* Accepts `(ServerRequestInterface $req, callable $next)`
* Returns `Promise<ResponseInterface>`
* Mutates headers only in the **response**


