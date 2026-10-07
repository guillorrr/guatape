import { definePreset } from '@primeuix/themes';
import Aura from '@primeuix/themes/aura';

const AppPreset = definePreset(Aura, {
  semantic: {
    primary: {
      50: '#e8f0fe',
      100: '#c5dbfc',
      200: '#9fc3fa',
      300: '#78abf7',
      400: '#5a96f5',
      500: '#1a73e8',
      600: '#1557b0',
      700: '#104a9e',
      800: '#0b3d8b',
      900: '#062e6f',
      950: '#031d4d',
    },
    colorScheme: {
      light: {
        surface: {
          0: '#ffffff',
          50: '#f8f9fa',
          100: '#f1f3f5',
          200: '#e8eaed',
          300: '#dadce0',
          400: '#bdc1c6',
          500: '#9aa0a6',
          600: '#80868b',
          700: '#5f6368',
          800: '#3c4043',
          900: '#202124',
          950: '#1a1a1a',
        },
      },
    },
  },
});

export default AppPreset;
