<?php

namespace App\Events;

use App\Models\Orcamento;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrcamentoCriado
{
    use Dispatchable, SerializesModels;

    public $orcamento;

    public function __construct(Orcamento $orcamento)
    {
        $this->orcamento = $orcamento;
    }
}
