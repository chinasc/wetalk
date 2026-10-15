import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User } from '../types'
import { authApi } from '../api'
import { wsService } from '../services/websocket'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string>(localStorage.getItem('token') || '')
  const user = ref<User | null>(null)

  const isLoggedIn = computed(() => !!token.value)
  const nickname = computed(() => user.value?.nickname || user.value?.username || '')
  const avatar = computed(() => user.value?.avatar || '')

  function getDeviceToken(): string {
    let dt = localStorage.getItem('device_token')
    if (!dt) {
      dt = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
        const r = (Math.random() * 16) | 0
        return (c === 'x' ? r : (r & 0x3) | 0x8).toString(16)
      })
      localStorage.setItem('device_token', dt)
    }
    return dt
  }

  async function login(username: string, password: string) {
    const res = await authApi.login({ username, verify_code: password, device_token: getDeviceToken() })
    token.value = res.data.token
    user.value = res.data.user
    localStorage.setItem('token', res.data.token)
    wsService.connect(res.data.token)
    return res.data
  }

  async function fetchUser() {
    const res = await authApi.getInfo()
    user.value = res.data
    return res.data
  }

  async function updateProfile(formData: FormData) {
    const res = await authApi.updateProfile(formData)
    user.value = res.data
    return res.data
  }

  function logout() {
    token.value = ''
    user.value = null
    localStorage.removeItem('token')
    wsService.disconnect()
  }

  function initWs() {
    if (token.value) {
      wsService.connect(token.value)
    }
  }

  return {
    token,
    user,
    isLoggedIn,
    nickname,
    avatar,
    login,
    fetchUser,
    updateProfile,
    logout,
    initWs,
  }
})
