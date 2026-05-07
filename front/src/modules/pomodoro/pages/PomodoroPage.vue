<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { usePomodoroStore } from '../stores/pomodoroStore'
import PomodoroSettingsModal from '../components/PomodoroSettingsModal.vue'

const router = useRouter()
const store = usePomodoroStore()

const showSettings = ref(false)
const showSessionSettings = ref(false)
const settingsChecked = ref(false)

onMounted(async () => {
  await store.loadUserSettings()
  settingsChecked.value = true
})

const statusLabel = computed(() => {
  const map: Record<string, string> = {
    idle: 'Готов к работе',
    work: 'Работа',
    break: 'Перерыв',
    long_break: 'Длинный перерыв',
    paused: 'Пауза',
    finished: 'Сессия завершена',
  }
  return map[store.status] || store.status
})

const statusColor = computed(() => {
  const map: Record<string, string> = {
    idle: 'text-gray-600',
    work: 'text-green-600',
    break: 'text-blue-600',
    long_break: 'text-indigo-600',
    paused: 'text-yellow-600',
    finished: 'text-purple-600',
  }
  return map[store.status] || 'text-gray-600'
})

const statusBg = computed(() => {
  const map: Record<string, string> = {
    idle: 'bg-gray-100',
    work: 'bg-green-100',
    break: 'bg-blue-100',
    long_break: 'bg-indigo-100',
    paused: 'bg-yellow-100',
    finished: 'bg-purple-100',
  }
  return map[store.status] || 'bg-gray-100'
})

const progressPercent = computed(() => {
  if (store.currentDuration === 0) return 0
  return ((store.currentDuration - store.timeLeft) / store.currentDuration) * 100
})

const isLongBreakNext = computed(() => {
  const nextSession = store.completedSessions + 1
  if (nextSession >= store.settings.totalPomodoros) return false
  return nextSession % store.settings.sessionsBeforeLongBreak === 0
})

const canStart = computed(() => {
  return settingsChecked.value
})

async function handleToggle(): Promise<void> {
  await store.toggle()
}

function handleReset(): void {
  store.reset()
}

function handleSkip(): void {
  store.skipPhase()
}

function openSettings(): void {
  showSettings.value = true
}

async function saveSettings(settings: typeof store.settings): Promise<void> {
  try {
    await store.saveUserSettingsToBackend(settings)
    showSettings.value = false
  } catch {
    // error is already set in store
  }
}

function saveSessionSettings(settings: typeof store.settings): void {
  store.setSessionSettings(settings)
  showSessionSettings.value = false
}

function openSessionSettings(): void {
  showSessionSettings.value = true
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Header -->
    <header class="bg-gradient-to-r from-accent-purple to-accent-blue text-white shadow-lg">
      <div class="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
        <div class="flex items-center gap-4">
          <button
            @click="router.push('/dashboard')"
            class="flex items-center gap-1 text-sm font-medium hover:opacity-80 transition"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Назад
          </button>
          <h1 class="text-xl font-bold">Помодоро</h1>
        </div>
        <button
          @click="openSettings"
          class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/20 hover:bg-white/30 transition text-sm font-medium"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          Настройки
        </button>
      </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 flex items-center justify-center px-4 py-10">
      <div class="w-full max-w-md">
        <!-- Session Settings Badge -->
        <div
          v-if="store.isUsingSessionSettings"
          class="mb-4 bg-indigo-50 border border-indigo-200 rounded-xl p-3 text-sm text-indigo-800"
        >
          <div class="flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            <span class="font-medium">Используются настройки текущей сессии</span>
          </div>
        </div>

        <!-- Timer Card -->
        <div class="bg-white rounded-3xl shadow-xl p-8 text-center">
          <!-- Status Badge -->
          <div class="mb-6">
            <span
              class="inline-block px-4 py-1 rounded-full text-sm font-semibold"
              :class="[statusColor, statusBg]"
            >
              {{ statusLabel }}
            </span>
          </div>

          <!-- Timer Display -->
          <div class="mb-2">
            <div class="text-7xl font-mono font-bold text-gray-800 tracking-tight">
              {{ store.formattedTimeLeft }}
            </div>
          </div>

          <!-- Progress Bar -->
          <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden mb-8">
            <div
              class="h-full bg-gradient-to-r from-accent-purple to-accent-blue transition-all duration-1000 ease-linear rounded-full"
              :style="{ width: `${progressPercent}%` }"
            />
          </div>

          <!-- Session Info -->
          <div class="flex items-center justify-center gap-6 mb-8 text-sm text-gray-500">
            <div class="flex items-center gap-1.5">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-accent-purple" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>Сессий: <strong class="text-gray-700">{{ store.completedSessions }}</strong></span>
            </div>
            <div v-if="store.isSessionFinished" class="flex items-center gap-1.5">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
              </svg>
              <span><strong class="text-gray-700">Все помодоро завершены!</strong></span>
            </div>
            <div v-else-if="store.currentPomodoro > 0" class="flex items-center gap-1.5">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
              <span>
                Помодоро
                <strong class="text-gray-700">{{ store.currentPomodoro }} из {{ store.settings.totalPomodoros }}</strong>
              </span>
            </div>
            <div v-if="!store.isSessionFinished && store.status !== 'work' && store.status !== 'paused'" class="flex items-center gap-1.5">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-accent-blue" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>
                Следующий:
                <strong class="text-gray-700">
                  {{ isLongBreakNext ? 'Длинный перерыв' : 'Перерыв' }}
                </strong>
              </span>
            </div>
          </div>

          <!-- Controls -->
          <div class="flex items-center justify-center gap-4">
            <button
              @click="handleReset"
              class="p-4 rounded-2xl bg-gray-100 text-gray-600 hover:bg-gray-200 transition"
              title="Сбросить"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
            </button>

            <button
              @click="handleToggle"
              :disabled="!canStart && store.status === 'idle'"
              class="px-10 py-4 rounded-2xl bg-gradient-to-r from-accent-purple to-accent-blue text-white text-lg font-bold shadow-lg hover:opacity-90 transition flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg
                v-if="!store.isRunning"
                xmlns="http://www.w3.org/2000/svg"
                class="h-6 w-6"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <svg
                v-else
                xmlns="http://www.w3.org/2000/svg"
                class="h-6 w-6"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              {{ store.isRunning ? 'Пауза' : (store.status === 'paused' ? 'Продолжить' : (store.isSessionFinished ? 'Начать заново' : 'Старт')) }}
            </button>

            <button
              @click="handleSkip"
              class="p-4 rounded-2xl bg-gray-100 text-gray-600 hover:bg-gray-200 transition"
              title="Пропустить фазу"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Quick Settings Preview -->
        <div class="mt-6 bg-white rounded-2xl shadow-lg p-5">
          <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Текущие настройки</h3>
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div class="flex items-center justify-between">
              <span class="text-gray-600">Работа</span>
              <span class="font-semibold text-gray-800">{{ store.settings.workTime }} мин</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-600">Перерыв</span>
              <span class="font-semibold text-gray-800">{{ store.settings.breakTime }} мин</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-600">Длинный перерыв</span>
              <span class="font-semibold text-gray-800">{{ store.settings.longBreakTime }} мин</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-600">Сессий до долгого</span>
              <span class="font-semibold text-gray-800">{{ store.settings.sessionsBeforeLongBreak }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-600">Всего помодоро</span>
              <span class="font-semibold text-gray-800">{{ store.settings.totalPomodoros }}</span>
            </div>
          </div>
        </div>

        <!-- Session Settings -->
        <div class="mt-4 bg-white rounded-2xl shadow-lg p-5">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Настройки текущей сессии</h3>
            <button
              @click="openSessionSettings"
              class="text-sm font-medium text-accent-purple hover:text-accent-blue transition"
            >
              {{ store.isUsingSessionSettings ? 'Изменить' : 'Задать' }}
            </button>
          </div>
          <div v-if="store.isUsingSessionSettings" class="grid grid-cols-2 gap-4 text-sm">
            <div class="flex items-center justify-between">
              <span class="text-gray-600">Работа</span>
              <span class="font-semibold text-gray-800">{{ store.activeSettings.workTime }} мин</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-600">Перерыв</span>
              <span class="font-semibold text-gray-800">{{ store.activeSettings.breakTime }} мин</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-600">Длинный перерыв</span>
              <span class="font-semibold text-gray-800">{{ store.activeSettings.longBreakTime }} мин</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-600">Сессий до долгого</span>
              <span class="font-semibold text-gray-800">{{ store.activeSettings.sessionsBeforeLongBreak }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-600">Всего помодоро</span>
              <span class="font-semibold text-gray-800">{{ store.activeSettings.totalPomodoros }}</span>
            </div>
          </div>
          <p v-else class="text-sm text-gray-500">
            Будут использованы настройки профиля.
            <span v-if="!store.hasBackendSettings" class="text-yellow-600"> У вас нет сохранённых настроек — задайте их или используйте настройки текущей сессии.</span>
          </p>
        </div>
      </div>
    </main>

    <!-- Settings Modal -->
    <PomodoroSettingsModal
      v-model="showSettings"
      :settings="store.settings"
      @save="saveSettings"
    />

    <!-- Session Settings Modal -->
    <PomodoroSettingsModal
      v-model="showSessionSettings"
      :settings="store.activeSettings"
      @save="saveSessionSettings"
    />
  </div>
</template>
