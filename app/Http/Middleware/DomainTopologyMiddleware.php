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
        $baseDomain = 'autonomia.app.br';
        $appDomain = 'app.autonomia.app.br';
        $apiDomain = 'api.autonomia.app.br';

        // 1. O cara tá acessando domínios centrais do App/API
        if ($host === $appDomain || $host === $apiDomain) {
            // Aqui dentro roda o Filament, rotas de API privadas e o Login. Tudo liberado.
            return $next($request);
        }

        // 2. O cara tá acessando o Site de Marketing (Landing Page)
        if ($host === $baseDomain) {
            return $next($request);
        }

        // 3. O cara tá acessando um Subdomínio Wildcard (A Vitrine do Cliente)
        // Exemplo: joao.autonomia.app.br
        if (str_ends_with($host, '.' . $baseDomain)) {
            $subdomain = str_replace('.' . $baseDomain, '', $host);

            // Regra Inegociável: Vitrine NÃO TEM TELA DE LOGIN.
            // Se o peão tentar burlar e digitar /login ou /admin, toma um 301 na testa.
            $restrictedPaths = ['login', 'admin', 'app', 'register', 'api/auth/login', 'api/auth/register', 'api/auth/*'];
            $currentPath = $request->path();

            foreach ($restrictedPaths as $path) {
                if ($request->is($path) || $request->is($path . '/*')) {
                    // Redireciona o corno pro painel central
                    return redirect()->to('https://' . $appDomain . '/login', 301);
                }
            }

            // Se passou, injeta o subdomínio na requisição para os Controllers usarem na Vitrine
            $request->attributes->add(['tenant_subdomain' => $subdomain]);

            return $next($request);
        }

        // 4. Fallback de segurança (se cair aqui é bot chinês scaneando IP direto)
        abort(404, 'Domínio não reconhecido pela base Autonomia.');
    }
}
