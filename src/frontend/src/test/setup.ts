import { i18n } from '@/i18n';

// jsdom reports an en-US browser; pin the UI language so specs don't depend
// on the machine running them.
i18n.global.locale.value = 'es';
