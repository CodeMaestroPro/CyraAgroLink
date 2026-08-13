/**
 * Persist and apply CyraAgroLink light/dark theme.
 */
const STORAGE_KEY = 'cyra-theme';

/**
 * @returns {'light'|'dark'}
 */
export function resolveTheme() {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored === 'light' || stored === 'dark') {
            return stored;
        }
    } catch {
        // Ignore storage access errors.
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

/**
 * @param {'light'|'dark'} theme
 */
export function applyTheme(theme) {
    document.documentElement.classList.toggle('dark', theme === 'dark');

    try {
        localStorage.setItem(STORAGE_KEY, theme);
    } catch {
        // Ignore storage access errors.
    }

    document.documentElement.style.colorScheme = theme;
    window.dispatchEvent(new CustomEvent('cyra-theme-changed', { detail: { theme } }));
}

/**
 * Toggle between light and dark themes.
 * @returns {'light'|'dark'}
 */
export function toggleTheme() {
    const next = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
    applyTheme(next);

    return next;
}

export function initTheme() {
    applyTheme(resolveTheme());
}
