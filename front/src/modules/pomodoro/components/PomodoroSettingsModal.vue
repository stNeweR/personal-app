<script setup lang="ts">
import { ref, watch } from 'vue'
import type { PomodoroSettings } from '../types/settings'

const props = defineProps<{
  modelValue: boolean
  settings: PomodoroSettings
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'save', settings: PomodoroSettings): void
}>()

const localSettings = ref<PomodoroSettings>({ ...props.settings })

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      localSettings.value = { ...props.settings }
    }
  }
)

function close(): void {
  emit('update:modelValue', false)
}

function handleSave(): void {
  emit('save', { ...localSettings.value })
  close()
}

function handleOverlayClick(event: MouseEvent): void {
  if (event.target === event.currentTarget) {
    close()
  }
}
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="modelValue"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
        @click="handleOverlayClick"
      >
        <Transition
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition duration-150 ease-in"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div
            v-if="modelValue"
            class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6"
            role="dialog"
            aria-modal="true"
          >
            <div class="flex items-center justify-between mb-6">
              <h2 class="text-xl font-bold text-gray-800">Настройки таймера</h2>
              <button
                @click="close"
                class="text-gray-400 hover:text-gray-600 transition"
                aria-label="Закрыть"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <div class="space-y-5">
              <div>
                <label for="workTime" class="block text-sm font-medium text-gray-700 mb-1">
                  Время работы (мин)
                </label>
                <input
                  id="workTime"
                  v-model.number="localSettings.workTime"
                  type="number"
                  min="1"
                  max="120"
                  class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-accent-purple focus:border-transparent transition"
                />
              </div>

              <div>
                <label for="breakTime" class="block text-sm font-medium text-gray-700 mb-1">
                  Время перерыва (мин)
                </label>
                <input
                  id="breakTime"
                  v-model.number="localSettings.breakTime"
                  type="number"
                  min="1"
                  max="60"
                  class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-accent-purple focus:border-transparent transition"
                />
              </div>

              <div>
                <label for="longBreakTime" class="block text-sm font-medium text-gray-700 mb-1">
                  Время долгого перерыва (мин)
                </label>
                <input
                  id="longBreakTime"
                  v-model.number="localSettings.longBreakTime"
                  type="number"
                  min="1"
                  max="120"
                  class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-accent-purple focus:border-transparent transition"
                />
              </div>

              <div>
                <label for="sessionsBeforeLongBreak" class="block text-sm font-medium text-gray-700 mb-1">
                  Сессий до длинного перерыва
                </label>
                <input
                  id="sessionsBeforeLongBreak"
                  v-model.number="localSettings.sessionsBeforeLongBreak"
                  type="number"
                  min="1"
                  max="20"
                  class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-accent-purple focus:border-transparent transition"
                />
              </div>

              <div>
                <label for="totalPomodoros" class="block text-sm font-medium text-gray-700 mb-1">
                  Всего помодоро в сессии
                </label>
                <input
                  id="totalPomodoros"
                  v-model.number="localSettings.totalPomodoros"
                  type="number"
                  min="1"
                  max="20"
                  class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-accent-purple focus:border-transparent transition"
                />
              </div>
            </div>

            <div class="flex items-center justify-end gap-3 mt-8">
              <button
                @click="close"
                class="px-5 py-2.5 rounded-lg text-gray-700 bg-gray-100 hover:bg-gray-200 transition font-medium"
              >
                Отмена
              </button>
              <button
                @click="handleSave"
                class="px-5 py-2.5 rounded-lg bg-gradient-to-r from-accent-purple to-accent-blue text-white font-medium shadow hover:opacity-90 transition"
              >
                Сохранить
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>
