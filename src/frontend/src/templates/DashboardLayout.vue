<script setup lang="ts">
import AppSidebar from '@/organisms/AppSidebar.vue';
import EnvBadge from '@/molecules/EnvBadge.vue';
import { useAuthStore } from '@/core/stores/auth.store';
import { useLayoutStore } from '@/core/stores/layout.store';
import { storeToRefs } from 'pinia';
import Button from 'primevue/button';
import { useRouter } from 'vue-router';

const auth = useAuthStore();
const router = useRouter();

// Collapsible sidebar. The store wires the viewport breakpoint once (auto
// collapse on small screens, push content on large); the hamburger toggles it
// and a manual toggle survives route changes.
const layout = useLayoutStore();
const { sidebarOpen, isMobile } = storeToRefs(layout);
layout.initResponsive();

async function logout() {
  await auth.logout();
  await router.push({ name: 'login' });
}
</script>

<template>
  <div class="layout" :class="{ 'layout--sidebar-collapsed': !sidebarOpen }">
    <AppSidebar />
    <div
      v-if="isMobile && sidebarOpen"
      class="layout__backdrop"
      @click="layout.setSidebar(false)"
    />
    <div class="layout__main">
      <header class="layout__topbar">
        <div class="layout__topbar-left">
          <Button
            v-tooltip.bottom="'Mostrar/ocultar menú'"
            icon="pi pi-bars"
            text
            rounded
            severity="secondary"
            size="small"
            class="layout__menu-toggle"
            aria-label="Mostrar u ocultar el menú"
            @click="layout.toggleSidebar()"
          />
        </div>
        <div class="layout__topbar-right">
          <!-- App-wide indicators (notifications, counters) go here. -->
          <EnvBadge />
          <button class="layout__user" type="button" @click="router.push({ name: 'account' })">
            <i class="pi pi-user" />
            <span>{{ auth.user?.name }}</span>
          </button>
          <Button
            v-tooltip.bottom="'Cerrar sesión'"
            icon="pi pi-sign-out"
            text
            rounded
            severity="secondary"
            size="small"
            aria-label="Cerrar sesión"
            @click="logout"
          />
        </div>
      </header>
      <main class="layout__content">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<style scoped>
.layout {
  display: flex;
  min-height: 100vh;
  /* Sticky topbar height — consumed by sub-page sticky headers (e.g. the
     product edit page) so they park right below the topbar. */
  --app-topbar-height: 57px;
}

.layout__main {
  flex: 1;
  /* Flex children default to min-width:auto and grow with wide content
     (tables); 0 lets the column shrink to the viewport. */
  min-width: 0;
  margin-left: 260px;
  display: flex;
  flex-direction: column;
  background: var(--app-surface-ground);
  transition: margin-left 0.2s ease;
}

/* Sidebar collapsed: it slides out (AppSidebar), content reclaims the space. */
.layout--sidebar-collapsed .layout__main {
  margin-left: 0;
}

.layout__topbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
  min-height: var(--app-topbar-height);
  padding: 12px 24px;
  background: var(--app-surface-card);
  border-bottom: 1px solid var(--app-surface-border);
  position: sticky;
  top: 0;
  z-index: 50;
}

.layout__topbar-left {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
}

.layout__topbar-right {
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Dim/lock the content behind the sidebar when it overlays on small screens. */
.layout__backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  z-index: 90;
}

/* On small screens the sidebar overlays content instead of pushing it, so the
   main column always spans full width regardless of the open/collapsed state. */
@media (max-width: 1024px) {
  .layout__main {
    margin-left: 0;
  }
}

.layout__user {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.875rem;
  color: var(--p-text-muted-color);
  background: transparent;
  border: 0;
  padding: 4px 8px;
  border-radius: 6px;
  cursor: pointer;
}
.layout__user:hover {
  background: var(--app-surface-ground);
  color: var(--p-text-color);
}

.layout__content {
  flex: 1;
  min-width: 0;
  padding: 24px;
}

@media (max-width: 640px) {
  .layout__topbar {
    padding: 8px 12px;
  }

  .layout__content {
    padding: 16px;
  }

  /* Keep the icon, drop the name: the topbar must fit a phone. */
  .layout__user span {
    display: none;
  }
}
</style>
