<script setup lang="ts">
import { onMounted, ref } from 'vue'
import {
  getMailNotifierStatus,
  connectMailNotifier,
  disconnectMailNotifier,
  sendVerificationEmail,
  verifyEmail,
} from '../api/mail-notifier'

const connected = ref(false)
const verified = ref(false)
const email = ref('')
const error = ref<string | null>(null)
const loading = ref(false)
const showConnectForm = ref(false)
const connectLoading = ref(false)
const verificationSent = ref(false)
const verificationCode = ref('')

onMounted(async () => {
  await loadStatus()
})

async function loadStatus() {
  try {
    const status = await getMailNotifierStatus()
    connected.value = status.connected
    verified.value = status.verified
    email.value = status.email || ''
  } catch {
    // ignore
  }
}

async function handleConnect() {
  if (!email.value || !email.value.includes('@')) return
  
  connectLoading.value = true
  error.value = null
  try {
    await connectMailNotifier(email.value)
    showConnectForm.value = false
    verificationSent.value = true
    await loadStatus()
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to save email'
  } finally {
    connectLoading.value = false
  }
}

async function handleSendVerification() {
  loading.value = true
  error.value = null
  try {
    await sendVerificationEmail()
    verificationSent.value = true
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to send verification email'
  } finally {
    loading.value = false
  }
}

async function handleVerify() {
  if (!verificationCode.value || verificationCode.value.length !== 6) {
    error.value = 'Введите 6-значный код'
    return
  }
  
  loading.value = true
  error.value = null
  try {
    await verifyEmail(verificationCode.value)
    verificationCode.value = ''
    verificationSent.value = false
    await loadStatus()
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to verify email'
  } finally {
    loading.value = false
  }
}

async function handleDisconnect() {
  loading.value = true
  error.value = null
  try {
    await disconnectMailNotifier()
    email.value = ''
    connected.value = false
    verified.value = false
    verificationSent.value = false
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to disconnect'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="bg-white rounded-2xl shadow-lg p-8">
    <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
      <div>
        <h3 class="text-xl font-bold text-gray-800">Email уведомления</h3>
        <p v-if="connected" class="text-xs text-gray-500 mt-1">
          <span v-if="verified" class="text-green-600">✓ Подтвержден</span>
          <span v-else class="text-orange-600">⏳ Ожидает подтверждения</span>
        </p>
      </div>
      <div class="flex items-center gap-2">
        <button
          v-if="!connected"
          @click="showConnectForm = !showConnectForm"
          class="px-4 py-2 rounded-lg bg-gradient-to-r from-blue-500 to-indigo-500 text-white text-sm font-medium shadow hover:opacity-90 transition"
        >
          Добавить email
        </button>
        <template v-else>
          <button
            v-if="!verified"
            @click="handleSendVerification"
            :disabled="loading"
            class="px-4 py-2 rounded-lg bg-gradient-to-r from-orange-500 to-red-500 text-white text-sm font-medium shadow hover:opacity-90 transition disabled:opacity-50"
          >
            {{ loading ? 'Отправка...' : 'Отправить код' }}
          </button>
          <button
            @click="handleDisconnect"
            :disabled="loading"
            class="px-3 py-2 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300 transition disabled:opacity-50"
          >
            Удалить
          </button>
        </template>
      </div>
    </div>

    <!-- Connect Form -->
    <div
      v-if="showConnectForm && !connected"
      class="mb-6 bg-gray-50 rounded-xl p-6"
    >
      <h4 class="font-semibold text-gray-800 mb-4">Добавление email</h4>
      <div class="space-y-3 max-w-md">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email адрес</label>
          <input
            v-model="email"
            type="email"
            placeholder="your@email.com"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <div class="flex gap-3">
          <button
            @click="handleConnect"
            :disabled="connectLoading || !email || !email.includes('@')"
            class="px-4 py-2 rounded-lg bg-gradient-to-r from-blue-500 to-indigo-500 text-white text-sm font-medium shadow hover:opacity-90 transition disabled:opacity-50"
          >
            {{ connectLoading ? 'Сохранение...' : 'Сохранить' }}
          </button>
          <button
            @click="showConnectForm = false"
            class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300 transition"
          >
            Отмена
          </button>
        </div>
      </div>
    </div>

    <div
      v-if="connected && !verified && verificationSent"
      class="mb-6 bg-gray-50 rounded-xl p-6"
    >
      <h4 class="font-semibold text-gray-800 mb-4">Подтверждение email</h4>
      <p class="text-sm text-gray-600 mb-3">
        На {{ email }} отправлен 6-значный код. Введите его ниже:
      </p>
      <div class="space-y-3 max-w-md">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Код подтверждения</label>
          <input
            v-model="verificationCode"
            type="text"
            placeholder="Введите 6-значный код"
            maxlength="6"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-center text-2xl tracking-widest"
          />
        </div>
        <div class="flex gap-3">
          <button
            @click="handleVerify"
            :disabled="loading || !verificationCode || verificationCode.length !== 6"
            class="px-4 py-2 rounded-lg bg-gradient-to-r from-blue-500 to-indigo-500 text-white text-sm font-medium shadow hover:opacity-90 transition disabled:opacity-50"
          >
            {{ loading ? 'Проверка...' : 'Подтвердить' }}
          </button>
          <button
            @click="handleSendVerification"
            :disabled="loading"
            class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300 transition disabled:opacity-50"
          >
            {{ loading ? 'Отправка...' : 'Отправить повторно' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Email Info -->
    <div v-if="connected && !showConnectForm" class="mb-4">
      <div class="flex items-center gap-2 text-sm text-gray-600">
        <span>Email:</span>
        <span class="font-medium">{{ email }}</span>
        <span
          v-if="verified"
          class="inline-block px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700"
        >
          Подтвержден
        </span>
        <span
          v-else
          class="inline-block px-2 py-0.5 rounded-full text-xs bg-orange-100 text-orange-700"
        >
          Не подтвержден
        </span>
      </div>
      <p v-if="!verified && !verificationSent" class="text-sm text-gray-500 mt-2">
        Нажмите "Отправить код" чтобы получить письмо с подтверждением.
      </p>
    </div>

    <div v-if="error" class="text-red-500 text-sm mb-4">{{ error }}</div>

    <div
      v-if="!connected && !showConnectForm"
      class="text-gray-500 text-center py-8"
    >
      Добавьте email адрес для получения уведомлений о Pomodoro.
    </div>
  </div>
</template>
