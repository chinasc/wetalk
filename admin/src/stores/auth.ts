import { defineStore } from 'pinia'
import { ref } from 'vue'
import { loginApi, getAdminInfo } from '@/api'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('admin_token') || '')
  const adminInfo = ref<any>(null)

  async function login(username: string, password: string) {
    const res = await loginApi({ username, password })
    token.value = res.data.token
    localStorage.setItem('admin_token', res.data.token)
  }

  async function fetchInfo() {
    const res = await getAdminInfo()
    adminInfo.value = res.data
  }

  function logout() {
    token.value = ''
    adminInfo.value = null
    localStorage.removeItem('admin_token')
  }

  return { token, adminInfo, login, fetchInfo, logout }
})
