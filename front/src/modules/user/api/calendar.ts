import { apiClient } from '@/shared/api/client'

export interface CalendarStatus {
  connected: boolean
}

export interface CalendarEvent {
  id: string
  summary: string
  description: string
  start: string
  end: string
  start_tz?: string
  end_tz?: string
}

export interface CalendarTodayResponse {
  data: CalendarEvent[]
}

export interface ConnectYandexPayload {
  email: string
  app_password: string
}

export function getCalendarStatus(): Promise<CalendarStatus> {
  return apiClient<CalendarStatus>('/api/v1/yandex-calendar/status')
}

export function connectYandexCalendar(payload: ConnectYandexPayload): Promise<{ message: string }> {
  return apiClient<{ message: string }>('/api/v1/yandex-calendar/connect', {
    method: 'POST',
    body: payload,
  })
}

export function getTodayEvents(date: string): Promise<CalendarTodayResponse> {
  return apiClient<CalendarTodayResponse>(`/api/v1/yandex-calendar/today?date=${date}`)
}
