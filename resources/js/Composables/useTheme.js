import { ref, watch, onMounted } from 'vue'

const STORAGE_KEY = 'theme'
const DARK_CLASS = 'dark'

function getSystemPreference() {
    if (typeof window === 'undefined') return false
    return window.matchMedia('(prefers-color-scheme: dark)').matches
}

function readStorage() {
    if (typeof window === 'undefined') return null
    return window.localStorage.getItem(STORAGE_KEY)
}

function writeStorage(value) {
    if (typeof window === 'undefined') return
    window.localStorage.setItem(STORAGE_KEY, value)
}

export function useTheme(userTheme = null, persistToServer = false) {
    const stored = readStorage()

    const isDark = ref(
        userTheme === 'dark' || userTheme === 'light'
            ? userTheme === 'dark'
            : stored === 'dark' || (stored === null && getSystemPreference())
    )

    function applyTheme() {
        if (typeof document === 'undefined') return
        if (isDark.value) {
            document.documentElement.classList.add(DARK_CLASS)
        } else {
            document.documentElement.classList.remove(DARK_CLASS)
        }
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
