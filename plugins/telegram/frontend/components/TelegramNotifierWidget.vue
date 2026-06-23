<script setup lang="ts">
import { onMounted, ref } from 'vue'
import {
  getTelegramNotifierStatus,
  disconnectTelegramNotifier,
  getTelegramNotifierHistory,
  type TelegramNotifierHistoryItem,
} from '../api/telegram-notifier'

const connected = ref(false)
const chatId = ref<number | null>(null)
const botName = ref<string | null>(null)
const error = ref<string | null>(null)
const loading = ref(false)
const history = ref<TelegramNotifierHistoryItem[]>([])
const showHistory = ref(false)

onMounted(async () => {
  await loadStatus()
})

async function loadStatus() {
  try {
    const status = await getTelegramNotifierStatus()
    connected.value = status.connected
    chatId.value = status.chat_id
    botName.value = status.bot_name
  } catch {
    // ignore
  }
}

async function handleDisconnect() {
  loading.value = true
  error.value = null
  try {
    await disconnectTelegramNotifier()
    connected.value = false
    chatId.value = null
    history.value = []
    showHistory.value = false
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to disconnect'
  } finally {
    loading.value = false
  }
}

async function loadHistory() {
  loading.value = true
  try {
    history.value = await getTelegramNotifierHistory()
    showHistory.value = true
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to load history'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="bg-white rounded-2xl shadow-lg p-8">
    <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
      <div>
        <h3 class="text-xl font-bold text-gray-800">Telegram уведомления</h3>
        <p v-if="connected" class="text-xs text-gray-500 mt-1">
          <span class="text-green-600">✓ Подключен</span>
        </p>
      </div>
      <div class="flex items-center gap-2">
        <button
          v-if="connected"
          @click="handleDisconnect"
          :disabled="loading"
          class="px-3 py-2 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300 transition disabled:opacity-50"
        >
          Отвязать
        </button>
      </div>
    </div>

    <!-- Connected Info -->
    <div v-if="connected" class="mb-4">
      <div class="flex items-center gap-2 text-sm text-gray-600">
        <span>Chat ID:</span>
        <span class="font-medium">{{ chat_id }}</span>
        <span class="inline-block px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700">
          Подключен
        </span>
      </div>
      <p class="text-sm text-gray-500 mt-2">
        Вы будете получать уведомления о Pomodoro таймере в Telegram.
      </p>

      <button
        v-if="!showHistory"
        @click="loadHistory"
        :disabled="loading"
        class="mt-4 px-4 py-2 rounded-lg bg-gradient-to-r from-blue-500 to-indigo-500 text-white text-sm font-medium shadow hover:opacity-90 transition disabled:opacity-50"
      >
        {{ loading ? 'Загрузка...' : 'История уведомлений' }}
      </button>
      <button
        v-else
        @click="showHistory = false"
        class="mt-4 px-4 py-2 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300 transition"
      >
        Скрыть историю
      </button>

      <!-- History -->
      <div v-if="showHistory && history.length > 0" class="mt-4 bg-gray-50 rounded-xl p-4 max-h-64 overflow-y-auto">
        <div v-for="item in history" :key="item.id" class="border-b border-gray-200 py-2 last:border-b-0">
          <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
            <span>{{ new Date(item.sent_at).toLocaleString('ru-RU') }}</span>
            <span :class="item.status === 'sent' ? 'text-green-600' : 'text-red-600'">
              {{ item.status === 'sent' ? 'Отправлено' : 'Ошибка' }}
            </span>
          </div>
          <p class="text-sm text-gray-700">{{ item.message }}</p>
        </div>
      </div>

      <div v-if="showHistory && history.length === 0" class="mt-4 text-gray-500 text-sm text-center py-4">
        Нет отправленных уведомлений
      </div>
    </div>

    <div v-if="error" class="text-red-500 text-sm mb-4">{{ error }}</div>

    <!-- Not Connected -->
    <div v-if="!connected" class="text-gray-500 text-center py-8">
      <p class="mb-4">Для получения уведомлений в Telegram:</p>
      <ol class="text-left inline-block space-y-2 text-sm">
        <li class="flex items-start gap-2">
          <span class="font-bold text-blue-600">1.</span>
          <span>Перейдите в бот <span class="font-medium" v-if="botName">@{{ botName }}</span><span v-else>Telegram</span></span>
        </li>
        <li class="flex items-start gap-2">
          <span class="font-bold text-blue-600">2.</span>
          <span>Отправьте команду <code class="bg-gray-100 px-1 rounded">/start</code></span>
        </li>
      </ol>
    </div>
  </div>
</template>
