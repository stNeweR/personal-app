<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/authStore'
import { getTodaySessions } from '@/modules/pomodoro/api/sessions'
<<<<<<< HEAD
import { getCalendarStatus, connectYandexCalendar, getTodayEvents } from '../api/calendar'
import {
  getTodoistStatus,
  connectTodoist,
  disconnectTodoist,
  getTodoistTasks,
  createTodoistTask,
  completeTodoistTask,
  reopenTodoistTask,
  deleteTodoistTask,
} from '../api/todoist'
=======
import { usePomodoroStore } from '@/modules/pomodoro/stores/pomodoroStore'
import PomodoroSettingsModal from '@/modules/pomodoro/components/PomodoroSettingsModal.vue'
import type { PomodoroSettings } from '@/modules/pomodoro/types/settings'
>>>>>>> course
import type { PomodoroSession } from '@/modules/pomodoro/types/session'
import type { Plan } from '../types/auth'
import { pluginRegistry } from '@/shared/plugins/PluginRegistry'

const router = useRouter()
const auth = useAuthStore()

const sessions = ref<PomodoroSession[]>([])
const sessionsLoading = ref(false)
const sessionsError = ref<string | null>(null)

<<<<<<< HEAD
const calendarStatus = ref<{ connected: boolean } | null>(null)
const calendarLoading = ref(false)
const calendarEvents = ref<CalendarEvent[]>([])
const calendarError = ref<string | null>(null)

const showConnectForm = ref(false)
const yandexEmail = ref('')
const yandexPassword = ref('')
const connectLoading = ref(false)

const todoistConnected = ref(false)
const todoistLoading = ref(false)
const todoistTasks = ref<TodoistTask[]>([])
const todoistError = ref<string | null>(null)
const showTodoistConnectForm = ref(false)
const todoistApiToken = ref('')
const todoistConnectLoading = ref(false)

const showCreateTodoistForm = ref(false)
const newTaskContent = ref('')
const newTaskDescription = ref('')
const newTaskPriority = ref(1)
const createTaskLoading = ref(false)
const mutatingTaskId = ref<string | null>(null)

const todoistActiveCount = computed(() => todoistTasks.value.filter((t) => !t.checked).length)
const todoistCompletedCount = computed(() => todoistTasks.value.filter((t) => t.checked).length)

onMounted(() => {
=======
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
>>>>>>> course
  if (!auth.user) {
    auth.fetchUser()
  }
  loadSessions()
<<<<<<< HEAD
  loadCalendarStatus()
  loadTodoistStatus()
=======
  await pomodoro.loadUserSettings()
  settingsChecked.value = true
  await pluginRegistry.loadPlugins()
>>>>>>> course
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

<<<<<<< HEAD
async function loadCalendarStatus() {
  try {
    calendarStatus.value = await getCalendarStatus()
    if (calendarStatus.value?.connected) {
      await loadCalendarEvents()
    }
  } catch {
    // ignore
  }
}

async function handleConnectYandex() {
  connectLoading.value = true
  calendarError.value = null
  try {
    await connectYandexCalendar({
      email: yandexEmail.value,
      app_password: yandexPassword.value,
    })
    showConnectForm.value = false
    yandexEmail.value = ''
    yandexPassword.value = ''
    await loadCalendarStatus()
  } catch (e) {
    calendarError.value = e instanceof Error ? e.message : 'Failed to connect Yandex Calendar'
  } finally {
    connectLoading.value = false
  }
}

async function loadCalendarEvents() {
  calendarLoading.value = true
  calendarError.value = null
  try {
    const today = new Date().toLocaleDateString('en-CA')
    const res = await getTodayEvents(today)
    calendarEvents.value = res.data
  } catch (e) {
    calendarError.value = e instanceof Error ? e.message : 'Failed to load calendar'
  } finally {
    calendarLoading.value = false
  }
}

async function loadTodoistStatus() {
  try {
    const status = await getTodoistStatus()
    todoistConnected.value = status.connected
    if (status.connected) {
      await loadTodoistTasks()
    }
  } catch {
    // ignore
  }
}

async function handleConnectTodoist() {
  todoistConnectLoading.value = true
  todoistError.value = null
  try {
    await connectTodoist({ api_token: todoistApiToken.value })
    showTodoistConnectForm.value = false
    todoistApiToken.value = ''
    await loadTodoistStatus()
  } catch (e) {
    todoistError.value = e instanceof Error ? e.message : 'Failed to connect Todoist'
  } finally {
    todoistConnectLoading.value = false
  }
}

async function handleDisconnectTodoist() {
  todoistConnectLoading.value = true
  todoistError.value = null
  try {
    await disconnectTodoist()
    todoistTasks.value = []
    todoistConnected.value = false
  } catch (e) {
    todoistError.value = e instanceof Error ? e.message : 'Failed to disconnect Todoist'
  } finally {
    todoistConnectLoading.value = false
  }
}

async function loadTodoistTasks() {
  todoistLoading.value = true
  todoistError.value = null
  try {
    const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone
    const res = await getTodoistTasks(timezone)
    todoistTasks.value = res.data
  } catch (e) {
    todoistError.value = e instanceof Error ? e.message : 'Failed to load Todoist tasks'
  } finally {
    todoistLoading.value = false
  }
}

async function handleCreateTodoistTask() {
  if (!newTaskContent.value.trim()) return
  createTaskLoading.value = true
  todoistError.value = null
  try {
    await createTodoistTask({
      content: newTaskContent.value.trim(),
      description: newTaskDescription.value.trim() || undefined,
      priority: newTaskPriority.value,
    })
    newTaskContent.value = ''
    newTaskDescription.value = ''
    newTaskPriority.value = 1
    showCreateTodoistForm.value = false
    await loadTodoistTasks()
  } catch (e) {
    todoistError.value = e instanceof Error ? e.message : 'Failed to create task'
  } finally {
    createTaskLoading.value = false
  }
}

async function handleCompleteTodoistTask(task: TodoistTask) {
  mutatingTaskId.value = task.id
  todoistError.value = null
  try {
    await completeTodoistTask(task.id)
    await loadTodoistTasks()
  } catch (e) {
    todoistError.value = e instanceof Error ? e.message : 'Failed to complete task'
  } finally {
    mutatingTaskId.value = null
  }
}

async function handleReopenTodoistTask(task: TodoistTask) {
  mutatingTaskId.value = task.id
  todoistError.value = null
  try {
    await reopenTodoistTask(task.id)
    await loadTodoistTasks()
  } catch (e) {
    todoistError.value = e instanceof Error ? e.message : 'Failed to reopen task'
  } finally {
    mutatingTaskId.value = null
  }
}

async function handleDeleteTodoistTask(task: TodoistTask) {
  if (!confirm(`Удалить задачу «${task.content}»?`)) return
  mutatingTaskId.value = task.id
  todoistError.value = null
  try {
    await deleteTodoistTask(task.id)
    await loadTodoistTasks()
  } catch (e) {
    todoistError.value = e instanceof Error ? e.message : 'Failed to delete task'
  } finally {
    mutatingTaskId.value = null
  }
=======
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
>>>>>>> course
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
<<<<<<< HEAD
      <!-- Calendar Section -->
      <div class="bg-white rounded-2xl shadow-lg p-8">
        <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
          <h3 class="text-xl font-bold text-gray-800">Календарь на сегодня</h3>
          <div v-if="calendarStatus">
            <button
              v-if="!calendarStatus.connected"
              @click="showConnectForm = true"
              class="px-4 py-2 rounded-lg bg-gradient-to-r from-emerald-500 to-teal-500 text-white text-sm font-medium shadow hover:opacity-90 transition"
            >
              Подключить Яндекс Календарь
            </button>
            <button
              v-else
              @click="loadCalendarEvents"
              :disabled="calendarLoading"
              class="px-4 py-2 rounded-lg bg-gradient-to-r from-accent-purple to-accent-blue text-white text-sm font-medium shadow hover:opacity-90 transition disabled:opacity-50"
            >
              {{ calendarLoading ? 'Загрузка...' : 'Обновить' }}
            </button>
          </div>
=======
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
>>>>>>> course
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

      <!-- Navigation -->
      <div class="bg-white rounded-2xl shadow-lg p-8 flex justify-center gap-4 flex-wrap">
        <router-link
          to="/pomodoro"
          class="px-6 py-3 rounded-lg bg-gradient-to-r from-accent-purple to-accent-blue text-white text-base font-medium shadow hover:opacity-90 transition"
        >
          Запустить помодоро
        </router-link>
        <router-link
          to="/plugins"
          class="px-6 py-3 rounded-lg bg-gradient-to-r from-emerald-500 to-teal-500 text-white text-base font-medium shadow hover:opacity-90 transition"
        >
          Плагины
        </router-link>
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
<<<<<<< HEAD
          Сегодня сессий пока нет. Запустите их пройдя в
          <router-link to="/pomodoro" class="text-blue-600 hover:underline">таймер</router-link>
=======
          Сегодня сессий пока нет. Запустите таймер на странице помодоро.
>>>>>>> course
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
<<<<<<< HEAD
=======

      <!-- Dynamic Plugin Widgets -->
      <div v-for="widget in pluginWidgets" :key="widget.name">
        <component :is="widget.component" />
      </div>

>>>>>>> course
    </main>
  </div>
</template>
