/**
 * Locale and time zone used to display dates and numbers.
 *
 * The API stores and serves timestamps in UTC (config/app.php timezone=UTC), so
 * converting to local time is the frontend's job. Without an explicit timeZone,
 * toLocaleString uses the runtime's zone and shows UTC hours on servers and
 * containers. Override with VITE_LOCALE / VITE_TIME_ZONE (src/frontend/.env).
 */
export const LOCALE: string = import.meta.env.VITE_LOCALE || 'es-AR'

export const TIME_ZONE: string = import.meta.env.VITE_TIME_ZONE || 'America/Argentina/Buenos_Aires'

export const CURRENCY: string = import.meta.env.VITE_CURRENCY || 'ARS'
