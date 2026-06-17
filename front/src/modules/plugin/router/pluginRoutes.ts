import type { RouteRecordRaw } from 'vue-router'

export const pluginRoutes: RouteRecordRaw[] = [
  {
    path: '/plugins',
    name: 'Plugins',
    component: () => import('../pages/PluginsPage.vue'),
    meta: { requiresAuth: true },
  },
]
