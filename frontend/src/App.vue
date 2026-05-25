<script setup lang="ts">
import { watch } from 'vue'
import { useAuthStore } from './stores/auth'
import { useChatStore } from './stores/chat'

const authStore = useAuthStore()
const chatStore = useChatStore()

watch(() => authStore.isLoggedIn, (val) => {
  if (val) {
    authStore.initWs()
    chatStore.setupWsListeners()
    chatStore.fetchConversations().catch(() => {})
    authStore.fetchUser().catch(() => {
      authStore.logout()
    })
  }
}, { immediate: true })
</script>

<template>
  <router-view />
</template>

<style>
html, body {
  margin: 0;
  padding: 0;
  height: 100%;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  background-color: #f7f8fa;
}

#app {
  height: 100%;
}
</style>
