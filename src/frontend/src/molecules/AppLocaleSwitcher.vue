<script setup lang="ts">
/**
 * Language picker for places without a profile (login, public pages). Signed-in
 * users pick theirs on the account page, which also saves it on the API.
 */
import Select from 'primevue/select';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { LOCALE_NAMES, SUPPORTED_LOCALES, setLocale, type AppLocale } from '@/i18n';

const { t, locale } = useI18n();

const options = SUPPORTED_LOCALES.map((code) => ({ value: code, label: LOCALE_NAMES[code] }));

const selected = computed({
  get: () => locale.value as AppLocale,
  set: (value: AppLocale) => setLocale(value),
});
</script>

<template>
  <Select
    v-model="selected"
    :options="options"
    option-label="label"
    option-value="value"
    size="small"
    :aria-label="t('common.language')"
  />
</template>
