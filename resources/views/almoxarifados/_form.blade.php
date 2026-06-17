{{-- Campos compartilhados pelos formulários de criação e edição. --}}
<div class="mb-3">
    <label for="nome" class="form-label">Nome</label>
    <input
        type="text"
        class="form-control @error('nome') is-invalid @enderror"
        id="nome"
        name="nome"
        value="{{ old('nome', $almoxarifado->nome ?? '') }}"
        maxlength="255"
        required
        autofocus>
    @error('nome')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="localizacao" class="form-label">Localização</label>
    <input
        type="text"
        class="form-control @error('localizacao') is-invalid @enderror"
        id="localizacao"
        name="localizacao"
        value="{{ old('localizacao', $almoxarifado->localizacao ?? '') }}"
        maxlength="255"
        required>
    @error('localizacao')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
