<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-cyra-forest border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-cyra-green focus:bg-cyra-green active:bg-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
