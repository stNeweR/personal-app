<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { connectYandexCalendar, useYandexCalendarWidget } from '../api/calendar'

const {
  status,
  loading,
  events,
  error,
  showConnectForm,
  loadStatus,
  loadEvents,
} = useYandexCalendarWidget()

const yandexEmail = ref('')
const yandexPassword = ref('')
const connectLoading = ref(false)

onMounted(async () => {
  await loadStatus()
})

async function handleConnectYandex() {
  connectLoading.value = true
  error.value = null
  try {
    await connectYandexCalendar({
      email: yandexEmail.value,
      app_password: yandexPassword.value,
    })
    showConnectForm.value = false
    yandexEmail.value = ''
    yandexPassword.value = ''
    await loadStatus()
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to connect Yandex Calendar'
  } finally {
    connectLoading.value = false
  }
}

function formatEventTime(dateStr: string): string {
  const match = dateStr.match(/T(\d{2})(\d{2})/)
  if (match) {
    return `${match[1]}:${match[2]}`
  }
  const date = new Date(dateStr)
  if (!isNaN(date.getTime())) {
    return date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })
  }
  return dateStr
}
</script>

<template>
  <div class="bg-white rounded-2xl shadow-lg p-8">
    <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
      <h3 class="text-xl font-bold text-gray-800">Календарь на сегодня</h3>
      <div v-if="status">
        <button
          v-if="!status.connected"
          @click="showConnectForm = true"
          class="px-4 py-2 rounded-lg bg-gradient-to-r from-emerald-500 to-teal-500 text-white text-sm font-medium shadow hover:opacity-90 transition"
        >
          Подключить Яндекс Календарь
        </button>
        <button
          v-else
          @click="loadEvents"
          :disabled="loading"
          class="px-4 py-2 rounded-lg bg-gradient-to-r from-accent-purple to-accent-blue text-white text-sm font-medium shadow hover:opacity-90 transition disabled:opacity-50"
        >
          {{ loading ? 'Загрузка...' : 'Обновить' }}
        </button>
      </div>
    </div>

    <!-- Connect Form -->
    <div
      v-if="showConnectForm && !status?.connected"
      class="mb-6 bg-gray-50 rounded-xl p-6"
    >
      <h4 class="font-semibold text-gray-800 mb-4">Подключение Яндекс Календаря</h4>
      <div class="space-y-3 max-w-md">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email Яндекса</label>
          <input
            v-model="yandexEmail"
            type="email"
            placeholder="your@yandex.ru"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Пароль приложения</label>
          <input
            v-model="yandexPassword"
            type="password"
            placeholder="Введите пароль приложения"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"
          />
          <p class="text-xs text-gray-500 mt-1">
            Создайте пароль приложения в
            <a
              href="https://id.yandex.ru/security/app-passwords"
              target="_blank"
              class="text-blue-600 hover:underline"
            >настройках безопасности Яндекса</a>
          </p>
        </div>
        <div class="flex gap-3">
          <button
            @click="handleConnectYandex"
            :disabled="connectLoading || !yandexEmail || !yandexPassword"
            class="px-4 py-2 rounded-lg bg-gradient-to-r from-emerald-500 to-teal-500 text-white text-sm font-medium shadow hover:opacity-90 transition disabled:opacity-50"
          >
            {{ connectLoading ? 'Подключение...' : 'Подключить' }}
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

    <div v-if="error" class="text-red-500 text-sm mb-4">{{ error }}</div>

    <div
      v-if="!status || (!status.connected && !showConnectForm)"
      class="text-gray-500 text-center py-8"
    >
      Подключите Яндекс Календарь, чтобы увидеть расписание на сегодня.
    </div>

    <div
      v-else-if="events.length === 0 && !loading && status?.connected"
      class="text-gray-500 text-center py-8"
    >
      На сегодня событий нет.
    </div>

    <div v-else-if="events.length > 0" class="space-y-3">
      <div
        v-for="event in events"
        :key="event.id"
        class="border border-gray-100 rounded-xl p-4 hover:bg-gray-50 transition"
      >
        <div class="flex items-start justify-between gap-4">
          <div>
            <h4 class="font-semibold text-gray-800">{{ event.summary || 'Без названия' }}</h4>
            <p v-if="event.description" class="text-sm text-gray-500 mt-1">
              {{ event.description }}
            </p>
          </div>
          <div class="text-sm text-gray-500 whitespace-nowrap">
            {{ formatEventTime(event.start) }} — {{ formatEventTime(event.end) }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
