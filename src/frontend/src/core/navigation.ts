/**
 * Sidebar menu. Items are filtered by permission (see usePermissions): an item
 * without `permission` is visible to every authenticated user; a group is shown
 * only if at least one of its children is.
 *
 * Keep it in sync with the routes' `meta.permission` (router/index.ts): hiding a
 * link is UX, the route guard and the API are what actually deny access.
 */
export interface MenuItem {
  label: string;
  icon: string;
  to?: string;
  /** Visible if the user has ANY of these permissions. */
  permission?: string | string[];
  children?: MenuItem[];
}

export const menu: MenuItem[] = [
  { label: 'Inicio', icon: 'pi pi-home', to: '/app' },
  {
    label: 'Configuración',
    icon: 'pi pi-cog',
    children: [
      { label: 'Usuarios', icon: 'pi pi-users', to: '/app/settings/users', permission: 'users.view' },
    ],
  },
];
