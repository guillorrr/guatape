import { computed } from 'vue';
import { useAuthStore } from '@/core/stores/auth.store';

/**
 * Permission checks for templates and navigation. Gate on permissions
 * (`recurso.accion`, seeded by RolePermissionSeeder), not on role names: roles
 * are bundles of permissions and change per fork, permissions don't.
 *
 * This is UX only — the API enforces the same permissions on every route.
 */
export function usePermissions() {
  const auth = useAuthStore();

  const roles = computed<string[]>(() => auth.roles);
  const permissions = computed<string[]>(() => auth.permissions);

  function hasRole(role: string): boolean {
    return roles.value.includes(role);
  }

  function hasAnyRole(...checkRoles: string[]): boolean {
    return checkRoles.some((r) => roles.value.includes(r));
  }

  // Super admins can do everything, as on the API (Gate::before).
  const isSuperAdmin = computed(() => auth.user?.is_super_admin === true);

  function can(permission: string): boolean {
    return isSuperAdmin.value || permissions.value.includes(permission);
  }

  function canAny(...checkPermissions: string[]): boolean {
    return isSuperAdmin.value || checkPermissions.some((p) => permissions.value.includes(p));
  }

  function canAll(...checkPermissions: string[]): boolean {
    return isSuperAdmin.value || checkPermissions.every((p) => permissions.value.includes(p));
  }

  return { roles, permissions, isSuperAdmin, hasRole, hasAnyRole, can, canAny, canAll };
}
