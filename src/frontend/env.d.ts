/// <reference types="vite/client" />

interface ImportMetaEnv {
  /** Name shown in the sidebar, the header and the browser tab. */
  readonly VITE_APP_NAME?: string;
  /** BCP 47 locale for dates and numbers (default es-AR). */
  readonly VITE_LOCALE?: string;
  /** IANA time zone used to display API timestamps (default America/Argentina/Buenos_Aires). */
  readonly VITE_TIME_ZONE?: string;
  /** ISO 4217 currency for useCurrency (default ARS). */
  readonly VITE_CURRENCY?: string;
}

interface ImportMeta {
  readonly env: ImportMetaEnv;
}

declare module '*.vue' {
  import type { DefineComponent } from 'vue';
  const component: DefineComponent<object, object, unknown>;
  export default component;
}
