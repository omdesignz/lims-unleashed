import { ref, watch, onMounted } from 'vue'

const STORAGE_KEY = 'theme'
const DARK_CLASS = 'dark'

function readStorage() {
    if (typeof window === 'undefined') return null
    try { return window.localStorage.getItem(STORAGE_KEY) } catch { return null }
}

function writeStorage(value) {
    if (typeof window === 'undefined') return
    try { window.localStorage.setItem(STORAGE_KEY, value) } catch { /* Theme still works without storage. */ }
}

/**
 * Plano is light by default. Dark is an explicit choice (account menu or Shift+D),
 * remembered per user on the server and per browser in storage.
 */
export function resolveInitialDark(userTheme = null, stored = null) {
    if (userTheme === 'dark' || userTheme === 'light') {
        return userTheme === 'dark'
    }

    return stored === 'dark'
}

/** The browser chrome (mobile status bar, PWA title bar) matches the shell's canvas. */
export const THEME_CHROME = { light: '#ffffff', dark: '#070f1c' }

export function applyThemeToDocument(dark) {
    if (typeof document === 'undefined') return
    const root = document.documentElement
    root.classList.toggle(DARK_CLASS, dark)
    root.dataset.theme = dark ? 'dark' : 'light'
    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', dark ? THEME_CHROME.dark : THEME_CHROME.light)
}

export function useTheme(userTheme = null, persistToServer = false) {
    const isDark = ref(resolveInitialDark(userTheme, readStorage()))

    // The theme change is instant: transitions are suspended for the swap.
    function applyTheme() {
        if (typeof document === 'undefined') return
        const style = document.createElement('style')
        style.textContent = '*,*::before,*::after{transition:none !important}'
        document.head.append(style)
        applyThemeToDocument(isDark.value)
        void document.body.offsetHeight
        requestAnimationFrame(() => requestAnimationFrame(() => style.remove()))
    }

    function toggle() {
        isDark.value = !isDark.value
    }

    async function persistTheme(theme) {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')

        await fetch('/user/theme', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...(token ? { 'X-CSRF-TOKEN': token } : {}),
            },
            credentials: 'same-origin',
            body: JSON.stringify({ theme }),
        })
    }

    watch(isDark, (val) => {
        const theme = val ? 'dark' : 'light'
        writeStorage(theme)
        applyTheme()
        if (persistToServer) {
            persistTheme(theme).catch(() => {
                // Keep the local theme applied even if persistence fails.
            })
        }
    })

    onMounted(() => {
        applyTheme()
    })

    return { isDark, toggle }
}

/** Shift+D toggles the theme, except while the person is typing. */
export function isThemeShortcut(event) {
    if (!event.shiftKey || event.metaKey || event.ctrlKey || event.altKey || event.key?.toLowerCase() !== 'd') {
        return false
    }

    const target = event.target
    const tag = target?.tagName?.toLowerCase?.()

    return !(target?.isContentEditable || ['input', 'textarea', 'select'].includes(tag) || target?.closest?.('[contenteditable="true"], math-field'))
}
