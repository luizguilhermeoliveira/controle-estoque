@extends('layouts.app')

@section('titulo', 'Novo material — Controle de Estoque')

@section('conteudo')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0">Novo material</h1>
        <a href="{{ route('materiais.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Voltar
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST" action="{{ route('materiais.store') }}">
                @csrf

                @include('materiais._form')

                <hr class="my-4">

                <h2 class="h6 text-muted mb-3">Estoque inicial</h2>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="almoxarifado_id" class="form-label">Almoxarifado</label>
                        <select
                            class="form-select @error('almoxarifado_id') is-invalid @enderror"
                            id="almoxarifado_id"
                            name="almoxarifado_id">
                            <option value="">Selecione um almoxarifado</option>
                            @foreach ($almoxarifados as $almoxarifado)
                                <option
                                    value="{{ $almoxarifado->id }}"
                                    @selected(old('almoxarifado_id') == $almoxarifado->id)>
                                    {{ $almoxarifado->nome }}
                                </option>
                            @endforeach
                        </select>
                        @error('almoxarifado_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="quantidade_inicial" class="form-label">Quantidade inicial</label>
                        <input
                            type="number"
                            class="form-control @error('quantidade_inicial') is-invalid @enderror"
                            id="quantidade_inicial"
                            name="quantidade_inicial"
                            value="{{ old('quantidade_inicial', 0) }}"
                            min="0"
                            step="1"
                            required>
                        @error('quantidade_inicial')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            Deixe 0 para cadastrar o material sem estoque inicial.
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Salvar
                    </button>
                    <a href="{{ route('materiais.index') }}" class="btn btn-light">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
