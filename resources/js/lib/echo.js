import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

let echoInstance = null
let echoUnavailable = false

function canBootstrapRealtime() {
    const key = import.meta.env.VITE_REVERB_APP_KEY
    const host = import.meta.env.VITE_REVERB_HOST

    return Boolean(key && host)
}

export function getEcho() {
    if (echoUnavailable || echoInstance || !canBootstrapRealtime()) {
        return echoInstance
    }

    try {
        window.Pusher = Pusher

        echoInstance = new Echo({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
            wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
        })

        window.Echo = echoInstance
    } catch (error) {
        echoUnavailable = true
        console.error('Realtime client bootstrap failed.', error)
    }

    return echoInstance
}

export function disconnectEcho() {
    if (!echoInstance) {
        return
    }

    echoInstance.disconnect()
    echoInstance = null
}
