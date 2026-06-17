{{-- Campos cadastrais compartilhados pelos formulários de criação e edição. --}}
<div class="mb-3">
    <label for="codigo_interno" class="form-label">Código interno</label>
    <input
        type="text"
        class="form-control @error('codigo_interno') is-invalid @enderror"
        id="codigo_interno"
        name="codigo_interno"
        value="{{ old('codigo_interno', $material->codigo_interno ?? '') }}"
        maxlength="255"
        required
        autofocus>
    @error('codigo_interno')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="descricao" class="form-label">Descrição</label>
    <input
        type="text"
        class="form-control @error('descricao') is-invalid @enderror"
        id="descricao"
        name="descricao"
        value="{{ old('descricao', $material->descricao ?? '') }}"
        maxlength="255"
        required>
    @error('descricao')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
