import { apiClient } from '@/shared/api/client'
import type { PluginExecuteRequest, PluginExecuteResponse } from '../types/plugin'

export function executePlugin<T = unknown>(
  name: string,
  action: string,
  payload: PluginExecuteRequest = {},
): Promise<PluginExecuteResponse<T>> {
  return apiClient<PluginExecuteResponse<T>>(`/api/v1/plugins/${name}/${action}`, {
    method: 'POST',
    body: payload,
  })
}
