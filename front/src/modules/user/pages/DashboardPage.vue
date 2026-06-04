<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/authStore'
import { getTodaySessions } from '@/modules/pomodoro/api/sessions'
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
import { usePlaylistStore } from '../stores/playlistStore'
import { useNotificationStore } from '../stores/notificationStore'
import { usePomodoroStore } from '@/modules/pomodoro/stores/pomodoroStore'
import PomodoroSettingsModal from '@/modules/pomodoro/components/PomodoroSettingsModal.vue'
import type { PomodoroSettings } from '@/modules/pomodoro/types/settings'
import type { PomodoroSession } from '@/modules/pomodoro/types/session'
import type { CalendarEvent } from '../api/calendar'
import type { TodoistTask } from '../api/todoist'

const router = useRouter()
const auth = useAuthStore()

const sessions = ref<PomodoroSession[]>([])
const sessionsLoading = ref(false)
const sessionsError = ref<string | null>(null)

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

const playlist = usePlaylistStore()
const playlistInput = ref('')

const notifications = useNotificationStore()
const emailInput = ref('')

const channelOptions: { value: 'telegram' | 'email' | null; label: string }[] = [
  { value: null, label: 'Не получать' },
  { value: 'telegram', label: 'Telegram' },
  { value: 'email', label: 'Email' },
]

const pomodoro = usePomodoroStore()
const showSettings = ref(false)
const settingsChecked = ref(false)

const pomodoroStatusLabel = computed(() => {
  const map: Record<string, string> = {
    idle: 'Готов к работе',
    work: 'Работа',
    break: 'Перерыв',
    long_break: 'Длинный перерыв',
    paused: 'Пауза',
    finished: 'Сессия завершена',
  }
  return map[pomodoro.status] || pomodoro.status
})

const pomodoroStatusColor = computed(() => {
  const map: Record<string, string> = {
    idle: 'text-gray-600',
    work: 'text-green-600',
    break: 'text-blue-600',
    long_break: 'text-indigo-600',
    paused: 'text-yellow-600',
    finished: 'text-purple-600',
  }
  return map[pomodoro.status] || 'text-gray-600'
})

const pomodoroStatusBg = computed(() => {
  const map: Record<string, string> = {
    idle: 'bg-gray-100',
    work: 'bg-green-100',
    break: 'bg-blue-100',
    long_break: 'bg-indigo-100',
    paused: 'bg-yellow-100',
    finished: 'bg-purple-100',
  }
  return map[pomodoro.status] || 'bg-gray-100'
})

const pomodoroProgressPercent = computed(() => {
  if (pomodoro.currentDuration === 0) return 0
  return ((pomodoro.currentDuration - pomodoro.timeLeft) / pomodoro.currentDuration) * 100
})

const isLongBreakNext = computed(() => {
  const nextSession = pomodoro.completedSessions + 1
  if (nextSession >= pomodoro.settings.totalPomodoros) return false
  return nextSession % pomodoro.settings.sessionsBeforeLongBreak === 0
})

const canStartPomodoro = computed(
  () => settingsChecked.value && pomodoro.hasBackendSettings,
)

onMounted(async () => {
  if (!auth.user) {
    auth.fetchUser()
  }
  loadSessions()
  loadCalendarStatus()
  loadTodoistStatus()
  await playlist.load()
  if (playlist.url !== null) {
    playlistInput.value = playlist.url
  }
  await notifications.load()
  emailInput.value = notifications.email.address ?? ''
  const params = new URLSearchParams(window.location.search)
  const ev = params.get('email_verified')
  if (ev === '1') {
    notifications.lastFlash = { type: 'success', message: 'Email успешно подтверждён' }
    window.history.replaceState({}, '', window.location.pathname)
  } else if (ev === '0') {
    notifications.lastFlash = { type: 'error', message: 'Не удалось подтвердить email — ссылка недействительна' }
    window.history.replaceState({}, '', window.location.pathname)
  }
  await pomodoro.loadUserSettings()
  await pomodoro.restoreSession()
  settingsChecked.value = true
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
}

async function handleSavePlaylist(): Promise<void> {
  const url = playlistInput.value.trim()
  if (url === '') return
  try {
    await playlist.save(url)
    playlistInput.value = playlist.url ?? url
  } catch {
    // ошибка уже в playlist.error
  }
}

async function handleClearPlaylist(): Promise<void> {
  if (!confirm('Удалить сохранённый плейлист?')) return
  try {
    await playlist.clear()
    playlistInput.value = ''
  } catch {
    // ошибка уже в playlist.error
  }
}

async function handleGenerateTelegramToken(): Promise<void> {
  try {
    await notifications.generateTelegramToken()
  } catch {
    // ошибка уже в notifications.error
  }
}

async function handleRefreshNotifications(): Promise<void> {
  await notifications.load()
  emailInput.value = notifications.email.address ?? ''
}

async function handleSetChannel(next: 'telegram' | 'email' | null): Promise<void> {
  try {
    await notifications.setChannel(next)
  } catch {
    // ошибка уже в notifications.error
  }
}

async function handleSaveEmail(): Promise<void> {
  const trimmed = emailInput.value.trim()
  if (trimmed === '') return
  try {
    await notifications.setEmail(trimmed)
  } catch {
    // ошибка уже в notifications.error
  }
}

async function handleSendEmailVerification(): Promise<void> {
  try {
    await notifications.sendVerification()
  } catch {
    // ошибка уже в notifications.error
  }
}

async function handleDisconnectTelegram(): Promise<void> {
  if (!confirm('Отвязать Telegram?')) return
  try {
    await notifications.disconnectTelegramAccount()
  } catch {
    // ошибка уже в notifications.error
  }
}

async function handleToggleTimer(): Promise<void> {
  await pomodoro.toggle()
}

async function handleResetTimer(): Promise<void> {
  await pomodoro.reset()
}

function handleSkipPhase(): void {
  pomodoro.skipPhase()
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

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}

function formatDate(dateStr: string | null): string {
  if (!dateStr) return '—'
  const date = new Date(dateStr)
  return date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
}

function formatEventTime(dateStr: string): string {
  // iCalendar datetime format: YYYYMMDDTHHMMSS or YYYYMMDDTHHMMSSZ
  const match = dateStr.match(/T(\d{2})(\d{2})/)
  if (match) {
    return `${match[1]}:${match[2]}`
  }
  // Fallback to native parsing for ISO strings
  const date = new Date(dateStr)
  if (!isNaN(date.getTime())) {
    return date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })
  }
  return dateStr
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

function priorityLabel(priority: number): string {
  const map: Record<number, string> = {
    1: 'Обычный',
    2: 'Средний',
    3: 'Высокий',
    4: 'Срочный',
  }
  return map[priority] || `P${priority}`
}

function priorityColor(priority: number): string {
  const map: Record<number, string> = {
    1: 'text-gray-600 bg-gray-50',
    2: 'text-blue-600 bg-blue-50',
    3: 'text-orange-600 bg-orange-50',
    4: 'text-red-600 bg-red-50',
  }
  return map[priority] || 'text-gray-600 bg-gray-50'
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
      <!-- Pomodoro Timer Section -->
      <div class="bg-white rounded-2xl shadow-lg p-8">
        <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
          <h3 class="text-xl font-bold text-gray-800">🍅 Помодоро</h3>
          <div v-if="pomodoro.isUsingSessionSettings" class="text-sm text-indigo-600">
            Используются настройки текущей сессии
          </div>
        </div>

        <div class="max-w-md mx-auto">
          <div class="text-center mb-6">
            <span
              class="inline-block px-4 py-1 rounded-full text-sm font-semibold"
              :class="[pomodoroStatusColor, pomodoroStatusBg]"
            >
              {{ pomodoroStatusLabel }}
            </span>
          </div>

          <div class="text-7xl font-mono font-bold text-gray-800 tracking-tight text-center mb-2">
            {{ pomodoro.formattedTimeLeft }}
          </div>

          <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden mb-6">
            <div
              class="h-full bg-gradient-to-r from-accent-purple to-accent-blue transition-all duration-1000 ease-linear rounded-full"
              :style="{ width: `${pomodoroProgressPercent}%` }"
            />
          </div>

          <div class="flex items-center justify-center gap-6 mb-6 text-sm text-gray-500">
            <span>
              Сессий:
              <strong class="text-gray-700">{{ pomodoro.completedSessions }}</strong>
            </span>
            <span v-if="pomodoro.isSessionFinished" class="text-purple-600 font-medium">
              Все помодоро завершены!
            </span>
            <span v-else-if="pomodoro.currentPomodoro > 0">
              Помодоро
              <strong class="text-gray-700"
                >{{ pomodoro.currentPomodoro }} из
                {{ pomodoro.settings.totalPomodoros }}</strong
              >
            </span>
            <span
              v-if="
                !pomodoro.isSessionFinished &&
                pomodoro.status !== 'work' &&
                pomodoro.status !== 'paused'
              "
            >
              Следующий:
              <strong class="text-gray-700">
                {{ isLongBreakNext ? 'Длинный перерыв' : 'Перерыв' }}
              </strong>
            </span>
          </div>

          <div class="flex items-center justify-center gap-4 mb-6">
            <button
              @click="handleResetTimer"
              class="p-3 rounded-2xl bg-gray-100 text-gray-600 hover:bg-gray-200 transition"
              title="Сбросить"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                />
              </svg>
            </button>

            <button
              @click="handleToggleTimer"
              :disabled="!canStartPomodoro && pomodoro.status === 'idle'"
              class="px-8 py-3 rounded-2xl bg-gradient-to-r from-accent-purple to-accent-blue text-white text-base font-bold shadow-lg hover:opacity-90 transition flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg
                v-if="!pomodoro.isRunning"
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"
                />
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
              <svg
                v-else
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
              {{
                pomodoro.isRunning
                  ? 'Пауза'
                  : pomodoro.status === 'paused'
                    ? 'Продолжить'
                    : pomodoro.isSessionFinished
                      ? 'Начать заново'
                      : 'Старт'
              }}
            </button>

            <button
              @click="handleSkipPhase"
              class="p-3 rounded-2xl bg-gray-100 text-gray-600 hover:bg-gray-200 transition"
              title="Пропустить фазу"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M13 5l7 7-7 7M5 5l7 7-7 7"
                />
              </svg>
            </button>
          </div>

          <div class="bg-gray-50 rounded-xl p-4">
            <div
              v-if="settingsChecked && !pomodoro.hasBackendSettings"
              class="mb-3 px-3 py-2 rounded-lg bg-amber-50 border border-amber-200 text-sm text-amber-800"
            >
              Добавьте настройки помодоро, чтобы запустить таймер.
            </div>
            <div class="flex items-center justify-between mb-3">
              <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">
                Настройки
              </h4>
              <button
                @click="openPomodoroSettings"
                class="text-sm font-medium text-accent-purple hover:text-accent-blue transition"
              >
                {{ pomodoro.hasBackendSettings ? 'Изменить' : 'Добавить' }}
              </button>
            </div>
            <div class="grid grid-cols-2 gap-3 text-sm">
              <div class="flex items-center justify-between">
                <span class="text-gray-600">Работа</span>
                <span class="font-semibold text-gray-800">{{ pomodoro.settings.workTime }} мин</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-gray-600">Перерыв</span>
                <span class="font-semibold text-gray-800">
                  {{ pomodoro.settings.breakTime }} мин
                </span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-gray-600">Длинный перерыв</span>
                <span class="font-semibold text-gray-800">
                  {{ pomodoro.settings.longBreakTime }} мин
                </span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-gray-600">До длинного</span>
                <span class="font-semibold text-gray-800">
                  {{ pomodoro.settings.sessionsBeforeLongBreak }}
                </span>
              </div>
              <div class="flex items-center justify-between col-span-2">
                <span class="text-gray-600">Всего помодоро</span>
                <span class="font-semibold text-gray-800">
                  {{ pomodoro.settings.totalPomodoros }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

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
        </div>

        <!-- Connect Form -->
        <div
          v-if="showConnectForm && !calendarStatus?.connected"
          class="mb-6 bg-gray-50 rounded-xl p-6"
        >
          <h4 class="font-semibold text-gray-800 mb-4">Подключение Яндекс Календаря</h4>
          <div class="space-y-3 max-w-md">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Email Яндекса</label>
              <input
                v-model="yandexEmail"
                type="email"
                placeholder="your@yandex.ru"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Пароль приложения</label>
              <input
                v-model="yandexPassword"
                type="password"
                placeholder="Введите пароль приложения"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"
              />
              <p class="text-xs text-gray-500 mt-1">
                Создайте пароль приложения в
                <a
                  href="https://id.yandex.ru/security/app-passwords"
                  target="_blank"
                  class="text-blue-600 hover:underline"
                  >настройках безопасности Яндекса</a
                >
              </p>
            </div>
            <div class="flex gap-3">
              <button
                @click="handleConnectYandex"
                :disabled="connectLoading || !yandexEmail || !yandexPassword"
                class="px-4 py-2 rounded-lg bg-gradient-to-r from-emerald-500 to-teal-500 text-white text-sm font-medium shadow hover:opacity-90 transition disabled:opacity-50"
              >
                {{ connectLoading ? 'Подключение...' : 'Подключить' }}
              </button>
              <button
                @click="showConnectForm = false"
                class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300 transition"
              >
                Отмена
              </button>
            </div>
          </div>
        </div>

        <div v-if="calendarError" class="text-red-500 text-sm mb-4">{{ calendarError }}</div>

        <div
          v-if="!calendarStatus || (!calendarStatus.connected && !showConnectForm)"
          class="text-gray-500 text-center py-8"
        >
          Подключите Яндекс Календарь, чтобы увидеть расписание на сегодня.
        </div>

        <div
          v-else-if="calendarEvents.length === 0 && !calendarLoading && calendarStatus?.connected"
          class="text-gray-500 text-center py-8"
        >
          На сегодня событий нет.
        </div>

        <div v-else-if="calendarEvents.length > 0" class="space-y-3">
          <div
            v-for="event in calendarEvents"
            :key="event.id"
            class="border border-gray-100 rounded-xl p-4 hover:bg-gray-50 transition"
          >
            <div class="flex items-start justify-between gap-4">
              <div>
                <h4 class="font-semibold text-gray-800">{{ event.summary || 'Без названия' }}</h4>
                <p v-if="event.description" class="text-sm text-gray-500 mt-1">
                  {{ event.description }}
                </p>
              </div>
              <div class="text-sm text-gray-500 whitespace-nowrap">
                {{ formatEventTime(event.start) }} — {{ formatEventTime(event.end) }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Todoist Section -->
      <div class="bg-white rounded-2xl shadow-lg p-8">
        <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
          <div>
            <h3 class="text-xl font-bold text-gray-800">Задачи Todoist</h3>
            <p v-if="todoistConnected" class="text-xs text-gray-500 mt-1">
              Активных: {{ todoistActiveCount }} · Завершённых: {{ todoistCompletedCount }}
            </p>
          </div>
          <div class="flex items-center gap-2">
            <button
              v-if="!todoistConnected"
              @click="showTodoistConnectForm = !showTodoistConnectForm"
              class="px-4 py-2 rounded-lg bg-gradient-to-r from-rose-500 to-pink-500 text-white text-sm font-medium shadow hover:opacity-90 transition"
            >
              Подключить Todoist
            </button>
            <template v-else>
              <button
                @click="showCreateTodoistForm = !showCreateTodoistForm"
                class="px-4 py-2 rounded-lg bg-gradient-to-r from-rose-500 to-pink-500 text-white text-sm font-medium shadow hover:opacity-90 transition"
              >
                {{ showCreateTodoistForm ? 'Скрыть' : 'Новая задача' }}
              </button>
              <button
                @click="loadTodoistTasks"
                :disabled="todoistLoading"
                class="px-4 py-2 rounded-lg bg-gradient-to-r from-accent-purple to-accent-blue text-white text-sm font-medium shadow hover:opacity-90 transition disabled:opacity-50"
              >
                {{ todoistLoading ? 'Загрузка...' : 'Обновить' }}
              </button>
              <button
                @click="handleDisconnectTodoist"
                :disabled="todoistConnectLoading"
                class="px-3 py-2 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300 transition disabled:opacity-50"
              >
                Отключить
              </button>
            </template>
          </div>
        </div>

        <!-- Connect Form -->
        <div
          v-if="showTodoistConnectForm && !todoistConnected"
          class="mb-6 bg-gray-50 rounded-xl p-6"
        >
          <h4 class="font-semibold text-gray-800 mb-4">Подключение Todoist</h4>
          <div class="space-y-3 max-w-md">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">API токен</label>
              <input
                v-model="todoistApiToken"
                type="password"
                placeholder="Введите API токен Todoist"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-rose-500"
              />
              <p class="text-xs text-gray-500 mt-1">
                Получите токен в
                <a
                  href="https://todoist.com/app/settings/integrations/developer"
                  target="_blank"
                  class="text-blue-600 hover:underline"
                  >настройках интеграций Todoist</a
                >
              </p>
            </div>
            <div class="flex gap-3">
              <button
                @click="handleConnectTodoist"
                :disabled="todoistConnectLoading || !todoistApiToken"
                class="px-4 py-2 rounded-lg bg-gradient-to-r from-rose-500 to-pink-500 text-white text-sm font-medium shadow hover:opacity-90 transition disabled:opacity-50"
              >
                {{ todoistConnectLoading ? 'Подключение...' : 'Подключить' }}
              </button>
              <button
                @click="showTodoistConnectForm = false"
                class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300 transition"
              >
                Отмена
              </button>
            </div>
          </div>
        </div>

        <!-- Create Form -->
        <div
          v-if="showCreateTodoistForm && todoistConnected"
          class="mb-6 bg-gray-50 rounded-xl p-6"
        >
          <h4 class="font-semibold text-gray-800 mb-4">Новая задача</h4>
          <div class="space-y-3 max-w-md">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Содержание</label>
              <input
                v-model="newTaskContent"
                type="text"
                placeholder="Что нужно сделать?"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-rose-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1"
                >Описание (необязательно)</label
              >
              <textarea
                v-model="newTaskDescription"
                rows="2"
                placeholder="Дополнительные детали"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-rose-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Приоритет</label>
              <select
                v-model.number="newTaskPriority"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-rose-500"
              >
                <option :value="1">Обычный</option>
                <option :value="2">Средний</option>
                <option :value="3">Высокий</option>
                <option :value="4">Срочный</option>
              </select>
            </div>
            <div class="flex gap-3">
              <button
                @click="handleCreateTodoistTask"
                :disabled="createTaskLoading || !newTaskContent.trim()"
                class="px-4 py-2 rounded-lg bg-gradient-to-r from-rose-500 to-pink-500 text-white text-sm font-medium shadow hover:opacity-90 transition disabled:opacity-50"
              >
                {{ createTaskLoading ? 'Создание...' : 'Создать' }}
              </button>
              <button
                @click="showCreateTodoistForm = false"
                class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300 transition"
              >
                Отмена
              </button>
            </div>
          </div>
        </div>

        <div v-if="todoistError" class="text-red-500 text-sm mb-4">{{ todoistError }}</div>

        <div
          v-if="!todoistConnected && !showTodoistConnectForm"
          class="text-gray-500 text-center py-8"
        >
          Подключите Todoist, чтобы видеть и управлять задачами.
        </div>

        <div
          v-else-if="todoistConnected && todoistTasks.length === 0 && !todoistLoading"
          class="text-gray-500 text-center py-8"
        >
          Задач пока нет. Создайте первую!
        </div>

        <div v-else-if="todoistTasks.length > 0" class="space-y-2">
          <div
            v-for="task in todoistTasks"
            :key="task.id"
            class="border border-gray-100 rounded-xl p-4 hover:bg-gray-50 transition flex items-start gap-3"
            :class="task.checked ? 'opacity-60' : ''"
          >
            <input
              type="checkbox"
              :checked="task.checked"
              :disabled="mutatingTaskId === task.id"
              @change="
                task.checked ? handleReopenTodoistTask(task) : handleCompleteTodoistTask(task)
              "
              class="mt-1 w-5 h-5 rounded border-gray-300 text-rose-500 focus:ring-rose-500 cursor-pointer disabled:opacity-50"
            />
            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between gap-3 flex-wrap">
                <div class="min-w-0 flex-1">
                  <h4
                    class="font-medium text-gray-800"
                    :class="task.checked ? 'line-through text-gray-500' : ''"
                  >
                    {{ task.content }}
                  </h4>
                  <p v-if="task.description" class="text-sm text-gray-500 mt-1 whitespace-pre-line">
                    {{ task.description }}
                  </p>
                  <div class="flex items-center gap-2 mt-2 flex-wrap">
                    <span
                      class="inline-block px-2 py-0.5 rounded-full text-xs font-medium"
                      :class="priorityColor(task.priority)"
                    >
                      {{ priorityLabel(task.priority) }}
                    </span>
                    <span v-if="task.due" class="text-xs text-gray-500">
                      📅 {{ task.due.string || task.due.date || task.due.datetime }}
                    </span>
                    <span
                      v-for="label in task.labels"
                      :key="label"
                      class="inline-block px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600"
                    >
                      @{{ label }}
                    </span>
                  </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                  <a
                    v-if="task.url"
                    :href="task.url"
                    target="_blank"
                    rel="noopener"
                    class="text-xs text-blue-600 hover:underline"
                  >
                    Открыть
                  </a>
                  <button
                    @click="handleDeleteTodoistTask(task)"
                    :disabled="mutatingTaskId === task.id"
                    class="text-xs text-red-500 hover:text-red-700 disabled:opacity-50"
                  >
                    Удалить
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
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
          Сегодня сессий пока нет. Запустите таймер выше.
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

      <!-- Playlist Section -->
      <div class="bg-white rounded-2xl shadow-lg p-8">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-4">
          <div>
            <h3 class="text-xl font-bold text-gray-800">🎧 Плейлист для работы</h3>
            <p class="text-xs text-gray-500 mt-1">
              Ссылка появится в всплывающем окне при запуске помодоро
            </p>
          </div>
          <div v-if="playlist.url" class="flex items-center gap-2">
            <a
              :href="playlist.url"
              target="_blank"
              rel="noopener noreferrer"
              class="px-3 py-1.5 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200 transition"
            >
              Открыть
            </a>
            <button
              @click="handleClearPlaylist"
              :disabled="playlist.isSaving"
              class="px-3 py-1.5 rounded-lg bg-red-50 text-red-600 text-sm font-medium hover:bg-red-100 transition disabled:opacity-50"
            >
              Удалить
            </button>
          </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-2">
          <input
            v-model="playlistInput"
            type="url"
            placeholder="https://music.youtube.com/playlist?list=..."
            class="flex-1 px-4 py-2 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-accent-blue"
            :disabled="playlist.isSaving"
            @keyup.enter="handleSavePlaylist"
          />
          <button
            @click="handleSavePlaylist"
            :disabled="playlist.isSaving || playlistInput.trim() === ''"
            class="px-5 py-2 rounded-lg bg-gradient-to-r from-accent-purple to-accent-blue text-white text-sm font-medium shadow hover:opacity-90 transition disabled:opacity-50"
          >
            {{ playlist.isSaving ? 'Сохранение...' : playlist.url ? 'Обновить' : 'Сохранить' }}
          </button>
        </div>

        <div v-if="playlist.error" class="text-red-500 text-sm mt-3">
          {{ playlist.error }}
        </div>
      </div>

      <!-- Notifications Section -->
      <div class="bg-white rounded-2xl shadow-lg p-8">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-4">
          <div>
            <h3 class="text-xl font-bold text-gray-800">🔔 Уведомления</h3>
            <p class="text-xs text-gray-500 mt-1">
              Выберите, куда присылать уведомления о завершении фаз помодоро
            </p>
          </div>
          <button
            @click="handleRefreshNotifications"
            :disabled="notifications.isLoading"
            class="text-xs px-3 py-1.5 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition disabled:opacity-50"
            title="Обновить статус"
          >
            {{ notifications.isLoading ? 'Обновление...' : 'Обновить' }}
          </button>
        </div>

        <div
          v-if="notifications.lastFlash"
          :class="[
            'mb-4 px-4 py-3 rounded-lg text-sm flex items-center justify-between gap-3',
            notifications.lastFlash.type === 'success' && 'bg-emerald-50 text-emerald-700 border border-emerald-200',
            notifications.lastFlash.type === 'error' && 'bg-red-50 text-red-700 border border-red-200',
            notifications.lastFlash.type === 'info' && 'bg-sky-50 text-sky-700 border border-sky-200',
          ]"
        >
          <span>{{ notifications.lastFlash.message }}</span>
          <button
            @click="notifications.consumeFlash"
            class="text-xs opacity-70 hover:opacity-100"
          >
            ✕
          </button>
        </div>

        <!-- Channel selector -->
        <div class="mb-6">
          <p class="text-sm font-medium text-gray-700 mb-2">Канал уведомлений</p>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="option in channelOptions"
              :key="option.value ?? 'none'"
              @click="handleSetChannel(option.value)"
              :disabled="notifications.isMutating"
              :class="[
                'px-4 py-2 rounded-lg text-sm font-medium border transition disabled:opacity-50',
                notifications.channel === option.value
                  ? 'bg-gradient-to-r from-accent-purple to-accent-blue text-white border-transparent shadow'
                  : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
              ]"
            >
              {{ option.label }}
            </button>
          </div>
        </div>

        <!-- Telegram panel -->
        <div class="border-t border-gray-100 pt-5 mb-5">
          <div class="flex items-center justify-between mb-3">
            <h4 class="text-sm font-semibold text-gray-700">📱 Telegram</h4>
            <span
              v-if="notifications.telegram.linked"
              class="text-xs text-emerald-600 font-medium"
            >✓ Привязан</span>
            <span
              v-else
              class="text-xs text-gray-500"
            >Не привязан</span>
          </div>

          <div v-if="!notifications.telegram.linked">
            <button
              v-if="!notifications.linkToken"
              @click="handleGenerateTelegramToken"
              :disabled="notifications.isMutating"
              class="px-4 py-2 rounded-lg bg-gradient-to-r from-sky-500 to-blue-500 text-white text-sm font-medium shadow hover:opacity-90 transition disabled:opacity-50"
            >
              {{ notifications.isMutating ? 'Генерация...' : 'Привязать Telegram' }}
            </button>

            <div
              v-if="notifications.linkToken"
              class="bg-sky-50 border border-sky-200 rounded-xl p-4"
            >
              <p class="text-sm text-gray-700 mb-2">
                Откройте Telegram и отправьте боту
                <strong>@{{ notifications.linkToken.botName || 'bot' }}</strong> команду:
              </p>
              <code
                class="block bg-white px-3 py-2 rounded text-sm font-mono text-gray-800 select-all"
              >
                /start {{ notifications.linkToken.token }}
              </code>
              <p class="text-xs text-gray-500 mt-3">
                Токен действителен {{ notifications.linkToken.expiresInMinutes }} минут. После
                отправки команды нажмите «Обновить» выше.
              </p>
              <button
                @click="notifications.clearLinkToken"
                class="mt-2 text-xs text-gray-500 hover:text-gray-700 transition"
              >
                Скрыть
              </button>
            </div>
          </div>

          <button
            v-else
            @click="handleDisconnectTelegram"
            :disabled="notifications.isMutating"
            class="text-sm text-red-500 hover:text-red-700 transition disabled:opacity-50"
          >
            Отвязать Telegram
          </button>
        </div>

        <!-- Email panel -->
        <div class="border-t border-gray-100 pt-5">
          <div class="flex items-center justify-between mb-3">
            <h4 class="text-sm font-semibold text-gray-700">📧 Email</h4>
            <span
              v-if="notifications.email.verified"
              class="text-xs text-emerald-600 font-medium"
            >✓ Подтверждён</span>
            <span
              v-else-if="notifications.email.address"
              class="text-xs text-amber-600 font-medium"
            >Не подтверждён</span>
            <span
              v-else
              class="text-xs text-gray-500"
            >Не указан</span>
          </div>

          <div class="flex flex-col sm:flex-row gap-2">
            <input
              v-model="emailInput"
              type="email"
              placeholder="you@example.com"
              class="flex-1 px-4 py-2 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-accent-blue"
              :disabled="notifications.isMutating"
              @keyup.enter="handleSaveEmail"
            />
            <button
              @click="handleSaveEmail"
              :disabled="notifications.isMutating || emailInput.trim() === ''"
              class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200 transition disabled:opacity-50"
            >
              Сохранить
            </button>
            <button
              v-if="notifications.email.address && !notifications.email.verified"
              @click="handleSendEmailVerification"
              :disabled="notifications.isSendingVerification"
              class="px-4 py-2 rounded-lg bg-gradient-to-r from-accent-purple to-accent-blue text-white text-sm font-medium shadow hover:opacity-90 transition disabled:opacity-50"
            >
              {{ notifications.isSendingVerification ? 'Отправка...' : 'Отправить подтверждение' }}
            </button>
          </div>

          <p
            v-if="notifications.email.address && !notifications.email.verified"
            class="text-xs text-amber-600 mt-2"
          >
            Email не подтверждён. Пока вы не подтвердите почту, уведомления на email не будут отправляться.
          </p>
        </div>

        <div v-if="notifications.error" class="text-red-500 text-sm mt-3">
          {{ notifications.error }}
        </div>
      </div>
    </main>

    <PomodoroSettingsModal
      v-model="showSettings"
      :settings="pomodoro.settings"
      @save="savePomodoroSettings"
    />
  </div>
</template>
