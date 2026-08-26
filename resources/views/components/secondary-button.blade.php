<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-5 py-2.5 bg-white border-2 border-vale-red rounded-full font-semibold text-sm text-vale-red hover:bg-vale-red hover:text-white focus:outline-none focus:ring-2 focus:ring-vale-red focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
