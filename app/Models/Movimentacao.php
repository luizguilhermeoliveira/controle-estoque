<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tipo',
    'material_id',
    'quantidade',
    'almoxarifado_origem_id',
    'almoxarifado_destino_id',
    'user_id',
])]
class Movimentacao extends Model
{
    use HasFactory;

    protected $table = 'movimentacoes';

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function origem(): BelongsTo
    {
        return $this->belongsTo(Almoxarifado::class, 'almoxarifado_origem_id');
    }

    public function destino(): BelongsTo
    {
        return $this->belongsTo(Almoxarifado::class, 'almoxarifado_destino_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
