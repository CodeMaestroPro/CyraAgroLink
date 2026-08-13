<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 bg-white border border-cyra-line rounded-md font-semibold text-xs text-cyra-ink uppercase tracking-widest shadow-sm hover:bg-cyra-mint focus:outline-none focus:ring-2 focus:ring-cyra-forest focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
