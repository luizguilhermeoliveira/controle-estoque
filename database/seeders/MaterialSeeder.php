<?php

namespace Database\Seeders;

use App\Models\Almoxarifado;
use App\Models\Material;
use Illuminate\Database\Seeder;

class MaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Cria materiais com saldo inicial distribuído no pivot e mantém
     * `quantidade_total` consistente com a soma das quantidades.
     */
    public function run(): void
    {
        $almoxarifados = Almoxarifado::all()->keyBy('nome');

        $materiais = [
            [
                'codigo_interno' => 'MAT-0001',
                'descricao' => 'Parafuso sextavado M8',
                'estoque' => [
                    'Almoxarifado Central' => 500,
                    'Almoxarifado Manutenção' => 120,
                ],
            ],
            [
                'codigo_interno' => 'MAT-0002',
                'descricao' => 'Cabo elétrico 2,5mm (metro)',
                'estoque' => [
                    'Almoxarifado Central' => 300,
                    'Almoxarifado Norte' => 150,
                ],
            ],
            [
                'codigo_interno' => 'MAT-0003',
                'descricao' => 'Luva de proteção (par)',
                'estoque' => [
                    'Almoxarifado Norte' => 80,
                ],
            ],
            [
                'codigo_interno' => 'MAT-0004',
                'descricao' => 'Fita isolante 19mm',
                'estoque' => [
                    'Almoxarifado Central' => 200,
                    'Almoxarifado Norte' => 60,
                    'Almoxarifado Manutenção' => 40,
                ],
            ],
        ];

        foreach ($materiais as $dados) {
            $quantidadeTotal = array_sum($dados['estoque']);

            $material = Material::firstOrCreate(
                ['codigo_interno' => $dados['codigo_interno']],
                [
                    'descricao' => $dados['descricao'],
                    'quantidade_total' => $quantidadeTotal,
                ]
            );

            $pivot = [];
            foreach ($dados['estoque'] as $nomeAlmoxarifado => $quantidade) {
                $pivot[$almoxarifados[$nomeAlmoxarifado]->id] = ['quantidade' => $quantidade];
            }

            $material->almoxarifados()->sync($pivot);
        }
    }
}
