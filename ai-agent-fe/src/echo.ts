import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

export function createEcho(): Echo<'reverb'> {
  return new Echo({
    broadcaster: 'reverb',
    client: Pusher,
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST ?? '127.0.0.1',
    wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
    forceTLS: false,
    enabledTransports: ['ws'],
    authEndpoint: '/api/broadcasting/auth',
    auth: { headers: { Authorization: `Bearer ${localStorage.getItem('ai_agent_token')}` } },
  })
}
