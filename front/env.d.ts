/// <reference types="vite/client" />

import 'vue-router'

declare module '*.vue' {
  import type { DefineComponent } from 'vue'
  const component: DefineComponent<unknown, unknown, unknown>
  export default component
}

declare module 'vue-router' {
  interface RouteMeta {
    requiresAuth?: boolean
    guestOnly?: boolean
    requiresPomodoroSettings?: boolean
  }
}
