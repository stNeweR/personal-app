import type { RouteRecordRaw } from 'vue-router'
import LoginPage from '../pages/LoginPage.vue'
import RegisterPage from '../pages/RegisterPage.vue'
import DashboardPage from '../pages/DashboardPage.vue'
import PluginsPage from '../pages/PluginsPage.vue'
import TimerPage from '@/modules/pomodoro/pages/TimerPage.vue'

export const authRoutes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'login',
    component: LoginPage,
    meta: { guestOnly: true },
  },
  {
    path: '/register',
    name: 'register',
    component: RegisterPage,
    meta: { guestOnly: true },
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: DashboardPage,
    meta: { requiresAuth: true },
  },
  {
    path: '/plugins',
    name: 'plugins',
    component: PluginsPage,
    meta: { requiresAuth: true },
  },
  {
    path: '/timer',
    name: 'timer',
    component: TimerPage,
    meta: { requiresAuth: true, requiresPomodoroSettings: true },
  },
]
