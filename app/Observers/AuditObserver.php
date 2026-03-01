<?php

namespace App\Observers;

use OwenIt\Auditing\Models\Audit;
use Exception;

class AuditObserver
{
    /**
     * Trava nível "Headshot de AWP". Ninguém altera log.
     */
    public function updating(Audit $audit)
    {
        throw new Exception('Acesso Negado: Registros de auditoria são imutáveis por força de Compliance (LGPD).');
    }

    /**
     * Deletar prova? Aqui não, estagiário.
     */
    public function deleting(Audit $audit)
    {
        throw new Exception('Acesso Negado: Registros de auditoria não podem ser excluídos do sistema.');
    }
}
