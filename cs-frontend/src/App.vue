<template>
  <router-view />
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useAuthStore } from './stores/auth'
import { useChatStore } from './stores/chat'
import { wsService } from './services/websocket'

const authStore = useAuthStore()
const chatStore = useChatStore()

onMounted(() => {
  if (authStore.token) {
    chatStore.initWsListeners()
  }

  wsService.onKicked(() => {
    chatStore.clearCurrentChat()
    chatStore.resetWsListeners()
    authStore.logout()
    window.location.href = '/login'
  })
})
</script>

<style>
/* Global reset */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html,
body {
  height: 100%;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue',
    Arial, 'PingFang SC', 'Hiragino Sans GB', 'Microsoft YaHei', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  background: #f5f5f5;
  -webkit-tap-highlight-color: transparent;
}

#app {
  height: 100%;
}

/* Prevent iOS bounce scroll on non-scrollable pages */
body {
  overscroll-behavior: none;
}
</style>
