@csrf
@foreach(['name' => ['Name', 100], 'code' => ['Two-letter country code', 2], 'currency' => ['Currency', 10], 'language' => ['Language', 60]] as $field => [$label, $length])
    <div class="section">
        <label class="form-label" for="{{ $field }}">{{ $label }}</label>
        <input class="input-control @error($field) is-invalid @enderror" id="{{ $field }}" name="{{ $field }}"
               value="{{ old($field, $country->$field) }}" maxlength="{{ $length }}" required
               @if($field === 'code') minlength="2" pattern="[A-Za-z]{2}" @endif>
        @error($field)<div class="error">{{ $message }}</div>@enderror
    </div>
@endforeach
<div class="section">
    <label class="form-label" for="local_apps">Local apps (optional)</label>
    <textarea class="input-control @error('local_apps') is-invalid @enderror" id="local_apps" name="local_apps" rows="3" maxlength="10000">{{ old('local_apps', $country->local_apps) }}</textarea>
    @error('local_apps')<div class="error">{{ $message }}</div>@enderror
</div>
<button class="button" type="submit">Save country</button>
<a class="button secondary" href="{{ route('admin.countries') }}">Cancel</a>
