import { defineStore } from 'pinia'
import { ref } from 'vue'

/**
 * App shell layout state. Drives the collapsible sidebar: on large screens the
 * sidebar pushes the content; on smaller resolutions it auto-collapses and the
 * hamburger reopens it as an overlay (see DashboardLayout). `isMobile` reflects
 * the current viewport so components can choose push-vs-overlay behavior.
 */
export const useLayoutStore = defineStore('layout', () => {
  const sidebarOpen = ref(true)
  const isMobile = ref(false)
  let initialized = false

  function toggleSidebar() {
    sidebarOpen.value = !sidebarOpen.value
  }

  function setSidebar(open: boolean) {
    sidebarOpen.value = open
  }

  /** Close the sidebar after navigation, but only when it overlays content. */
  function closeOnMobile() {
    if (isMobile.value) sidebarOpen.value = false
  }

  /**
   * Wire the viewport breakpoint once for the app's lifetime. Sets the initial
   * open/closed default and flips it only when crossing the breakpoint — so a
   * manual toggle within a breakpoint survives route changes (each page mounts
   * its own DashboardLayout, but this runs only the first time).
   */
  function initResponsive() {
    if (initialized || typeof window === 'undefined') return
    initialized = true
    const mq = window.matchMedia('(max-width: 1024px)')
    const apply = (matches: boolean) => {
      isMobile.value = matches
      sidebarOpen.value = !matches
    }
    apply(mq.matches)
    mq.addEventListener('change', (e) => apply(e.matches))
  }

  return { sidebarOpen, isMobile, toggleSidebar, setSidebar, closeOnMobile, initResponsive }
})
