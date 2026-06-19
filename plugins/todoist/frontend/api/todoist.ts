import { ref } from 'vue'

const API_URL = import.meta.env.VITE_API_URL || ''

interface RequestOptions {
  method?: string
  headers?: Record<string, string>
  body?: unknown
}

async function apiClient<T>(endpoint: string, options: RequestOptions = {}): Promise<T> {
  const token = localStorage.getItem('token')

  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    ...options.headers,
  }

  if (token) {
    headers['Authorization'] = `Bearer ${token}`
  }

  const response = await fetch(`${API_URL}${endpoint}`, {
    method: options.method || 'GET',
    headers,
    body: options.body ? JSON.stringify(options.body) : null,
  })

  if (!response.ok) {
    const error = await response.json().catch(() => ({}))
    throw new Error(error.message || `HTTP ${response.status}`)
  }

  return response.json() as Promise<T>
}

export interface TodoistStatus {
  connected: boolean
}

export interface TodoistTask {
  id: string
  content: string
  description: string
  checked: boolean
  priority: number
  project_id: string
  section_id: string
  parent_id: string
  order: number
  labels: string[]
  due?: {
    date: string
    datetime: string
    string: string
    timezone: string
    is_recurring: boolean
  } | null
  url: string
  comment_count: number
  created_at: string
  creator_id: string
  assignee_id: string
  assigner_id: string
}

export interface TodoistTasksResponse {
  data: TodoistTask[]
}

export interface TodoistTaskResponse {
  data: TodoistTask
}

export interface TodoistMutationResponse {
  data: {
    id: string
    [key: string]: string
  }
}

export interface ConnectTodoistPayload {
  api_token: string
}

export interface CreateTodoistTaskPayload {
  content: string
  description?: string
  priority?: number
}

export function getTodoistStatus(): Promise<TodoistStatus> {
  return apiClient<TodoistStatus>('/api/v1/todoist/status')
}

export function connectTodoist(payload: ConnectTodoistPayload): Promise<{ message: string }> {
  return apiClient<{ message: string }>('/api/v1/todoist/connect', {
    method: 'POST',
    body: payload,
  })
}

export function disconnectTodoist(): Promise<{ message: string }> {
  return apiClient<{ message: string }>('/api/v1/todoist/disconnect', {
    method: 'POST',
  })
}

export function getTodoistTasks(timezone?: string): Promise<TodoistTasksResponse> {
  const query = timezone ? `?timezone=${encodeURIComponent(timezone)}` : ''
  return apiClient<TodoistTasksResponse>(`/api/v1/todoist/tasks${query}`)
}

export function createTodoistTask(payload: CreateTodoistTaskPayload): Promise<TodoistTaskResponse> {
  return apiClient<TodoistTaskResponse>('/api/v1/todoist/tasks', {
    method: 'POST',
    body: payload,
  })
}

export function completeTodoistTask(id: string): Promise<TodoistMutationResponse> {
  return apiClient<TodoistMutationResponse>(`/api/v1/todoist/tasks/${id}/complete`, {
    method: 'POST',
  })
}

export function reopenTodoistTask(id: string): Promise<TodoistMutationResponse> {
  return apiClient<TodoistMutationResponse>(`/api/v1/todoist/tasks/${id}/reopen`, {
    method: 'POST',
  })
}

export function deleteTodoistTask(id: string): Promise<TodoistMutationResponse> {
  return apiClient<TodoistMutationResponse>(`/api/v1/todoist/tasks/${id}`, {
    method: 'DELETE',
  })
}

export function useTodoistWidget() {
  const connected = ref(false)
  const loading = ref(false)
  const tasks = ref<TodoistTask[]>([])
  const error = ref<string | null>(null)

  async function loadStatus(): Promise<void> {
    try {
      const status = await getTodoistStatus()
      connected.value = status.connected
      if (status.connected) {
        await loadTasks()
      }
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to load Todoist status'
    }
  }

  async function loadTasks(): Promise<void> {
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

  return {
    connected,
    loading,
    tasks,
    error,
    loadStatus,
    loadTasks,
  }
}
