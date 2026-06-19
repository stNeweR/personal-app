<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/authStore'
import { getTodaySessions } from '@/modules/pomodoro/api/sessions'
import { useNotificationStore } from '../stores/notificationStore'
import { usePomodoroStore } from '@/modules/pomodoro/stores/pomodoroStore'
import PomodoroSettingsModal from '@/modules/pomodoro/components/PomodoroSettingsModal.vue'
import type { PomodoroSettings } from '@/modules/pomodoro/types/settings'
import type { PomodoroSession } from '@/modules/pomodoro/types/session'
import { pluginRegistry } from '@/shared/plugins/PluginRegistry'

const router = useRouter()
const auth = useAuthStore()

const sessions = ref<PomodoroSession[]>([])
const sessionsLoading = ref(false)
const sessionsError = ref<string | null>(null)

const notifications = useNotificationStore()
const emailInput = ref('')

const channelOptions: { value: 'telegram' | 'email' | null; label: string }[] = [
  { value: null, label: 'Не получать' },
  { value: 'telegram', label: 'Telegram' },
  { value: 'email', label: 'Email' },
]

const pomodoro = usePomodoroStore()
const showSettings = ref(false)
const settingsChecked = ref(false)

const pluginWidgets = computed(() => pluginRegistry.getWidgets())

onMounted(async () => {
  if (!auth.user) {
    auth.fetchUser()
  }
  loadSessions()
  await notifications.load()
  emailInput.value = notifications.email.address ?? ''
  const params = new URLSearchParams(window.location.search)
  const ev = params.get('email_verified')
  if (ev === '1') {
    notifications.lastFlash = { type: 'success', message: 'Email успешно подтверждён' }
    window.history.replaceState({}, '', window.location.pathname)
  } else if (ev === '0') {
    notifications.lastFlash = { type: 'error', message: 'Не удалось подтвердить email — ссылка недействительна' }
    window.history.replaceState({}, '', window.location.pathname)
  }
  await pomodoro.loadUserSettings()
  settingsChecked.value = true
  await pluginRegistry.loadPlugins()
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

async function handleGenerateTelegramToken(): Promise<void> {
  try {
    await notifications.generateTelegramToken()
  } catch {
    // ошибка уже в notifications.error
  }
}

async function handleRefreshNotifications(): Promise<void> {
  await notifications.load()
  emailInput.value = notifications.email.address ?? ''
}

async function handleSetChannel(next: 'telegram' | 'email' | null): Promise<void> {
  try {
    await notifications.setChannel(next)
  } catch {
    // ошибка уже в notifications.error
  }
}

async function handleSaveEmail(): Promise<void> {
  const trimmed = emailInput.value.trim()
  if (trimmed === '') return
  try {
    await notifications.setEmail(trimmed)
  } catch {
    // ошибка уже в notifications.error
  }
}

async function handleSendEmailVerification(): Promise<void> {
  try {
    await notifications.sendVerification()
  } catch {
    // ошибка уже в notifications.error
  }
}

async function handleDisconnectTelegram(): Promise<void> {
  if (!confirm('Отвязать Telegram?')) return
  try {
    await notifications.disconnectTelegramAccount()
  } catch {
    // ошибка уже в notifications.error
  }
}

function openPomodoroSettings(): void {
  showSettings.value = true
}

async function savePomodoroSettings(settings: PomodoroSettings): Promise<void> {
  try {
    await pomodoro.saveUserSettingsToBackend(settings)
    showSettings.value = false
  } catch {
    // error is already set in store
  }
}

function handleGoToTimer(): void {
  router.push('/timer')
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
          <router-link
            to="/plugins"
            class="px-4 py-1.5 rounded-lg bg-white/20 hover:bg-white/30 transition text-sm font-medium"
          >
            Плагины
          </router-link>
        </div>
      </div>
    </header>

    <!-- Content -->
    <main class="max-w-6xl mx-auto px-4 py-10 space-y-8">
      <!-- Pomodoro Settings Section -->
      <div class="bg-white rounded-2xl shadow-lg p-8">
        <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
          <div>
            <h3 class="text-xl font-bold text-gray-800">🍅 Настройки помодоро</h3>
            <p class="text-sm text-gray-500 mt-1">
              Настройте таймер перед началом работы
            </p>
          </div>
          <button
            v-if="pomodoro.hasBackendSettings"
            @click="handleGoToTimer"
            class="px-4 py-2 rounded-lg bg-gradient-to-r from-accent-purple to-accent-blue text-white text-sm font-medium shadow hover:opacity-90 transition"
          >
            Перейти к таймеру
          </button>
        </div>

        <div
          v-if="settingsChecked && !pomodoro.hasBackendSettings"
          class="mb-6 px-4 py-3 rounded-lg bg-amber-50 border border-amber-200 text-sm text-amber-800"
        >
          Добавьте настройки помодоро, чтобы открыть страницу таймера.
        </div>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
          <div class="bg-gray-50 rounded-xl p-4 text-center">
            <div class="text-xs text-gray-500 mb-1">Работа</div>
            <div class="text-lg font-bold text-gray-800">{{ pomodoro.settings.workTime }} мин</div>
          </div>
          <div class="bg-gray-50 rounded-xl p-4 text-center">
            <div class="text-xs text-gray-500 mb-1">Перерыв</div>
            <div class="text-lg font-bold text-gray-800">{{ pomodoro.settings.breakTime }} мин</div>
          </div>
          <div class="bg-gray-50 rounded-xl p-4 text-center">
            <div class="text-xs text-gray-500 mb-1">Длинный перерыв</div>
            <div class="text-lg font-bold text-gray-800">{{ pomodoro.settings.longBreakTime }} мин</div>
          </div>
          <div class="bg-gray-50 rounded-xl p-4 text-center">
            <div class="text-xs text-gray-500 mb-1">До длинного</div>
            <div class="text-lg font-bold text-gray-800">{{ pomodoro.settings.sessionsBeforeLongBreak }}</div>
          </div>
          <div class="bg-gray-50 rounded-xl p-4 text-center">
            <div class="text-xs text-gray-500 mb-1">Всего помодоро</div>
            <div class="text-lg font-bold text-gray-800">{{ pomodoro.settings.totalPomodoros }}</div>
          </div>
        </div>

        <button
          @click="openPomodoroSettings"
          class="px-5 py-2.5 rounded-lg bg-gradient-to-r from-accent-purple to-accent-blue text-white text-sm font-medium shadow hover:opacity-90 transition"
        >
          {{ pomodoro.hasBackendSettings ? 'Изменить настройки' : 'Добавить настройки' }}
        </button>
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
          Сегодня сессий пока нет. Запустите таймер на странице помодоро.
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

      <!-- Dynamic Plugin Widgets -->
      <div v-for="widget in pluginWidgets" :key="widget.name">
        <component :is="widget.component" />
      </div>

      <!-- Notifications Section -->
      <div class="bg-white rounded-2xl shadow-lg p-8">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-4">
          <div>
            <h3 class="text-xl font-bold text-gray-800">🔔 Уведомления</h3>
            <p class="text-xs text-gray-500 mt-1">
              Выберите, куда присылать уведомления о завершении фаз помодоро
            </p>
          </div>
          <button
            @click="handleRefreshNotifications"
            :disabled="notifications.isLoading"
            class="text-xs px-3 py-1.5 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition disabled:opacity-50"
            title="Обновить статус"
          >
            {{ notifications.isLoading ? 'Обновление...' : 'Обновить' }}
          </button>
        </div>

        <div
          v-if="notifications.lastFlash"
          :class="[
            'mb-4 px-4 py-3 rounded-lg text-sm flex items-center justify-between gap-3',
            notifications.lastFlash.type === 'success' && 'bg-emerald-50 text-emerald-700 border border-emerald-200',
            notifications.lastFlash.type === 'error' && 'bg-red-50 text-red-700 border border-red-200',
            notifications.lastFlash.type === 'info' && 'bg-sky-50 text-sky-700 border border-sky-200',
          ]"
        >
          <span>{{ notifications.lastFlash.message }}</span>
          <button
            @click="notifications.consumeFlash"
            class="text-xs opacity-70 hover:opacity-100"
          >
            ✕
          </button>
        </div>

        <!-- Channel selector -->
        <div class="mb-6">
          <p class="text-sm font-medium text-gray-700 mb-2">Канал уведомлений</p>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="option in channelOptions"
              :key="option.value ?? 'none'"
              @click="handleSetChannel(option.value)"
              :disabled="notifications.isMutating"
              :class="[
                'px-4 py-2 rounded-lg text-sm font-medium border transition disabled:opacity-50',
                notifications.channel === option.value
                  ? 'bg-gradient-to-r from-accent-purple to-accent-blue text-white border-transparent shadow'
                  : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
              ]"
            >
              {{ option.label }}
            </button>
          </div>
        </div>

        <!-- Telegram panel -->
        <div class="border-t border-gray-100 pt-5 mb-5">
          <div class="flex items-center justify-between mb-3">
            <h4 class="text-sm font-semibold text-gray-700">📱 Telegram</h4>
            <span
              v-if="notifications.telegram.linked"
              class="text-xs text-emerald-600 font-medium"
            >✓ Привязан</span>
            <span
              v-else
              class="text-xs text-gray-500"
            >Не привязан</span>
          </div>

          <div v-if="!notifications.telegram.linked">
            <button
              v-if="!notifications.linkToken"
              @click="handleGenerateTelegramToken"
              :disabled="notifications.isMutating"
              class="px-4 py-2 rounded-lg bg-gradient-to-r from-sky-500 to-blue-500 text-white text-sm font-medium shadow hover:opacity-90 transition disabled:opacity-50"
            >
              {{ notifications.isMutating ? 'Генерация...' : 'Привязать Telegram' }}
            </button>

            <div
              v-if="notifications.linkToken"
              class="bg-sky-50 border border-sky-200 rounded-xl p-4"
            >
              <p class="text-sm text-gray-700 mb-2">
                Откройте Telegram и отправьте боту
                <strong>@{{ notifications.linkToken.botName || 'bot' }}</strong> команду:
              </p>
              <code
                class="block bg-white px-3 py-2 rounded text-sm font-mono text-gray-800 select-all"
              >
                /start {{ notifications.linkToken.token }}
              </code>
              <p class="text-xs text-gray-500 mt-3">
                Токен действителен {{ notifications.linkToken.expiresInMinutes }} минут. После
                отправки команды нажмите «Обновить» выше.
              </p>
              <button
                @click="notifications.clearLinkToken"
                class="mt-2 text-xs text-gray-500 hover:text-gray-700 transition"
              >
                Скрыть
              </button>
            </div>
          </div>

          <button
            v-else
            @click="handleDisconnectTelegram"
            :disabled="notifications.isMutating"
            class="text-sm text-red-500 hover:text-red-700 transition disabled:opacity-50"
          >
            Отвязать Telegram
          </button>
        </div>

        <!-- Email panel -->
        <div class="border-t border-gray-100 pt-5">
          <div class="flex items-center justify-between mb-3">
            <h4 class="text-sm font-semibold text-gray-700">📧 Email</h4>
            <span
              v-if="notifications.email.verified"
              class="text-xs text-emerald-600 font-medium"
            >✓ Подтверждён</span>
            <span
              v-else-if="notifications.email.address"
              class="text-xs text-amber-600 font-medium"
            >Не подтверждён</span>
            <span
              v-else
              class="text-xs text-gray-500"
            >Не указан</span>
          </div>

          <div class="flex flex-col sm:flex-row gap-2">
            <input
              v-model="emailInput"
              type="email"
              placeholder="you@example.com"
              class="flex-1 px-4 py-2 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-accent-blue"
              :disabled="notifications.isMutating"
              @keyup.enter="handleSaveEmail"
            />
            <button
              @click="handleSaveEmail"
              :disabled="notifications.isMutating || emailInput.trim() === ''"
              class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200 transition disabled:opacity-50"
            >
              Сохранить
            </button>
            <button
              v-if="notifications.email.address && !notifications.email.verified"
              @click="handleSendEmailVerification"
              :disabled="notifications.isSendingVerification"
              class="px-4 py-2 rounded-lg bg-gradient-to-r from-accent-purple to-accent-blue text-white text-sm font-medium shadow hover:opacity-90 transition disabled:opacity-50"
            >
              {{ notifications.isSendingVerification ? 'Отправка...' : 'Отправить подтверждение' }}
            </button>
          </div>

          <p
            v-if="notifications.email.address && !notifications.email.verified"
            class="text-xs text-amber-600 mt-2"
          >
            Email не подтверждён. Пока вы не подтвердите почту, уведомления на email не будут отправляться.
          </p>
        </div>

        <div v-if="notifications.error" class="text-red-500 text-sm mt-3">
          {{ notifications.error }}
        </div>
      </div>
    </main>

    <PomodoroSettingsModal
      v-model="showSettings"
      :settings="pomodoro.settings"
      @save="savePomodoroSettings"
    />
  </div>
</template>
