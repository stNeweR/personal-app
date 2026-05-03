<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/authStore'

const router = useRouter()
const auth = useAuthStore()

onMounted(() => {
  if (!auth.user) {
    auth.fetchUser()
  }
})

async function handleLogout() {
  await auth.logout()
  router.push('/login')
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
        </div>
      </div>
    </header>

    <!-- Content -->
    <main class="max-w-6xl mx-auto px-4 py-10">
      <div class="bg-white rounded-2xl shadow-lg p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">
          Добро пожаловать{{ auth.user ? ', ' + auth.user.name : '' }}!
        </h2>
        <p class="text-gray-600">
          Это ваш личный кабинет. Здесь будет отображаться ваша персональная информация и инструменты.
        </p>

        <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="rounded-xl bg-gradient-to-br from-accent-purple/10 to-accent-blue/10 p-6 border border-accent-purple/20">
            <h3 class="font-semibold text-gray-800 mb-2">Профиль</h3>
            <p class="text-sm text-gray-600">Управление вашими данными и настройками.</p>
          </div>
          <div class="rounded-xl bg-gradient-to-br from-accent-purple/10 to-accent-blue/10 p-6 border border-accent-purple/20">
            <h3 class="font-semibold text-gray-800 mb-2">Задачи</h3>
            <p class="text-sm text-gray-600">Ваши задачи и планы на день.</p>
          </div>
          <div class="rounded-xl bg-gradient-to-br from-accent-purple/10 to-accent-blue/10 p-6 border border-accent-purple/20">
            <h3 class="font-semibold text-gray-800 mb-2">Помодоро</h3>
            <p class="text-sm text-gray-600">Таймер для продуктивной работы.</p>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>
