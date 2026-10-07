import { watch } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import type { RouteRecordRaw } from 'vue-router';
import { useAuthStore } from '@/core/stores/auth.store';
import { useTenancyStore } from '@/core/stores/tenancy.store';
import { i18n } from '@/i18n';

declare module 'vue-router' {
  interface RouteMeta {
    /** Only for signed-in users; guests go to /login?redirect=… */
    requiresAuth?: boolean;
    /** Only for guests; signed-in users go to /app. */
    guest?: boolean;
    /** Required permission(s); the user needs ANY of them. Inherited by children. */
    permission?: string | string[];
    /** Platform administration: super admins on the central domain (tenancy on). */
    superAdmin?: boolean;
    /** Browser tab title, as an i18n key. */
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
        meta: { title: 'nav.login' },
      },
      {
        path: 'forgot-password',
        name: 'forgot-password',
        component: () => import('@/pages/auth/ForgotPasswordPage.vue'),
        meta: { title: 'nav.forgotPassword' },
      },
      {
        path: 'reset-password',
        name: 'reset-password',
        component: () => import('@/pages/auth/ResetPasswordPage.vue'),
        meta: { title: 'nav.resetPassword' },
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
        meta: { title: 'nav.home' },
      },
      {
        path: 'account',
        name: 'account',
        component: () => import('@/pages/AccountPage.vue'),
        meta: { title: 'nav.account' },
      },
      {
        path: 'settings/users',
        name: 'settings-users',
        component: () => import('@/pages/settings/UserListPage.vue'),
        meta: { title: 'nav.users', permission: 'users.view' },
      },
      // Living reference of the form kit, development builds only.
      ...(import.meta.env.DEV
        ? [
            {
              path: 'dev/form-kit',
              name: 'dev-form-kit',
              component: () => import('@/pages/dev/FormKitPage.vue'),
              meta: { title: 'nav.formKit' },
            },
          ]
        : []),
      {
        path: 'platform/tenants',
        name: 'platform-tenants',
        component: () => import('@/pages/platform/TenantListPage.vue'),
        meta: { title: 'nav.tenants', superAdmin: true },
      },
      {
        path: 'system',
        meta: { permission: 'system.view' },
        children: [
          {
            path: 'activity',
            name: 'system-activity',
            component: () => import('@/pages/system/ActivityPage.vue'),
            meta: { title: 'nav.activity' },
          },
          {
            path: 'commands',
            name: 'system-commands',
            component: () => import('@/pages/system/CommandCatalogPage.vue'),
            meta: { title: 'nav.commands' },
          },
        ],
      },
    ],
  },
  {
    path: '/unavailable',
    name: 'tenant-unavailable',
    component: () => import('@/pages/TenantUnavailablePage.vue'),
    meta: { title: 'tenancy.unavailableTitle' },
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/pages/NotFoundPage.vue'),
    meta: { title: 'nav.notFound' },
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async (to) => {
  // Organization first (the API resolves it from the host), then the user.
  const tenancy = useTenancyStore();
  await tenancy.ensureLoaded();
  if (tenancy.unavailable) {
    return to.name === 'tenant-unavailable' ? true : { name: 'tenant-unavailable' };
  }

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
    return !auth.user?.is_super_admin && !required.some((p) => auth.permissions.includes(p));
  });
  const superAdminOnly = to.matched.some((r) => r.meta.superAdmin);
  if (denied || (superAdminOnly && !(auth.user?.is_super_admin && tenancy.isCentral))) {
    return { name: 'dashboard' };
  }

  return true;
});

const appName = import.meta.env.VITE_APP_NAME || 'Guatape';
function updateTitle() {
  const route = router.currentRoute.value;
  // [DEV] prefix so the dev tab is hard to confuse with production.
  const base = import.meta.env.DEV ? `[DEV] ${appName}` : appName;
  document.title = route.meta.title ? `${i18n.global.t(route.meta.title)} · ${base}` : base;
}
router.afterEach(updateTitle);
// Re-title the open tab when the language changes.
watch(i18n.global.locale, updateTitle);

export default router;
