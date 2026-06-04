import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/modules/user/stores/authStore'
import { authRoutes } from '@/modules/user/router/authRoutes'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: '/dashboard',
    },
    ...authRoutes,
  ],
})

router.beforeEach((to, from, next) => {
  const auth = useAuthStore()

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    next('/login')
  } else if (to.meta.guestOnly && auth.isAuthenticated) {
    next('/dashboard')
  } else {
    next()
  }
})

export default router
