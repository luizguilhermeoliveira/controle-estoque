@extends('layouts.app')

@section('titulo', 'Editar almoxarifado — Controle de Estoque')

@section('conteudo')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0">Editar almoxarifado</h1>
        <a href="{{ route('almoxarifados.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Voltar
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST" action="{{ route('almoxarifados.update', $almoxarifado) }}">
                @csrf
                @method('PUT')

                @include('almoxarifados._form')

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Atualizar
                    </button>
                    <a href="{{ route('almoxarifados.index') }}" class="btn btn-light">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
