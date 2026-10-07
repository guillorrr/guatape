import { useToast } from 'primevue/usetoast';
import { useI18n } from 'vue-i18n';

export function useAppToast() {
  const toast = useToast();
  const { t } = useI18n();

  return {
    success(message: string) {
      toast.add({ severity: 'success', summary: t('toast.success'), detail: message, life: 3000 });
    },
    error(message: string) {
      toast.add({ severity: 'error', summary: t('toast.error'), detail: message, life: 5000 });
    },
    info(message: string) {
      toast.add({ severity: 'info', summary: t('toast.info'), detail: message, life: 3000 });
    },
    warn(message: string) {
      toast.add({ severity: 'warn', summary: t('toast.warn'), detail: message, life: 4000 });
    },
  };
}
