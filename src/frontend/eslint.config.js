// ESLint 9 flat config: Vue 3 + TypeScript, with Prettier owning formatting.
import pluginVue from 'eslint-plugin-vue';
import { defineConfigWithVueTs, vueTsConfigs } from '@vue/eslint-config-typescript';
import prettier from 'eslint-config-prettier';
import vueI18n from '@intlify/eslint-plugin-vue-i18n';

export default defineConfigWithVueTs(
  {
    name: 'app/ignores',
    ignores: ['dist/**', 'node_modules/**', 'storybook-static/**', 'coverage/**'],
  },
  pluginVue.configs['flat/recommended'],
  vueTsConfigs.recommended,
  {
    name: 'app/rules',
    rules: {
      // Generic components and pages use single-word names on purpose only
      // for pages (HomePage) and layouts; everything else is App*-prefixed.
      'vue/multi-word-component-names': 'off',
      '@typescript-eslint/no-unused-vars': [
        'error',
        { argsIgnorePattern: '^_', varsIgnorePattern: '^_' },
      ],
    },
  },
  // UI text lives in src/locales/*.json: literal text in templates and keys
  // that don't exist fail the lint.
  ...vueI18n.configs['flat/recommended'],
  {
    name: 'app/i18n',
    rules: {
      '@intlify/vue-i18n/no-raw-text': [
        'error',
        // Punctuation, numbers, separators and technical codes aren't
        // translatable text.
        {
          ignorePattern: '^[-#:()&*%.,/|·—–«»“”"\'!?+=@0-9\\s]+$',
          ignoreText: ['', 'DEV', 'PROD'],
        },
      ],
      '@intlify/vue-i18n/no-missing-keys': 'error',
      // Dynamic keys (t(`activity.status.${s}`)) can't be tracked statically.
      '@intlify/vue-i18n/no-dynamic-keys': 'off',
      '@intlify/vue-i18n/no-unused-keys': 'off',
      '@intlify/vue-i18n/no-v-html': 'error',
    },
    settings: {
      'vue-i18n': { localeDir: './src/locales/*.json', messageSyntaxVersion: '^11.0.0' },
    },
  },
  prettier,
);
