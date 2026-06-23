import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/modules/user/stores/authStore'
import { usePomodoroStore } from '@/modules/pomodoro/stores/pomodoroStore'
import { authRoutes } from '@/modules/user/router/authRoutes'
import { pomodoroRoutes } from '@/modules/pomodoro/router/pomodoroRoutes'
import { pluginRoutes } from '@/modules/plugin/router/pluginRoutes'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: '/dashboard',
    },
    ...authRoutes,
    ...pomodoroRoutes,
    ...pluginRoutes,
  ],
})

router.beforeEach(async (to, from, next) => {
  const auth = useAuthStore()

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    next('/login')
    return
  }

  if (to.meta.guestOnly && auth.isAuthenticated) {
    next('/dashboard')
    return
  }

  if (to.meta.requiresPomodoroSettings && auth.isAuthenticated) {
    const pomodoro = usePomodoroStore()
    if (!pomodoro.hasBackendSettings) {
      await pomodoro.loadUserSettings()
      if (!pomodoro.hasBackendSettings) {
        next('/dashboard')
        return
      }
    }
  }

  next()
})

export default router
