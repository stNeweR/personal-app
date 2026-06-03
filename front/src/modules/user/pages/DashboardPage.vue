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

onMounted(() => {
  if (!auth.user) {
    auth.fetchUser()
  }
  loadSessions()
  loadCalendarStatus()
  loadTodoistStatus()
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
          Сегодня сессий пока нет. Запустите их пройдя в
          <router-link to="/pomodoro" class="text-blue-600 hover:underline">таймер</router-link>
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
