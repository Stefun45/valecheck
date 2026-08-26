@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 text-vale-navy focus:border-vale-red focus:ring-vale-red rounded-md shadow-sm']) }}>
