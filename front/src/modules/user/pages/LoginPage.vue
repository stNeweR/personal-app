<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/authStore'

const router = useRouter()
const auth = useAuthStore()

const email = ref('')
const password = ref('')

async function handleSubmit() {
  try {
    await auth.login({ email: email.value, password: password.value })
    router.push('/dashboard')
  } catch {
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-accent-purple to-accent-blue px-4">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl p-8">
      <h1 class="text-3xl font-bold text-center text-gray-800 mb-2">Вход</h1>
      <p class="text-center text-gray-500 mb-6">Войдите в свой аккаунт</p>

      <form @submit.prevent="handleSubmit" class="space-y-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input
            v-model="email"
            type="email"
            required
            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-accent-purple focus:border-transparent transition"
            placeholder="you@example.com"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Пароль</label>
          <input
            v-model="password"
            type="password"
            required
            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-accent-purple focus:border-transparent transition"
            placeholder="••••••••"
          />
        </div>

        <p v-if="auth.error" class="text-red-500 text-sm text-center">{{ auth.error }}</p>

        <button
          type="submit"
          :disabled="auth.isLoading"
          class="w-full py-2.5 rounded-lg bg-gradient-to-r from-accent-purple to-accent-blue text-white font-semibold shadow-lg hover:shadow-xl hover:opacity-90 transition disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ auth.isLoading ? 'Вход...' : 'Войти' }}
        </button>
      </form>

      <p class="mt-6 text-center text-sm text-gray-600">
        Нет аккаунта?
        <RouterLink to="/register" class="text-accent-purple font-semibold hover:underline">
          Зарегистрироваться
        </RouterLink>
      </p>
    </div>
  </div>
</template>
