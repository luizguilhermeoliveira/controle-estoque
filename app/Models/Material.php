<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['codigo_interno', 'descricao', 'quantidade_total'])]
class Material extends Model
{
    use HasFactory;

    protected $table = 'materiais';

    /**
     * Almoxarifados onde este material está em estoque, com a quantidade no pivot.
     */
    public function almoxarifados(): BelongsToMany
    {
        return $this->belongsToMany(Almoxarifado::class, 'almoxarifado_material')
            ->withPivot('quantidade')
            ->withTimestamps();
    }

    /**
     * Movimentações registradas para este material.
     */
    public function movimentacoes(): HasMany
    {
        return $this->hasMany(Movimentacao::class);
    }
}
