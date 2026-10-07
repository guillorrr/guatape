/**
 * Sidebar menu. Items are filtered by permission (see usePermissions): an item
 * without `permission` is visible to every authenticated user; a group is shown
 * only if at least one of its children is.
 *
 * Keep it in sync with the routes' `meta.permission` (router/index.ts): hiding a
 * link is UX, the route guard and the API are what actually deny access.
 */
export interface MenuItem {
  /** i18n key (src/locales/*.json). */
  label: string;
  icon: string;
  to?: string;
  /** Visible if the user has ANY of these permissions. */
  permission?: string | string[];
  /** Only for super admins on the central domain (tenancy on). */
  superAdmin?: boolean;
  children?: MenuItem[];
}

export const menu: MenuItem[] = [
  { label: 'nav.home', icon: 'pi pi-home', to: '/app' },
  {
    label: 'nav.settings',
    icon: 'pi pi-cog',
    children: [
      {
        label: 'nav.users',
        icon: 'pi pi-users',
        to: '/app/settings/users',
        permission: 'users.view',
      },
    ],
  },
  {
    label: 'nav.system',
    icon: 'pi pi-server',
    children: [
      {
        label: 'nav.activity',
        icon: 'pi pi-history',
        to: '/app/system/activity',
        permission: 'system.view',
      },
      {
        label: 'nav.commands',
        icon: 'pi pi-list',
        to: '/app/system/commands',
        permission: 'system.view',
      },
    ],
  },
  {
    label: 'nav.platform',
    icon: 'pi pi-globe',
    superAdmin: true,
    children: [{ label: 'nav.tenants', icon: 'pi pi-building', to: '/app/platform/tenants' }],
  },
  // Development only: living reference of the form kit (pages/dev/FormKitPage.vue).
  ...(import.meta.env.DEV
    ? [{ label: 'nav.formKit', icon: 'pi pi-palette', to: '/app/dev/form-kit' }]
    : []),
];
