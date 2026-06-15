<?php

namespace Database\Seeders;

use App\Models\Almoxarifado;
use Illuminate\Database\Seeder;

class AlmoxarifadoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $almoxarifados = [
            ['nome' => 'Almoxarifado Central', 'localizacao' => 'Galpão A - Térreo'],
            ['nome' => 'Almoxarifado Norte', 'localizacao' => 'Unidade Norte - Bloco 2'],
            ['nome' => 'Almoxarifado Manutenção', 'localizacao' => 'Oficina - Setor 3'],
        ];

        foreach ($almoxarifados as $almoxarifado) {
            Almoxarifado::firstOrCreate(['nome' => $almoxarifado['nome']], $almoxarifado);
        }
    }
}
