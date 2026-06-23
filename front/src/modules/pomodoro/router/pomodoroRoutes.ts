import type { RouteRecordRaw } from 'vue-router'
import PomodoroPage from '../pages/PomodoroPage.vue'

export const pomodoroRoutes: RouteRecordRaw[] = [
  {
    path: '/pomodoro',
    name: 'pomodoro',
    component: PomodoroPage,
    meta: { requiresAuth: true },
  },
]
