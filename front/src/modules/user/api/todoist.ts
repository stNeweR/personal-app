import { apiClient } from '@/shared/api/client'

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
