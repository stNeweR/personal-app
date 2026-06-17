export interface PluginExecuteRequest {
  input?: Record<string, unknown>
}

export interface PluginExecuteResponse<T = unknown> {
  data: T
}

export interface PluginInfo {
  name: string
  label: string
  description: string
  actions: PluginAction[]
}

export interface PluginAction {
  name: string
  label: string
  defaultPayload: string
}

export const AVAILABLE_PLUGINS: PluginInfo[] = [
  {
    name: 'calendar',
    label: 'Календарь',
    description: 'Управление событиями',
    actions: [
      { name: 'list_events', label: 'Список событий', defaultPayload: '{}' },
      {
        name: 'create_event',
        label: 'Создать событие',
        defaultPayload:
          '{"title":"Встреча","description":"","start":"2026-06-04T10:00:00Z","end":"2026-06-04T11:00:00Z"}',
      },
    ],
  },
  {
    name: 'todoist',
    label: 'Todoist',
    description: 'Управление задачами в Todoist',
    actions: [
      { name: 'list_tasks', label: 'Список задач', defaultPayload: '{"token":"<api_token>"}' },
      {
        name: 'create_task',
        label: 'Создать задачу',
        defaultPayload:
          '{"token":"<api_token>","content":"Новая задача","description":"","priority":1}',
      },
      {
        name: 'update_task',
        label: 'Обновить задачу',
        defaultPayload:
          '{"token":"<api_token>","id":"<task_id>","content":"Обновлённая задача","priority":2}',
      },
      {
        name: 'complete_task',
        label: 'Завершить задачу',
        defaultPayload: '{"token":"<api_token>","id":"<task_id>"}',
      },
      {
        name: 'reopen_task',
        label: 'Возобновить задачу',
        defaultPayload: '{"token":"<api_token>","id":"<task_id>"}',
      },
      {
        name: 'delete_task',
        label: 'Удалить задачу',
        defaultPayload: '{"token":"<api_token>","id":"<task_id>"}',
      },
    ],
  },
  {
    name: 'weather',
    label: 'Погода',
    description: 'Погода через Open-Meteo',
    actions: [
      {
        name: 'current',
        label: 'Текущая погода',
        defaultPayload: '{"latitude":55.7558,"longitude":37.6173}',
      },
      {
        name: 'forecast',
        label: 'Прогноз',
        defaultPayload: '{"latitude":55.7558,"longitude":37.6173,"days":7}',
      },
    ],
  },
  {
    name: 'converter',
    label: 'Конвертер',
    description: 'Конвертация валют и единиц',
    actions: [
      {
        name: 'currency',
        label: 'Валюта',
        defaultPayload: '{"amount":100,"from":"USD","to":"EUR"}',
      },
      { name: 'unit', label: 'Единицы', defaultPayload: '{"amount":1,"from":"km","to":"m"}' },
    ],
  },
  {
    name: 'notifier',
    label: 'Уведомления',
    description: 'Отправка и история уведомлений',
    actions: [
      {
        name: 'send',
        label: 'Отправить',
        defaultPayload: '{"channel":"telegram","message":"Привет!"}',
      },
      { name: 'history', label: 'История', defaultPayload: '{}' },
    ],
  },
]
