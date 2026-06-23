<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/modules/user/stores/authStore'
import { usePomodoroStore } from '@/modules/pomodoro/stores/pomodoroStore'
import PomodoroSettingsModal from '@/modules/pomodoro/components/PomodoroSettingsModal.vue'
import type { PomodoroSettings } from '@/modules/pomodoro/types/settings'

const router = useRouter()
const auth = useAuthStore()
const pomodoro = usePomodoroStore()

const showSettings = ref(false)
const settingsChecked = ref(false)

const pomodoroStatusLabel = computed(() => {
  const map: Record<string, string> = {
    idle: 'Готов к работе',
    work: 'Работа',
    break: 'Перерыв',
    long_break: 'Длинный перерыв',
    paused: 'Пауза',
    finished: 'Сессия завершена',
  }
  return map[pomodoro.status] || pomodoro.status
})

const pomodoroStatusColor = computed(() => {
  const map: Record<string, string> = {
    idle: 'text-gray-600',
    work: 'text-green-600',
    break: 'text-blue-600',
    long_break: 'text-indigo-600',
    paused: 'text-yellow-600',
    finished: 'text-purple-600',
  }
  return map[pomodoro.status] || 'text-gray-600'
})

const pomodoroStatusBg = computed(() => {
  const map: Record<string, string> = {
    idle: 'bg-gray-100',
    work: 'bg-green-100',
    break: 'bg-blue-100',
    long_break: 'bg-indigo-100',
    paused: 'bg-yellow-100',
    finished: 'bg-purple-100',
  }
  return map[pomodoro.status] || 'bg-gray-100'
})

const pomodoroProgressPercent = computed(() => {
  if (pomodoro.currentDuration === 0) return 0
  return ((pomodoro.currentDuration - pomodoro.timeLeft) / pomodoro.currentDuration) * 100
})

const isLongBreakNext = computed(() => {
  const nextSession = pomodoro.completedSessions + 1
  if (nextSession >= pomodoro.settings.totalPomodoros) return false
  return nextSession % pomodoro.settings.sessionsBeforeLongBreak === 0
})

const canStartPomodoro = computed(
  () => settingsChecked.value && pomodoro.hasBackendSettings,
)

onMounted(async () => {
  if (!auth.user) {
    auth.fetchUser()
  }
  await pomodoro.loadUserSettings()
  await pomodoro.restoreSession()
  settingsChecked.value = true
})

async function handleToggleTimer(): Promise<void> {
  await pomodoro.toggle()
}

async function handleResetTimer(): Promise<void> {
  await pomodoro.reset()
}

function handleSkipPhase(): void {
  pomodoro.skipPhase()
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

function handleBackToDashboard(): void {
  router.push('/dashboard')
}
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-gradient-to-r from-accent-purple to-accent-blue text-white shadow-lg">
      <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
        <h1 class="text-xl font-bold">Помодоро таймер</h1>
        <div class="flex items-center gap-4">
          <button
            @click="handleBackToDashboard"
            class="px-4 py-1.5 rounded-lg bg-white/20 hover:bg-white/30 transition text-sm font-medium"
          >
            Личный кабинет
          </button>
          <span v-if="auth.user" class="text-sm opacity-90">
            {{ auth.user.name }}
          </span>
        </div>
      </div>
    </header>

    <!-- Content -->
    <main class="max-w-6xl mx-auto px-4 py-10">
      <div class="bg-white rounded-2xl shadow-lg p-8">
        <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
          <h3 class="text-xl font-bold text-gray-800">🍅 Помодоро</h3>
          <div v-if="pomodoro.isUsingSessionSettings" class="text-sm text-indigo-600">
            Используются настройки текущей сессии
          </div>
        </div>

        <div class="max-w-md mx-auto">
          <div class="text-center mb-6">
            <span
              class="inline-block px-4 py-1 rounded-full text-sm font-semibold"
              :class="[pomodoroStatusColor, pomodoroStatusBg]"
            >
              {{ pomodoroStatusLabel }}
            </span>
          </div>

          <div class="text-7xl font-mono font-bold text-gray-800 tracking-tight text-center mb-2">
            {{ pomodoro.formattedTimeLeft }}
          </div>

          <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden mb-6">
            <div
              class="h-full bg-gradient-to-r from-accent-purple to-accent-blue transition-all duration-1000 ease-linear rounded-full"
              :style="{ width: `${pomodoroProgressPercent}%` }"
            />
          </div>

          <div class="flex items-center justify-center gap-6 mb-6 text-sm text-gray-500">
            <span>
              Сессий:
              <strong class="text-gray-700">{{ pomodoro.completedSessions }}</strong>
            </span>
            <span v-if="pomodoro.isSessionFinished" class="text-purple-600 font-medium">
              Все помодоро завершены!
            </span>
            <span v-else-if="pomodoro.currentPomodoro > 0">
              Помодоро
              <strong class="text-gray-700"
                >{{ pomodoro.currentPomodoro }} из
                {{ pomodoro.settings.totalPomodoros }}</strong
              >
            </span>
            <span
              v-if="
                !pomodoro.isSessionFinished &&
                pomodoro.status !== 'work' &&
                pomodoro.status !== 'paused'
              "
            >
              Следующий:
              <strong class="text-gray-700">
                {{ isLongBreakNext ? 'Длинный перерыв' : 'Перерыв' }}
              </strong>
            </span>
          </div>

          <div class="flex items-center justify-center gap-4 mb-6">
            <button
              @click="handleResetTimer"
              class="p-3 rounded-2xl bg-gray-100 text-gray-600 hover:bg-gray-200 transition"
              title="Сбросить"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                />
              </svg>
            </button>

            <button
              @click="handleToggleTimer"
              :disabled="!canStartPomodoro && pomodoro.status === 'idle'"
              class="px-8 py-3 rounded-2xl bg-gradient-to-r from-accent-purple to-accent-blue text-white text-base font-bold shadow-lg hover:opacity-90 transition flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg
                v-if="!pomodoro.isRunning"
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"
                />
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
              <svg
                v-else
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
              {{
                pomodoro.isRunning
                  ? 'Пауза'
                  : pomodoro.status === 'paused'
                    ? 'Продолжить'
                    : pomodoro.isSessionFinished
                      ? 'Начать заново'
                      : 'Старт'
              }}
            </button>

            <button
              @click="handleSkipPhase"
              class="p-3 rounded-2xl bg-gray-100 text-gray-600 hover:bg-gray-200 transition"
              title="Пропустить фазу"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M13 5l7 7-7 7M5 5l7 7-7 7"
                />
              </svg>
            </button>
          </div>

          <div class="bg-gray-50 rounded-xl p-4">
            <div
              v-if="settingsChecked && !pomodoro.hasBackendSettings"
              class="mb-3 px-3 py-2 rounded-lg bg-amber-50 border border-amber-200 text-sm text-amber-800"
            >
              Добавьте настройки помодоро, чтобы запустить таймер.
            </div>
            <div class="flex items-center justify-between mb-3">
              <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">
                Настройки
              </h4>
              <button
                @click="openPomodoroSettings"
                class="text-sm font-medium text-accent-purple hover:text-accent-blue transition"
              >
                {{ pomodoro.hasBackendSettings ? 'Изменить' : 'Добавить' }}
              </button>
            </div>
            <div class="grid grid-cols-2 gap-3 text-sm">
              <div class="flex items-center justify-between">
                <span class="text-gray-600">Работа</span>
                <span class="font-semibold text-gray-800">{{ pomodoro.settings.workTime }} мин</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-gray-600">Перерыв</span>
                <span class="font-semibold text-gray-800">
                  {{ pomodoro.settings.breakTime }} мин
                </span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-gray-600">Длинный перерыв</span>
                <span class="font-semibold text-gray-800">
                  {{ pomodoro.settings.longBreakTime }} мин
                </span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-gray-600">До длинного</span>
                <span class="font-semibold text-gray-800">
                  {{ pomodoro.settings.sessionsBeforeLongBreak }}
                </span>
              </div>
              <div class="flex items-center justify-between col-span-2">
                <span class="text-gray-600">Всего помодоро</span>
                <span class="font-semibold text-gray-800">
                  {{ pomodoro.settings.totalPomodoros }}
                </span>
              </div>
            </div>
          </div>
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
