<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['nome', 'localizacao'])]
class Almoxarifado extends Model
{
    use HasFactory;

    protected $table = 'almoxarifados';

    /**
     * Materiais em estoque neste almoxarifado, com a quantidade no pivot.
     */
    public function materiais(): BelongsToMany
    {
        return $this->belongsToMany(Material::class, 'almoxarifado_material')
            ->withPivot('quantidade')
            ->withTimestamps();
    }
}
