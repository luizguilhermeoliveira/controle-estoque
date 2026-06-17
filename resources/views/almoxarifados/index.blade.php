@extends('layouts.app')

@section('titulo', 'Almoxarifados — Controle de Estoque')

@section('conteudo')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0">Almoxarifados</h1>
        <a href="{{ route('almoxarifados.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Novo almoxarifado
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabela-almoxarifados" class="table table-striped table-hover align-middle w-100">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Localização</th>
                            <th class="text-center">Materiais</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($almoxarifados as $almoxarifado)
                            <tr>
                                <td>{{ $almoxarifado->nome }}</td>
                                <td>{{ $almoxarifado->localizacao }}</td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">{{ $almoxarifado->materiais_count }}</span>
                                </td>
                                <td class="text-end">
                                    <a
                                        href="{{ route('almoxarifados.edit', $almoxarifado) }}"
                                        class="btn btn-sm btn-outline-primary"
                                        title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form
                                        method="POST"
                                        action="{{ route('almoxarifados.destroy', $almoxarifado) }}"
                                        class="d-inline js-form-excluir"
                                        data-nome="{{ $almoxarifado->nome }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    Nenhum almoxarifado cadastrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if ($('#tabela-almoxarifados tbody tr').length && !$('#tabela-almoxarifados td.text-muted').length) {
                $('#tabela-almoxarifados').DataTable({
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/pt-BR.json',
                    },
                    columnDefs: [{ orderable: false, targets: 3 }],
                });
            }

            document.querySelectorAll('.js-form-excluir').forEach(function (form) {
                form.addEventListener('submit', function (evento) {
                    evento.preventDefault();

                    Swal.fire({
                        title: 'Excluir almoxarifado?',
                        text: 'O almoxarifado "' + form.dataset.nome + '" será removido permanentemente.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sim, excluir',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#dc3545',
                    }).then(function (resultado) {
                        if (resultado.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });
        });
    </script>
@endpush
