<?php
// ARQUIVO: app/Http/Middleware/DomainTopologyMiddleware.php
// DESCRIÇÃO: Barreira de fogo para roteamento Multi-Tenant.

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DomainTopologyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $baseDomain = (string) config('domain_routing.base_domain', 'autonomia.app.br');
        $providerSubdomain = (string) config('domain_routing.provider_subdomain', 'app');
        $appDomain = $providerSubdomain . '.' . $baseDomain;
        $apiDomain = 'api.' . $baseDomain;
        $wwwBaseDomain = 'www.' . $baseDomain;

        // 0. Localhost / IP direto (healthchecks, artisan serve interno)
        if (in_array($host, ['127.0.0.1', 'localhost', '0.0.0.0'])) {
            return $next($request);
        }

        // 1. Domínios centrais do App/API — tudo liberado
        if ($host === $appDomain || $host === $apiDomain) {
            return $next($request);
        }

        // 2. Domínio base (site comercial + app SaaS)
        if ($host === $baseDomain) {
            // Super Admin, Admin, Login — servir diretamente
            if (
                $request->is('super-admin') || $request->is('super-admin/*') ||
                $request->is('admin') || $request->is('admin/*') ||
                $request->is('login') || $request->is('auth/*') ||
                $request->is('livewire/*') || $request->is('filament/*') ||
                $request->is('up') || $request->is('api/*')
            ) {
                return $next($request);
            }

            // Tudo mais no domínio base → site comercial (landing page)
            return $next($request);
        }

        if ($host === $wwwBaseDomain) {
            return redirect()->to(config('domain_routing.provider_scheme', 'https') . '://' . $baseDomain . $request->getRequestUri(), 302);
        }

        // 3. Subdomínio Wildcard (Vitrine do Cliente)
        if (str_ends_with($host, '.' . $baseDomain)) {
            $subdomain = str_replace('.' . $baseDomain, '', $host);

            // Vitrine não tem login/admin — redireciona para o painel central
            $restrictedPaths = ['login', 'register', 'api/auth/login', 'api/auth/register', 'api/auth/*'];

            foreach ($restrictedPaths as $path) {
                if ($request->is($path) || $request->is($path . '/*')) {
                    return redirect()->to('https://' . $appDomain . '/login', 302);
                }
            }

            // Injeta subdomínio na requisição para Controllers de vitrine
            $request->attributes->add(['tenant_subdomain' => $subdomain]);

            return $next($request);
        }

        // 4. Fallback — domínio desconhecido
        abort(404, 'Domínio não reconhecido.');
    }
}
