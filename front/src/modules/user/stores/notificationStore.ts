import { ref } from 'vue'
import { defineStore } from 'pinia'
import {
  disconnectTelegram,
  generateTelegramLinkToken,
  getNotificationPreferences,
  sendEmailVerification,
  updateNotificationChannel,
  updateNotificationEmail,
  type NotificationPreferences,
  type TelegramLinkToken,
} from '../api/notifications'

const EMPTY_PREFERENCES: NotificationPreferences = {
  channel: null,
  telegram: { linked: false, telegramId: null, botName: null },
  email: { address: null, verified: false },
}

export const useNotificationStore = defineStore('notifications', () => {
  const channel = ref<'telegram' | 'email' | null>(null)
  const telegram = ref({ linked: false, telegramId: null as number | null, botName: null as string | null })
  const email = ref({ address: null as string | null, verified: false })
  const isLoading = ref(false)
  const isMutating = ref(false)
  const isSendingVerification = ref(false)
  const error = ref<string | null>(null)
  const linkToken = ref<TelegramLinkToken | null>(null)
  const lastFlash = ref<{ type: 'success' | 'error' | 'info'; message: string } | null>(null)

  function applyPreferences(prefs: NotificationPreferences): void {
    channel.value = prefs.channel
    telegram.value = {
      linked: prefs.telegram.linked,
      telegramId: prefs.telegram.telegramId,
      botName: prefs.telegram.botName,
    }
    email.value = {
      address: prefs.email.address,
      verified: prefs.email.verified,
    }
  }

  async function load(): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      const prefs = await getNotificationPreferences()
      applyPreferences(prefs)
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to load notification preferences'
    } finally {
      isLoading.value = false
    }
  }

  async function setChannel(next: 'telegram' | 'email' | null): Promise<void> {
    isMutating.value = true
    error.value = null
    try {
      await updateNotificationChannel(next)
      channel.value = next
      lastFlash.value = { type: 'success', message: 'Канал уведомлений обновлён' }
    } catch (e) {
      const message = e instanceof Error ? e.message : 'Failed to update channel'
      error.value = message
      lastFlash.value = { type: 'error', message }
      throw e
    } finally {
      isMutating.value = false
    }
  }

  async function setEmail(address: string): Promise<void> {
    isMutating.value = true
    error.value = null
    try {
      await updateNotificationEmail(address)
      email.value = { address, verified: false }
      lastFlash.value = {
        type: 'info',
        message: 'Email сохранён. Подтвердите его для получения уведомлений.',
      }
    } catch (e) {
      const message = e instanceof Error ? e.message : 'Failed to update email'
      error.value = message
      lastFlash.value = { type: 'error', message }
      throw e
    } finally {
      isMutating.value = false
    }
  }

  async function sendVerification(): Promise<void> {
    isSendingVerification.value = true
    error.value = null
    try {
      await sendEmailVerification()
      lastFlash.value = {
        type: 'success',
        message: 'Письмо с подтверждением отправлено. Проверьте почту.',
      }
    } catch (e) {
      const message = e instanceof Error ? e.message : 'Failed to send verification email'
      error.value = message
      lastFlash.value = { type: 'error', message }
      throw e
    } finally {
      isSendingVerification.value = false
    }
  }

  async function generateTelegramToken(): Promise<void> {
    isMutating.value = true
    error.value = null
    try {
      linkToken.value = await generateTelegramLinkToken()
    } catch (e) {
      const message = e instanceof Error ? e.message : 'Failed to generate link token'
      error.value = message
      throw e
    } finally {
      isMutating.value = false
    }
  }

  function clearLinkToken(): void {
    linkToken.value = null
  }

  async function disconnectTelegramAccount(): Promise<void> {
    isMutating.value = true
    error.value = null
    try {
      await disconnectTelegram()
      telegram.value = { linked: false, telegramId: null, botName: telegram.value.botName }
      linkToken.value = null
      if (channel.value === 'telegram') {
        channel.value = null
      }
      lastFlash.value = { type: 'info', message: 'Telegram отвязан' }
    } catch (e) {
      const message = e instanceof Error ? e.message : 'Failed to disconnect Telegram'
      error.value = message
      throw e
    } finally {
      isMutating.value = false
    }
  }

  function consumeFlash(): void {
    lastFlash.value = null
  }

  return {
    channel,
    telegram,
    email,
    isLoading,
    isMutating,
    isSendingVerification,
    error,
    linkToken,
    lastFlash,
    load,
    setChannel,
    setEmail,
    sendVerification,
    generateTelegramToken,
    clearLinkToken,
    disconnectTelegramAccount,
    consumeFlash,
  }
})

export function emptyNotificationPreferences(): NotificationPreferences {
  return { ...EMPTY_PREFERENCES }
}
