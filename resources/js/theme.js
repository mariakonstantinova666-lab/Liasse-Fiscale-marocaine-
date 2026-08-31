const STORAGE_KEY = 'theme';
const THEMES = ['light', 'dark', 'system'];

let mediaListenerRegistered = false;

const hasWindow = () => typeof window !== 'undefined';

export const themeStorageKey = STORAGE_KEY;
export const availableThemes = THEMES;

export const normalizeTheme = (theme) => (THEMES.includes(theme) ? theme : 'system');

export const getStoredTheme = () => {
    if (!hasWindow()) {
        return 'system';
    }

    try {
        return normalizeTheme(window.localStorage.getItem(STORAGE_KEY));
    } catch {
        return 'system';
    }
};

export const systemPrefersDark = () => {
    if (!hasWindow() || !window.matchMedia) {
        return false;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches;
};

export const resolveTheme = (theme = getStoredTheme()) => {
    const normalized = normalizeTheme(theme);

    if (normalized === 'system') {
        return systemPrefersDark() ? 'dark' : 'light';
    }

    return normalized;
};

export const applyTheme = (theme = getStoredTheme()) => {
    if (!hasWindow()) {
        return { theme: normalizeTheme(theme), resolvedTheme: 'light' };
    }

    const normalized = normalizeTheme(theme);
    const resolvedTheme = resolveTheme(normalized);
    const root = document.documentElement;

    root.classList.toggle('dark', resolvedTheme === 'dark');
    root.dataset.theme = normalized;
    root.dataset.resolvedTheme = resolvedTheme;
    root.style.colorScheme = resolvedTheme;

    return { theme: normalized, resolvedTheme };
};

export const setTheme = (theme) => {
    const normalized = normalizeTheme(theme);

    if (hasWindow()) {
        try {
            window.localStorage.setItem(STORAGE_KEY, normalized);
        } catch {
            // The visual theme can still be applied when storage is unavailable.
        }
    }

    const result = applyTheme(normalized);

    if (hasWindow()) {
        window.dispatchEvent(new CustomEvent('theme:changed', { detail: result }));
    }

    return result;
};

export const initTheme = () => {
    const result = applyTheme();

    if (hasWindow() && window.matchMedia && !mediaListenerRegistered) {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        const syncSystemTheme = () => {
            if (getStoredTheme() === 'system') {
                const applied = applyTheme('system');
                window.dispatchEvent(new CustomEvent('theme:changed', { detail: applied }));
            }
        };

        if (mediaQuery.addEventListener) {
            mediaQuery.addEventListener('change', syncSystemTheme);
        } else if (mediaQuery.addListener) {
            mediaQuery.addListener(syncSystemTheme);
        }

        mediaListenerRegistered = true;
    }

    return result;
};
