<?php

namespace App\Http\Controllers;

use App\Http\Requests\MaterialStoreRequest;
use App\Http\Requests\MaterialUpdateRequest;
use App\Models\Almoxarifado;
use App\Models\Material;
use App\Services\MaterialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * CRUD de materiais.
 *
 * Controller enxuto: valida via FormRequest, delega a regra de negócio ao
 * {@see MaterialService} e devolve a resposta com flash messages. O estoque
 * inicial informado no cadastro é lançado no pivot pelo service.
 */
class MaterialController extends Controller
{
    public function __construct(private readonly MaterialService $service)
    {
    }

    /**
     * Lista os materiais com a quantidade total em estoque.
     */
    public function index(): View
    {
        $materiais = Material::orderBy('codigo_interno')->get();

        return view('materiais.index', compact('materiais'));
    }

    /**
     * Exibe o formulário de criação com os almoxarifados disponíveis.
     */
    public function create(): View
    {
        $almoxarifados = Almoxarifado::orderBy('nome')->get();

        return view('materiais.create', compact('almoxarifados'));
    }

    /**
     * Persiste um novo material.
     */
    public function store(MaterialStoreRequest $request): RedirectResponse
    {
        $this->service->criar($request->validated());

        return redirect()
            ->route('materiais.index')
            ->with('success', 'Material cadastrado com sucesso.');
    }

    /**
     * Exibe o formulário de edição.
     */
    public function edit(Material $material): View
    {
        return view('materiais.edit', compact('material'));
    }

    /**
     * Atualiza os dados cadastrais de um material existente.
     */
    public function update(MaterialUpdateRequest $request, Material $material): RedirectResponse
    {
        $this->service->atualizar($material, $request->validated());

        return redirect()
            ->route('materiais.index')
            ->with('success', 'Material atualizado com sucesso.');
    }

    /**
     * Exclui um material e seus vínculos de estoque.
     */
    public function destroy(Material $material): RedirectResponse
    {
        $this->service->excluir($material);

        return redirect()
            ->route('materiais.index')
            ->with('success', 'Material excluído com sucesso.');
    }
}
