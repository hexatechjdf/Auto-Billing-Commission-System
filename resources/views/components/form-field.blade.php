<div class="form-group mb-3">
    <label class="form-label" for="{{ $name }}">{{ $label }}</label>
    <input type="{{ $type ?? 'text' }}" name="{{ $name }}" id="{{ $name }}"
           class="form-control" value="{{ $value ?? '' }}" {{ $required ? 'required' : '' }}>
    @error($name)
        <span class="text-danger">{{ $message }}</span>
    @enderror
</div>
