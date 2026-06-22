<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/authStore'
import { getTodaySessions } from '@/modules/pomodoro/api/sessions'
import { usePomodoroStore } from '@/modules/pomodoro/stores/pomodoroStore'
import PomodoroSettingsModal from '@/modules/pomodoro/components/PomodoroSettingsModal.vue'
import type { PomodoroSettings } from '@/modules/pomodoro/types/settings'
import type { PomodoroSession } from '@/modules/pomodoro/types/session'
import type { Plan } from '../types/auth'
import { pluginRegistry } from '@/shared/plugins/PluginRegistry'

const router = useRouter()
const auth = useAuthStore()

const sessions = ref<PomodoroSession[]>([])
const sessionsLoading = ref(false)
const sessionsError = ref<string | null>(null)

const pomodoro = usePomodoroStore()
const showSettings = ref(false)
const settingsChecked = ref(false)

const pluginWidgets = computed(() => pluginRegistry.getWidgets())

const planOptions: { value: Plan; label: string; description: string; icon: string }[] = [
  { value: 'junior', label: 'Junior', description: 'Только помодоро таймер', icon: '🌱' },
  { value: 'middle', label: 'Middle', description: 'До 2 плагинов', icon: '⚡' },
  { value: 'senior', label: 'Senior', description: 'Безлимитные плагины', icon: '🚀' },
]

const changingPlan = ref(false)

async function handleChangePlan(plan: Plan): Promise<void> {
  if (auth.user?.plan === plan) return

  const planLabel = planOptions.find(p => p.value === plan)?.label ?? plan
  if (!confirm(`Переключиться на план ${planLabel}?${plan === 'junior' ? '\n\nАктивные плагины будут отключены. Данные плагинов сохранятся.' : ''}`)) {
    return
  }

  changingPlan.value = true
  try {
    await auth.changePlan(plan)
  } catch {
    // error is set in store
  } finally {
    changingPlan.value = false
  }
}

onMounted(async () => {
  if (!auth.user) {
    auth.fetchUser()
  }
  loadSessions()
  await pomodoro.loadUserSettings()
  settingsChecked.value = true
  await pluginRegistry.loadPlugins()
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

function openPomodoroSettings(): void {
  showSettings.value = true
}

async function savePomodoroSettings(settings: PomodoroSettings): Promise<void> {
  try {
    await pomodoro.saveUserSettingsToBackend(settings)
    showSettings.value = false
  } catch {
    // error is already set in store
  }
}

function handleGoToTimer(): void {
  router.push('/timer')
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
          <router-link
            to="/plugins"
            class="px-4 py-1.5 rounded-lg bg-white/20 hover:bg-white/30 transition text-sm font-medium"
          >
            Плагины
          </router-link>
        </div>
      </div>
    </header>

    <!-- Content -->
    <main class="max-w-6xl mx-auto px-4 py-10 space-y-8">
      <!-- Plan Selector -->
      <div class="bg-white rounded-2xl shadow-lg p-8">
        <div class="mb-6">
          <h3 class="text-xl font-bold text-gray-800">📋 Ваш план: {{ auth.user?.plan ? planOptions.find(p => p.value === auth.user?.plan)?.label ?? auth.user.plan : 'Junior' }}</h3>
          <p class="text-sm text-gray-500 mt-1">
            Выберите план для управления доступом к плагинам
          </p>
        </div>

        <div v-if="auth.error" class="mb-4 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
          {{ auth.error }}
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div
            v-for="option in planOptions"
            :key="option.value"
            :class="[
              'rounded-xl p-5 border-2 transition cursor-pointer',
              auth.user?.plan === option.value
                ? 'border-accent-purple bg-accent-purple/5'
                : 'border-gray-200 hover:border-gray-300 bg-white',
            ]"
            @click="handleChangePlan(option.value)"
          >
            <div class="flex items-center justify-between mb-3">
              <span class="text-2xl">{{ option.icon }}</span>
              <span
                v-if="auth.user?.plan === option.value"
                class="px-2 py-0.5 rounded-full text-xs font-medium bg-accent-purple text-white"
              >
                Текущий
              </span>
            </div>
            <h4 class="text-lg font-bold text-gray-800 mb-1">{{ option.label }}</h4>
            <p class="text-sm text-gray-500">{{ option.description }}</p>
            <button
              v-if="auth.user?.plan !== option.value"
              :disabled="changingPlan"
              class="mt-4 w-full px-4 py-2 rounded-lg bg-gradient-to-r from-accent-purple to-accent-blue text-white text-sm font-medium shadow hover:opacity-90 transition disabled:opacity-50"
            >
              {{ changingPlan ? 'Смена...' : 'Выбрать' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Pomodoro Settings Section -->
      <div class="bg-white rounded-2xl shadow-lg p-8">
        <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
          <div>
            <h3 class="text-xl font-bold text-gray-800">🍅 Настройки помодоро</h3>
            <p class="text-sm text-gray-500 mt-1">
              Настройте таймер перед началом работы
            </p>
          </div>
          <button
            v-if="pomodoro.hasBackendSettings"
            @click="handleGoToTimer"
            class="px-4 py-2 rounded-lg bg-gradient-to-r from-accent-purple to-accent-blue text-white text-sm font-medium shadow hover:opacity-90 transition"
          >
            Перейти к таймеру
          </button>
        </div>

        <div
          v-if="settingsChecked && !pomodoro.hasBackendSettings"
          class="mb-6 px-4 py-3 rounded-lg bg-amber-50 border border-amber-200 text-sm text-amber-800"
        >
          Добавьте настройки помодоро, чтобы открыть страницу таймера.
        </div>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
          <div class="bg-gray-50 rounded-xl p-4 text-center">
            <div class="text-xs text-gray-500 mb-1">Работа</div>
            <div class="text-lg font-bold text-gray-800">{{ pomodoro.settings.workTime }} мин</div>
          </div>
          <div class="bg-gray-50 rounded-xl p-4 text-center">
            <div class="text-xs text-gray-500 mb-1">Перерыв</div>
            <div class="text-lg font-bold text-gray-800">{{ pomodoro.settings.breakTime }} мин</div>
          </div>
          <div class="bg-gray-50 rounded-xl p-4 text-center">
            <div class="text-xs text-gray-500 mb-1">Длинный перерыв</div>
            <div class="text-lg font-bold text-gray-800">{{ pomodoro.settings.longBreakTime }} мин</div>
          </div>
          <div class="bg-gray-50 rounded-xl p-4 text-center">
            <div class="text-xs text-gray-500 mb-1">До длинного</div>
            <div class="text-lg font-bold text-gray-800">{{ pomodoro.settings.sessionsBeforeLongBreak }}</div>
          </div>
          <div class="bg-gray-50 rounded-xl p-4 text-center">
            <div class="text-xs text-gray-500 mb-1">Всего помодоро</div>
            <div class="text-lg font-bold text-gray-800">{{ pomodoro.settings.totalPomodoros }}</div>
          </div>
        </div>

        <button
          @click="openPomodoroSettings"
          class="px-5 py-2.5 rounded-lg bg-gradient-to-r from-accent-purple to-accent-blue text-white text-sm font-medium shadow hover:opacity-90 transition"
        >
          {{ pomodoro.hasBackendSettings ? 'Изменить настройки' : 'Добавить настройки' }}
        </button>
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

        <div
          v-if="sessions.length === 0 && !sessionsLoading"
          class="text-gray-500 text-center py-8"
        >
          Сегодня сессий пока нет. Запустите таймер на странице помодоро.
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

      <!-- Dynamic Plugin Widgets -->
      <div v-for="widget in pluginWidgets" :key="widget.name">
        <component :is="widget.component" />
      </div>

    </main>

    <PomodoroSettingsModal
      v-model="showSettings"
      :settings="pomodoro.settings"
      @save="savePomodoroSettings"
    />
  </div>
</template>
