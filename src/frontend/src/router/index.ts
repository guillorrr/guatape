import { createRouter, createWebHistory } from 'vue-router';
import type { RouteRecordRaw } from 'vue-router';
import { useAuthStore } from '@/core/stores/auth.store';

declare module 'vue-router' {
  interface RouteMeta {
    /** Only for signed-in users; guests go to /login?redirect=… */
    requiresAuth?: boolean;
    /** Only for guests; signed-in users go to /app. */
    guest?: boolean;
    /** Required permission(s); the user needs ANY of them. Inherited by children. */
    permission?: string | string[];
    /** Browser tab title. */
    title?: string;
  }
}

// URLs are English and kebab-case; labels in the UI are not.
const routes: RouteRecordRaw[] = [
  {
    path: '/',
    component: () => import('@/templates/AppLayout.vue'),
    children: [{ path: '', name: 'home', component: () => import('@/pages/HomePage.vue') }],
  },
  {
    path: '/',
    component: () => import('@/templates/AuthLayout.vue'),
    meta: { guest: true },
    children: [
      {
        path: 'login',
        name: 'login',
        component: () => import('@/pages/auth/LoginPage.vue'),
        meta: { title: 'Iniciar sesión' },
      },
      {
        path: 'forgot-password',
        name: 'forgot-password',
        component: () => import('@/pages/auth/ForgotPasswordPage.vue'),
        meta: { title: 'Recuperar contraseña' },
      },
      {
        path: 'reset-password',
        name: 'reset-password',
        component: () => import('@/pages/auth/ResetPasswordPage.vue'),
        meta: { title: 'Nueva contraseña' },
      },
    ],
  },
  {
    path: '/app',
    component: () => import('@/templates/DashboardLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'dashboard',
        component: () => import('@/pages/DashboardPage.vue'),
        meta: { title: 'Inicio' },
      },
      {
        path: 'account',
        name: 'account',
        component: () => import('@/pages/AccountPage.vue'),
        meta: { title: 'Mi cuenta' },
      },
      {
        path: 'settings/users',
        name: 'settings-users',
        component: () => import('@/pages/settings/UserListPage.vue'),
        meta: { title: 'Usuarios', permission: 'users.view' },
      },
      {
        path: 'system',
        meta: { permission: 'system.view' },
        children: [
          {
            path: 'activity',
            name: 'system-activity',
            component: () => import('@/pages/system/ActivityPage.vue'),
            meta: { title: 'Actividad' },
          },
          {
            path: 'commands',
            name: 'system-commands',
            component: () => import('@/pages/system/CommandCatalogPage.vue'),
            meta: { title: 'Comandos' },
          },
        ],
      },
    ],
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/pages/NotFoundPage.vue'),
    meta: { title: 'No encontrado' },
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async (to) => {
  const auth = useAuthStore();
  await auth.ensureLoaded();

  if (to.matched.some((r) => r.meta.requiresAuth) && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } };
  }
  if (to.matched.some((r) => r.meta.guest) && auth.isAuthenticated) {
    return { name: 'dashboard' };
  }

  // Every matched record's permission must be satisfied (children inherit).
  const denied = to.matched.some((r) => {
    if (!r.meta.permission) return false;
    const required = Array.isArray(r.meta.permission) ? r.meta.permission : [r.meta.permission];
    return !required.some((p) => auth.permissions.includes(p));
  });
  if (denied) {
    return { name: 'dashboard' };
  }

  return true;
});

const appName = import.meta.env.VITE_APP_NAME || 'Guatape';
router.afterEach((to) => {
  // [DEV] prefix so the dev tab is hard to confuse with production.
  const base = import.meta.env.DEV ? `[DEV] ${appName}` : appName;
  document.title = to.meta.title ? `${to.meta.title} · ${base}` : base;
});

export default router;
