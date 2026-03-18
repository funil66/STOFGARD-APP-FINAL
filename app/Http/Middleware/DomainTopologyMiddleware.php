<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DomainTopologyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower($request->getHost());
        $baseDomain = strtolower((string) config('domain_routing.base_domain', 'autonomia.app.br'));
        $providerSubdomain = strtolower((string) config('domain_routing.provider_subdomain', 'app'));
        $providerHost = $providerSubdomain . '.' . $baseDomain;

        $isProviderHost = $host === $providerHost;
        $isWildcardClientHost = str_ends_with($host, '.' . $baseDomain) && !$isProviderHost;

        if ($isProviderHost) {
            $request->attributes->set('domain_area', 'provider_dashboard');

            return $next($request);
        }

        if ($isWildcardClientHost) {
            $request->attributes->set('domain_area', 'client_showcase');

            if ($this->isAuthPath($request)) {
                return redirect()->to($this->buildProviderUrl($request, $providerHost), 301);
            }

            return $next($request);
        }

        $request->attributes->set('domain_area', 'legacy_or_external');

        return $next($request);
    }

    private function isAuthPath(Request $request): bool
    {
        $path = '/' . ltrim($request->path(), '/');

        $authPaths = [
            '/login',
            '/register',
            '/admin/login',
            '/api/auth/login',
            '/api/auth/register',
        ];

        return in_array($path, $authPaths, true);
    }

    private function buildProviderUrl(Request $request, string $providerHost): string
    {
        $scheme = (string) config('domain_routing.provider_scheme', 'https');
        $path = '/' . ltrim($request->path(), '/');
        $query = $request->getQueryString();

        $url = $scheme . '://' . $providerHost . $path;

        if ($query) {
            $url .= '?' . $query;
        }

        return $url;
    }
}
