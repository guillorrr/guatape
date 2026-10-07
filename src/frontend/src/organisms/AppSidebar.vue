<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router';
import { computed, ref } from 'vue';
import { useLayoutStore } from '@/core/stores/layout.store';
import { usePermissions } from '@/composables/usePermissions';
import { menu, type MenuItem } from '@/core/navigation';

const route = useRoute();
const router = useRouter();
const layout = useLayoutStore();
const { canAny } = usePermissions();
const appName = import.meta.env.VITE_APP_NAME || 'Guatape';

function allowed(item: MenuItem): boolean {
  if (!item.permission) return true;
  const required = Array.isArray(item.permission) ? item.permission : [item.permission];
  return canAny(...required);
}

const visibleMenuItems = computed<MenuItem[]>(() =>
  menu
    .filter(allowed)
    .map((item) => (item.children ? { ...item, children: item.children.filter(allowed) } : item))
    .filter((item) => !item.children || item.children.length > 0),
);

function isActive(to: string): boolean {
  if (to === '/app') return route.path === '/app';
  return route.path.startsWith(to);
}

function isGroupActive(item: MenuItem): boolean {
  return item.children?.some((c) => c.to && isActive(c.to)) ?? false;
}

const expanded = ref<Set<string>>(
  new Set(visibleMenuItems.value.filter((m) => m.children && isGroupActive(m)).map((m) => m.label)),
);

function onGroupClick(item: MenuItem) {
  // Open → collapse.
  if (expanded.value.has(item.label)) {
    expanded.value.clear();
    return;
  }
  // Collapsed → open (accordion, one at a time) and navigate to the group's
  // first page, unless we're already on it.
  expanded.value = new Set([item.label]);
  const first = item.children?.find((c) => c.to);
  if (first?.to && route.path !== first.to) {
    router.push(first.to);
    layout.closeOnMobile();
  }
}
</script>

<template>
  <aside class="sidebar" :class="{ 'sidebar--collapsed': !layout.sidebarOpen }">
    <div class="sidebar__brand">
      <i class="pi pi-th-large" style="font-size: 1.4rem" />
      <span>{{ appName }}</span>
    </div>
    <nav class="sidebar__nav">
      <template v-for="item in visibleMenuItems" :key="item.label">
        <RouterLink
          v-if="item.to"
          :to="item.to"
          class="nav-item"
          :class="{ 'nav-item--active': isActive(item.to) }"
          @click="layout.closeOnMobile()"
        >
          <i :class="item.icon" />
          <span>{{ item.label }}</span>
        </RouterLink>

        <div v-else class="nav-group">
          <button
            class="nav-group__header"
            :class="{ 'nav-group__header--active': isGroupActive(item) }"
            @click="onGroupClick(item)"
          >
            <i :class="item.icon" />
            <span>{{ item.label }}</span>
            <i
              class="pi pi-chevron-down nav-group__arrow"
              :class="{ 'nav-group__arrow--open': expanded.has(item.label) }"
            />
          </button>
          <transition name="slide">
            <div v-show="expanded.has(item.label)" class="nav-group__items">
              <RouterLink
                v-for="child in item.children"
                :key="child.to"
                :to="child.to!"
                class="nav-item nav-item--child"
                :class="{ 'nav-item--active': isActive(child.to!) }"
                @click="layout.closeOnMobile()"
              >
                <i :class="child.icon" />
                <span>{{ child.label }}</span>
              </RouterLink>
            </div>
          </transition>
        </div>
      </template>
    </nav>
  </aside>
</template>

<style scoped>
.sidebar {
  width: 260px;
  height: 100vh;
  background: var(--p-surface-900);
  display: flex;
  flex-direction: column;
  position: fixed;
  left: 0;
  top: 0;
  z-index: 100;
  transition: transform 0.2s ease;
}

/* Horizontal collapse: slide the whole rail out of view. On large screens the
   content margin (DashboardLayout) reclaims the space; on small screens the
   sidebar overlays the content, so collapsing simply hides the drawer. */
.sidebar--collapsed {
  transform: translateX(-100%);
}

.sidebar__brand {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 20px 20px;
  color: white;
  font-size: 1.2rem;
  font-weight: 700;
  letter-spacing: -0.02em;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  flex-shrink: 0;
}

.sidebar__nav {
  flex: 1;
  padding: 12px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 14px;
  border-radius: 8px;
  color: rgba(255, 255, 255, 0.6);
  text-decoration: none;
  font-size: 0.875rem;
  transition: all 0.15s;
}

.nav-item i {
  font-size: 0.9rem;
  width: 20px;
  text-align: center;
}

.nav-item:hover {
  color: rgba(255, 255, 255, 0.9);
  background: rgba(255, 255, 255, 0.06);
}

.nav-item--active {
  color: white;
  background: var(--p-primary-color);
}

.nav-item--active:hover {
  background: var(--p-primary-color);
}

.nav-item--child {
  padding-left: 28px;
  font-size: 0.82rem;
}

.nav-group__header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 14px;
  border-radius: 8px;
  color: rgba(255, 255, 255, 0.45);
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  width: 100%;
  border: none;
  background: none;
  cursor: pointer;
  margin-top: 12px;
  transition: color 0.15s;
  font-family: inherit;
}

.nav-group__header i:first-child {
  font-size: 0.85rem;
  width: 20px;
  text-align: center;
}

.nav-group__header:hover {
  color: rgba(255, 255, 255, 0.7);
}

.nav-group__header--active {
  color: rgba(255, 255, 255, 0.6);
}

.nav-group__arrow {
  margin-left: auto;
  font-size: 0.65rem;
  transition: transform 0.2s;
}

.nav-group__arrow--open {
  transform: rotate(180deg);
}

.nav-group__items {
  display: flex;
  flex-direction: column;
  gap: 1px;
  margin-top: 2px;
}

.slide-enter-active,
.slide-leave-active {
  transition: all 0.2s ease;
  overflow: hidden;
}
.slide-enter-from,
.slide-leave-to {
  opacity: 0;
  max-height: 0;
}
.slide-enter-to,
.slide-leave-from {
  opacity: 1;
  max-height: 600px;
}
</style>
