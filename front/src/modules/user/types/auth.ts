export interface User {
  id: number
  name: string
  email: string
  telegram_id: number | null
}

export interface AuthResponse {
  token: string
  user: User
}

export interface LoginPayload {
  email: string
  password: string
}

export interface RegisterPayload {
  name: string
  email: string
  password: string
  password_confirmation: string
}

export interface TelegramLinkTokenResponse {
  link_url: string
}
