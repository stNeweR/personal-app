import { apiClient } from '@/shared/api/client'
import type {
  AuthResponse,
  LoginPayload,
  Plan,
  RegisterPayload,
  TelegramLinkTokenResponse,
  User,
} from '../types/auth'

export function login(payload: LoginPayload): Promise<AuthResponse> {
  return apiClient<AuthResponse>('/api/v1/auth/login', {
    method: 'POST',
    body: payload,
  })
}

export function register(payload: RegisterPayload): Promise<AuthResponse> {
  return apiClient<AuthResponse>('/api/v1/auth/register', {
    method: 'POST',
    body: payload,
  })
}

export function logout(): Promise<{ message: string }> {
  return apiClient<{ message: string }>('/api/v1/auth/logout', {
    method: 'POST',
  })
}

export function me(): Promise<User> {
  return apiClient<User>('/api/v1/auth/me')
}

export function generateTelegramLinkToken(): Promise<TelegramLinkTokenResponse> {
  return apiClient<TelegramLinkTokenResponse>('/api/v1/auth/telegram-link-token', {
    method: 'POST',
  })
}

export function updatePlan(plan: Plan): Promise<User> {
  return apiClient<User>('/api/v1/user/plan', {
    method: 'PUT',
    body: { plan },
  })
}
