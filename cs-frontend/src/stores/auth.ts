import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { UserInfo, LoginForm } from '../types'
import { login as apiLogin, getUserInfo } from '../api'
import { wsService } from '../services/websocket'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string>(localStorage.getItem('cs_token') || '')
  const user = ref<UserInfo | null>(null)
  const loading = ref(false)

  async function init(): Promise<void> {
    if (!token.value) return
    try {
      const info = await getUserInfo()
      if (info.role !== 'customer_service') {
        logout()
        return
      }
      user.value = info
      wsService.connect(token.value)
    } catch {
      logout()
    }
  }

  async function login(form: LoginForm): Promise<void> {
    loading.value = true
    try {
      const result = await apiLogin(form)
      if (result.user.role !== 'customer_service') {
        throw new Error('该账号不是客服账号')
      }
      token.value = result.token
      user.value = result.user
      localStorage.setItem('cs_token', result.token)
      localStorage.setItem('cs_user', JSON.stringify(result.user))
      wsService.connect(result.token)
    } finally {
      loading.value = false
    }
  }

  function logout(): void {
    token.value = ''
    user.value = null
    localStorage.removeItem('cs_token')
    localStorage.removeItem('cs_user')
    wsService.disconnect()
  }

  const isLoggedIn = () => !!token.value && !!user.value

  return { token, user, loading, init, login, logout, isLoggedIn }
})
