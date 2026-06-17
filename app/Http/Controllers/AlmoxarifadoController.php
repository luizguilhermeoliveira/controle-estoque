<?php

namespace App\Http\Controllers;

use App\Exceptions\RegraDeNegocioException;
use App\Http\Requests\AlmoxarifadoStoreRequest;
use App\Http\Requests\AlmoxarifadoUpdateRequest;
use App\Models\Almoxarifado;
use App\Services\AlmoxarifadoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * CRUD de almoxarifados.
 *
 * Controller enxuto: valida via FormRequest, delega a regra de negócio ao
 * {@see AlmoxarifadoService} e devolve a resposta com flash messages. A regra
 * de exclusão (almoxarifado com estoque) é tratada via {@see RegraDeNegocioException}.
 */
class AlmoxarifadoController extends Controller
{
    public function __construct(private readonly AlmoxarifadoService $service)
    {
    }

    /**
     * Lista os almoxarifados com a quantidade de materiais associados.
     */
    public function index(): View
    {
        $almoxarifados = Almoxarifado::withCount('materiais')
            ->orderBy('nome')
            ->get();

        return view('almoxarifados.index', compact('almoxarifados'));
    }

    /**
     * Exibe o formulário de criação.
     */
    public function create(): View
    {
        return view('almoxarifados.create');
    }

    /**
     * Persiste um novo almoxarifado.
     */
    public function store(AlmoxarifadoStoreRequest $request): RedirectResponse
    {
        $this->service->criar($request->validated());

        return redirect()
            ->route('almoxarifados.index')
            ->with('success', 'Almoxarifado cadastrado com sucesso.');
    }

    /**
     * Exibe o formulário de edição.
     */
    public function edit(Almoxarifado $almoxarifado): View
    {
        return view('almoxarifados.edit', compact('almoxarifado'));
    }

    /**
     * Atualiza um almoxarifado existente.
     */
    public function update(AlmoxarifadoUpdateRequest $request, Almoxarifado $almoxarifado): RedirectResponse
    {
        $this->service->atualizar($almoxarifado, $request->validated());

        return redirect()
            ->route('almoxarifados.index')
            ->with('success', 'Almoxarifado atualizado com sucesso.');
    }

    /**
     * Exclui um almoxarifado, respeitando a regra de estoque.
     */
    public function destroy(Almoxarifado $almoxarifado): RedirectResponse
    {
        try {
            $this->service->excluir($almoxarifado);
        } catch (RegraDeNegocioException $e) {
            return redirect()
                ->route('almoxarifados.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('almoxarifados.index')
            ->with('success', 'Almoxarifado excluído com sucesso.');
    }
}
