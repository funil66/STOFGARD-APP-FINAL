<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CadastroView extends Model
{
    protected $table = 'cadastros_view';

    // View is read-only and has a string synthetic id
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    // Helper accessor to return underlying model instance (Cliente or Parceiro)
    public function getUnderlyingModelAttribute()
    {
        if ($this->model === 'cliente') {
            return \App\Models\Cliente::find($this->model_id);
        }

        if ($this->model === 'parceiro') {
            return \App\Models\Parceiro::find($this->model_id);
        }

        return null;
    }
}