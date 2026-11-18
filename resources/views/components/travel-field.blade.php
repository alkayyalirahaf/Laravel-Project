@props(['name', 'label', 'type' => 'text', 'value' => null, 'options' => null, 'required' => false])
<label class="field">
    <span>{{ $label }}</span>
    @if($options !== null)
        <select name="{{ $name }}" @required($required) {{ $attributes }}>
            @foreach($options as $key => $description)
                <option value="{{ $key }}" @selected((string) old($name, $value) === (string) $key)>{{ $description }}</option>
            @endforeach
        </select>
    @elseif($type === 'textarea')
        <textarea name="{{ $name }}" @required($required) {{ $attributes }}>{{ old($name, $value) }}</textarea>
    @else
        <input name="{{ $name }}" type="{{ $type }}" @unless($type === 'password' || $type === 'file') value="{{ old($name, $value) }}" @endunless @required($required) {{ $attributes }}>
    @endif
    @if(isset($errors) && $errors->has($name))<small class="error">{{ $errors->first($name) }}</small>@endif
</label>
