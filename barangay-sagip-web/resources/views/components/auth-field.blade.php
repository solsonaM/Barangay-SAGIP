@props([
    'label',
    'name',
    'type' => 'text',
    'value' => null,
    'required' => true,
    'placeholder' => null,
])

<div>
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-300 mb-1.5">{{ $label }}</label>
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ $value }}"
        @if($required) required @endif
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        {{ $attributes->merge([
            'class' => 'w-full rounded-xl bg-field border border-edge px-4 py-3 text-gray-100 placeholder-gray-600 '
                     . 'focus:outline-none focus:ring-2 focus:ring-violet/60 focus:border-violet transition'
        ]) }}
    >
    @error($name)
        <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
    @enderror
</div>
