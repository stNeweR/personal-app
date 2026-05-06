<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/authStore'
import { generateTelegramLinkToken } from '../api/auth'
import { getTodaySessions } from '@/modules/pomodoro/api/sessions'
import type { PomodoroSession } from '@/modules/pomodoro/types/session'

const router = useRouter()
const auth = useAuthStore()

const sessions = ref<PomodoroSession[]>([])
const sessionsLoading = ref(false)
const sessionsError = ref<string | null>(null)

const linkLoading = ref(false)
const linkError = ref<string | null>(null)

onMounted(() => {
  if (!auth.user) {
    auth.fetchUser()
  }
  loadSessions()
})

async function loadSessions() {
  sessionsLoading.value = true
  sessionsError.value = null
  try {
    const response = await getTodaySessions()
    sessions.value = response.sessions
  } catch (e) {
    sessionsError.value = e instanceof Error ? e.message : 'Failed to load sessions'
  } finally {
    sessionsLoading.value = false
  }
}

async function handleLinkTelegram() {
  linkLoading.value = true
  linkError.value = null
  try {
    const response = await generateTelegramLinkToken()
    window.location.href = response.link_url
  } catch (e) {
    linkError.value = e instanceof Error ? e.message : 'Failed to generate link'
    linkLoading.value = false
  }
}

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}

function formatDate(dateStr: string | null): string {
  if (!dateStr) return '—'
  const date = new Date(dateStr)
  return date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
}

function statusLabel(status: string): string {
  const map: Record<string, string> = {
    work: 'Работа',
    break: 'Перерыв',
    long_break: 'Длинный перерыв',
    paused: 'Пауза',
    finished: 'Завершено',
  }
  return map[status] || status
}

function statusColor(status: string): string {
  const map: Record<string, string> = {
    work: 'text-green-600 bg-green-50',
    break: 'text-blue-600 bg-blue-50',
    long_break: 'text-indigo-600 bg-indigo-50',
    paused: 'text-yellow-600 bg-yellow-50',
    finished: 'text-gray-600 bg-gray-50',
  }
  return map[status] || 'text-gray-600 bg-gray-50'
}
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-gradient-to-r from-accent-purple to-accent-blue text-white shadow-lg">
      <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
        <h1 class="text-xl font-bold">Личный кабинет</h1>
        <div class="flex items-center gap-4">
          <span v-if="auth.user" class="text-sm opacity-90">
            {{ auth.user.name }} ({{ auth.user.email }})
          </span>
          <button
            @click="handleLogout"
            class="px-4 py-1.5 rounded-lg bg-white/20 hover:bg-white/30 transition text-sm font-medium"
          >
            Выйти
          </button>
        </div>
      </div>
    </header>

    <!-- Content -->
    <main class="max-w-6xl mx-auto px-4 py-10 space-y-8">
      <!-- Welcome -->
      <div class="bg-white rounded-2xl shadow-lg p-8">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-2xl font-bold text-gray-800">
            Добро пожаловать{{ auth.user ? ', ' + auth.user.name : '' }}!
          </h2>
          <button
            v-if="!auth.user?.telegram_id"
            @click="handleLinkTelegram"
            :disabled="linkLoading"
            class="px-4 py-2 rounded-lg bg-gradient-to-r from-accent-purple to-accent-blue text-white text-sm font-medium shadow hover:opacity-90 transition disabled:opacity-50"
          >
            {{ linkLoading ? 'Генерация...' : 'Привязать Telegram' }}
          </button>
          <span v-else class="text-sm text-green-600 font-medium">Telegram привязан ✅</span>
        </div>
        <p class="text-gray-600">
          Это ваш личный кабинет. Здесь отображается ваша персональная информация и статистика.
        </p>
        <p v-if="linkError" class="text-red-500 text-sm mt-2">{{ linkError }}</p>
      </div>

      <!-- Pomodoro Sessions -->
      <div class="bg-white rounded-2xl shadow-lg p-8">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-xl font-bold text-gray-800">Помодоро сессии за сегодня</h3>
          <button
            @click="loadSessions"
            :disabled="sessionsLoading"
            class="px-4 py-2 rounded-lg bg-gradient-to-r from-accent-purple to-accent-blue text-white text-sm font-medium shadow hover:opacity-90 transition disabled:opacity-50"
          >
            {{ sessionsLoading ? 'Загрузка...' : 'Обновить' }}
          </button>
        </div>

        <div v-if="sessionsError" class="text-red-500 text-sm mb-4">{{ sessionsError }}</div>

        <div v-if="sessions.length === 0 && !sessionsLoading" class="text-gray-500 text-center py-8">
          Сегодня сессий пока нет. Запустите таймер через Telegram бота!
        </div>

        <div v-else-if="sessions.length > 0" class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="py-3 px-4 text-sm font-semibold text-gray-600">#</th>
                <th class="py-3 px-4 text-sm font-semibold text-gray-600">Статус</th>
                <th class="py-3 px-4 text-sm font-semibold text-gray-600">Начало</th>
                <th class="py-3 px-4 text-sm font-semibold text-gray-600">Конец</th>
                <th class="py-3 px-4 text-sm font-semibold text-gray-600">Цикл</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(session, index) in sessions"
                :key="session.id"
                class="border-b border-gray-100 hover:bg-gray-50 transition"
              >
                <td class="py-3 px-4 text-sm text-gray-800">{{ index + 1 }}</td>
                <td class="py-3 px-4">
                  <span
                    class="inline-block px-2.5 py-0.5 rounded-full text-xs font-medium"
                    :class="statusColor(session.current_status)"
                  >
                    {{ statusLabel(session.current_status) }}
                  </span>
                </td>
                <td class="py-3 px-4 text-sm text-gray-700">{{ formatDate(session.start_at) }}</td>
                <td class="py-3 px-4 text-sm text-gray-700">{{ formatDate(session.end_at) }}</td>
                <td class="py-3 px-4 text-sm text-gray-700">{{ session.current_cycle }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</template>
