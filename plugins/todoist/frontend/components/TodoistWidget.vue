<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
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
import type { TodoistTask } from '../api/todoist'

const connected = ref(false)
const loading = ref(false)
const tasks = ref<TodoistTask[]>([])
const error = ref<string | null>(null)
const showConnectForm = ref(false)
const apiToken = ref('')
const connectLoading = ref(false)

const showCreateForm = ref(false)
const newContent = ref('')
const newDescription = ref('')
const newPriority = ref(1)
const createLoading = ref(false)
const mutatingTaskId = ref<string | null>(null)

const activeCount = computed(() => tasks.value.filter((t) => !t.checked).length)
const completedCount = computed(() => tasks.value.filter((t) => t.checked).length)

onMounted(async () => {
  await loadStatus()
})

async function loadStatus() {
  try {
    const status = await getTodoistStatus()
    connected.value = status.connected
    if (status.connected) {
      await loadTasks()
    }
  } catch {
    // ignore
  }
}

async function handleConnect() {
  if (!apiToken.value.trim()) return
  connectLoading.value = true
  error.value = null
  try {
    await connectTodoist({ api_token: apiToken.value.trim() })
    showConnectForm.value = false
    apiToken.value = ''
    await loadStatus()
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to connect Todoist'
  } finally {
    connectLoading.value = false
  }
}

async function handleDisconnect() {
  connectLoading.value = true
  error.value = null
  try {
    await disconnectTodoist()
    tasks.value = []
    connected.value = false
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to disconnect Todoist'
  } finally {
    connectLoading.value = false
  }
}

async function loadTasks() {
  loading.value = true
  error.value = null
  try {
    const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone
    const res = await getTodoistTasks(timezone)
    tasks.value = res.data
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to load tasks'
  } finally {
    loading.value = false
  }
}

async function handleCreateTask() {
  if (!newContent.value.trim()) return
  createLoading.value = true
  error.value = null
  try {
    await createTodoistTask({
      content: newContent.value.trim(),
      description: newDescription.value.trim() || undefined,
      priority: newPriority.value,
    })
    newContent.value = ''
    newDescription.value = ''
    newPriority.value = 1
    showCreateForm.value = false
    await loadTasks()
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to create task'
  } finally {
    createLoading.value = false
  }
}

async function handleCompleteTask(task: TodoistTask) {
  mutatingTaskId.value = task.id
  error.value = null
  try {
    await completeTodoistTask(task.id)
    await loadTasks()
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to complete task'
  } finally {
    mutatingTaskId.value = null
  }
}

async function handleReopenTask(task: TodoistTask) {
  mutatingTaskId.value = task.id
  error.value = null
  try {
    await reopenTodoistTask(task.id)
    await loadTasks()
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to reopen task'
  } finally {
    mutatingTaskId.value = null
  }
}

async function handleDeleteTask(task: TodoistTask) {
  if (!confirm(`Удалить задачу «${task.content}»?`)) return
  mutatingTaskId.value = task.id
  error.value = null
  try {
    await deleteTodoistTask(task.id)
    await loadTasks()
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to delete task'
  } finally {
    mutatingTaskId.value = null
  }
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
  <div class="bg-white rounded-2xl shadow-lg p-8">
    <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
      <div>
        <h3 class="text-xl font-bold text-gray-800">Задачи Todoist</h3>
        <p v-if="connected" class="text-xs text-gray-500 mt-1">
          Активных: {{ activeCount }} · Завершённых: {{ completedCount }}
        </p>
      </div>
      <div class="flex items-center gap-2">
        <button
          v-if="!connected"
          @click="showConnectForm = !showConnectForm"
          class="px-4 py-2 rounded-lg bg-gradient-to-r from-rose-500 to-pink-500 text-white text-sm font-medium shadow hover:opacity-90 transition"
        >
          Подключить Todoist
        </button>
        <template v-else>
          <button
            @click="showCreateForm = !showCreateForm"
            class="px-4 py-2 rounded-lg bg-gradient-to-r from-rose-500 to-pink-500 text-white text-sm font-medium shadow hover:opacity-90 transition"
          >
            {{ showCreateForm ? 'Скрыть' : 'Новая задача' }}
          </button>
          <button
            @click="loadTasks"
            :disabled="loading"
            class="px-4 py-2 rounded-lg bg-gradient-to-r from-accent-purple to-accent-blue text-white text-sm font-medium shadow hover:opacity-90 transition disabled:opacity-50"
          >
            {{ loading ? 'Загрузка...' : 'Обновить' }}
          </button>
          <button
            @click="handleDisconnect"
            :disabled="connectLoading"
            class="px-3 py-2 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300 transition disabled:opacity-50"
          >
            Отключить
          </button>
        </template>
      </div>
    </div>

    <!-- Connect Form -->
    <div
      v-if="showConnectForm && !connected"
      class="mb-6 bg-gray-50 rounded-xl p-6"
    >
      <h4 class="font-semibold text-gray-800 mb-4">Подключение Todoist</h4>
      <div class="space-y-3 max-w-md">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">API токен</label>
          <input
            v-model="apiToken"
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
            >настройках интеграций Todoist</a>
          </p>
        </div>
        <div class="flex gap-3">
          <button
            @click="handleConnect"
            :disabled="connectLoading || !apiToken"
            class="px-4 py-2 rounded-lg bg-gradient-to-r from-rose-500 to-pink-500 text-white text-sm font-medium shadow hover:opacity-90 transition disabled:opacity-50"
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

    <!-- Create Form -->
    <div
      v-if="showCreateForm && connected"
      class="mb-6 bg-gray-50 rounded-xl p-6"
    >
      <h4 class="font-semibold text-gray-800 mb-4">Новая задача</h4>
      <div class="space-y-3 max-w-md">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Содержание</label>
          <input
            v-model="newContent"
            type="text"
            placeholder="Что нужно сделать?"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-rose-500"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Описание (необязательно)</label>
          <textarea
            v-model="newDescription"
            rows="2"
            placeholder="Дополнительные детали"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-rose-500"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Приоритет</label>
          <select
            v-model.number="newPriority"
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
            @click="handleCreateTask"
            :disabled="createLoading || !newContent.trim()"
            class="px-4 py-2 rounded-lg bg-gradient-to-r from-rose-500 to-pink-500 text-white text-sm font-medium shadow hover:opacity-90 transition disabled:opacity-50"
          >
            {{ createLoading ? 'Создание...' : 'Создать' }}
          </button>
          <button
            @click="showCreateForm = false"
            class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300 transition"
          >
            Отмена
          </button>
        </div>
      </div>
    </div>

    <div v-if="error" class="text-red-500 text-sm mb-4">{{ error }}</div>

    <div
      v-if="!connected && !showConnectForm"
      class="text-gray-500 text-center py-8"
    >
      Подключите Todoist, чтобы видеть и управлять задачами.
    </div>

    <div
      v-else-if="connected && tasks.length === 0 && !loading"
      class="text-gray-500 text-center py-8"
    >
      Задач пока нет. Создайте первую!
    </div>

    <div v-else-if="tasks.length > 0" class="space-y-2">
      <div
        v-for="task in tasks"
        :key="task.id"
        class="border border-gray-100 rounded-xl p-4 hover:bg-gray-50 transition flex items-start gap-3"
        :class="task.checked ? 'opacity-60' : ''"
      >
        <input
          type="checkbox"
          :checked="task.checked"
          :disabled="mutatingTaskId === task.id"
          @change="task.checked ? handleReopenTask(task) : handleCompleteTask(task)"
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
                @click="handleDeleteTask(task)"
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
</template>
