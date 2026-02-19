import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

declare global {
  interface Window {
    Pusher: typeof Pusher
    Echo: Echo<'pusher'>
  }
}

window.Pusher = Pusher

let echoInstances: Record<number, Echo<'pusher'>> = {}

export function useReverb(locationId: number): Echo<'pusher'> {
  if (echoInstances[locationId]) {
    return echoInstances[locationId]
  }

  const token = localStorage.getItem('preshift_token') || ''

  const echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_REVERB_APP_KEY || 'preshift-key',
    wsHost: import.meta.env.VITE_REVERB_HOST || '127.0.0.1',
    wsPort: import.meta.env.VITE_REVERB_PORT || 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT || 8080,
    forceTLS: false,
    disableStats: true,
    cluster: 'mt1',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/broadcasting/auth',
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    },
  })

  echoInstances[locationId] = echo
  return echo
}

export function useLocationChannel(locationId: number) {
  const echo = useReverb(locationId)
  return echo.private(`location.${locationId}`)
}

export function disconnectReverb() {
  Object.values(echoInstances).forEach((echo) => {
    echo.disconnect()
  })
  echoInstances = {}
}
