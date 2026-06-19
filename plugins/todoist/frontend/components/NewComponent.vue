<template>
	<button
	            @click="showCreateForm = !showCreateForm"
	            class="px-4 py-2 rounded-lg bg-gradient-to-r from-rose-500 to-pink-500 text-white text-sm font-medium shadow hover:opacity-90 transition"
	          >
	            {{ showCreateForm ? 'Скрыть' : 'Новая задача' }}
	          </button>
</template>
