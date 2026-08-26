@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-vale-navy']) }}>
    {{ $value ?? $slot }}
</label>
