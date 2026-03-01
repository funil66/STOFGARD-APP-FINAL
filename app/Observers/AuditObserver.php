<?php

namespace App\Observers;

use OwenIt\Auditing\Models\Audit;
use Exception;

class AuditObserver
{
    public function updating(Audit $audit)
    {
        throw new Exception('Acesso Negado: Registos de auditoria são imutáveis (LGPD).');
    }

    public function deleting(Audit $audit)
    {
        throw new Exception('Acesso Negado: Registros de auditoria estruturais de LGPD não podem ser deletados da base.');
    }
}
