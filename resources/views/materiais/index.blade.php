@extends('layouts.app')

@section('titulo', 'Materiais — Controle de Estoque')

@section('conteudo')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0">Materiais</h1>
        <a href="{{ route('materiais.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Novo material
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabela-materiais" class="table table-striped table-hover align-middle w-100">
                    <thead>
                        <tr>
                            <th>Código interno</th>
                            <th>Descrição</th>
                            <th class="text-center">Quantidade total</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($materiais as $material)
                            <tr>
                                <td>{{ $material->codigo_interno }}</td>
                                <td>{{ $material->descricao }}</td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">{{ $material->quantidade_total }}</span>
                                </td>
                                <td class="text-end">
                                    <a
                                        href="{{ route('materiais.edit', $material) }}"
                                        class="btn btn-sm btn-outline-primary"
                                        title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form
                                        method="POST"
                                        action="{{ route('materiais.destroy', $material) }}"
                                        class="d-inline js-form-excluir"
                                        data-codigo="{{ $material->codigo_interno }}">
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
                                    Nenhum material cadastrado.
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
            if ($('#tabela-materiais tbody tr').length && !$('#tabela-materiais td.text-muted').length) {
                $('#tabela-materiais').DataTable({
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
                        title: 'Excluir material?',
                        text: 'O material "' + form.dataset.codigo + '" será removido permanentemente.',
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
