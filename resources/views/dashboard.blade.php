@extends('layouts.app')

@section('titulo', 'Início — Controle de Estoque')

@section('conteudo')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1">Painel</h1>
            <p class="text-muted mb-0">Bem-vindo, {{ $usuario }}.</p>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-md-6 col-lg-4">
            <a href="{{ route('almoxarifados.index') }}" class="card shadow-sm border-0 h-100 text-decoration-none text-reset">
                <div class="card-body">
                    <h2 class="h5 card-title">
                        <i class="bi bi-building me-1"></i> Almoxarifados
                    </h2>
                    <p class="card-text text-muted">Cadastro e gestão dos almoxarifados.</p>
                </div>
            </a>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <a href="{{ route('materiais.index') }}" class="card shadow-sm border-0 h-100 text-decoration-none text-reset">
                <div class="card-body">
                    <h2 class="h5 card-title">
                        <i class="bi bi-box-seam me-1"></i> Materiais
                    </h2>
                    <p class="card-text text-muted">Cadastro e controle de materiais.</p>
                </div>
            </a>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h2 class="h5 card-title">
                        <i class="bi bi-arrow-left-right me-1"></i> Movimentações
                    </h2>
                    <p class="card-text text-muted">Entradas, saídas e transferências.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
