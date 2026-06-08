<script setup lang="ts">
import { watch } from 'vue'
import { useAuthStore } from './stores/auth'
import { useChatStore } from './stores/chat'
import { wsService } from './services/websocket'

const authStore = useAuthStore()
const chatStore = useChatStore()

wsService.on('kicked', () => {
  authStore.logout()
  window.location.reload()
})

watch(() => authStore.isLoggedIn, (val) => {
  if (val) {
    // Register listeners BEFORE connecting to prevent missing auth.success
    chatStore.setupWsListeners()
    authStore.initWs()
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
