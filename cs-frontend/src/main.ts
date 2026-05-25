import { createApp } from 'vue'
import { createPinia } from 'pinia'
import Vant from 'vant'
import 'vant/lib/index.css'

import App from './App.vue'
import router from './router'
import { useAuthStore } from './stores/auth'
import { useChatStore } from './stores/chat'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)
app.use(Vant)

app.mount('#app')

const authStore = useAuthStore()
const chatStore = useChatStore()

// Page refresh: restore session
authStore.init().then(() => {
  if (authStore.token) {
    chatStore.initWsListeners()
    chatStore.loadConversations()
  }
})
