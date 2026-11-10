import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

try {
    window.Pusher = Pusher;

    const key = import.meta.env.VITE_REVERB_APP_KEY;
    if (!key) {
        console.warn('Realtime (Echo) skipped: VITE_REVERB_APP_KEY is not set.');
    } else {
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 80),
            wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN':
                        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    Accept: 'application/json',
                },
            },
        });
    }
} catch (e) {
    console.warn('Realtime (Echo) failed to initialize; continuing without it.', e);
    window.Echo = undefined;
}
