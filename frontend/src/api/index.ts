import axios from 'axios'
import { useAuthStore } from '../stores/auth'

const http = axios.create({
  baseURL: '/api',
  timeout: 15000,
})

http.interceptors.request.use((config) => {
  const authStore = useAuthStore()
  if (authStore.token) {
    config.headers.Authorization = `Bearer ${authStore.token}`
  }
  return config
})

http.interceptors.response.use(
  (response) => {
    const res = response.data
    if (res && typeof res === 'object' && 'code' in res) {
      if (res.code !== 0) {
        const err: any = new Error(res.msg || '请求失败')
        err.code = res.code
        return Promise.reject(err)
      }
      response.data = res.data ?? res
    }
    return response
  },
  (error) => {
    if (error.response?.status === 401) {
      const authStore = useAuthStore()
      authStore.logout()
      window.location.href = '/login'
    }
    const msg = error.response?.data?.msg || error.message || '请求失败'
    return Promise.reject(new Error(msg))
  },
)

export default http

export const authApi = {
  login(params: { username: string; verify_code?: string; device_token?: string }) {
    return http.post('/auth/login', params)
  },
  checkFirstLogin(username: string, deviceToken?: string) {
    return http.post('/auth/check-first-login', { username, device_token: deviceToken || '' })
  },
  getInfo() {
    return http.get('/auth/info')
  },
  updateProfile(formData: FormData) {
    return http.post('/auth/profile', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
}

export const gameApi = {
  getGames() {
    return http.get('/games')
  },
}

export const csApi = {
  getList() {
    return http.get('/cs/list')
  },
}

export const chatApi = {
  verifyCode(code: string) {
    return http.post('/chat/verify-code', { code })
  },
  getConversations() {
    return http.get('/chat/conversations')
  },
  createConversation(customerServiceId: number) {
    return http.post('/chat/conversation/create', { customer_service_id: customerServiceId })
  },
  getMessages(conversationId: number, beforeId?: number) {
    const params: Record<string, number> = {}
    if (beforeId) params.before_id = beforeId
    return http.get(`/chat/messages/${conversationId}`, { params })
  },
  sendText(conversationId: number, content: string) {
    return http.post('/chat/send', { conversation_id: conversationId, type: 'text', content })
  },
  send(conversationId: number, type: string, content: string) {
    return http.post('/chat/send', { conversation_id: conversationId, type, content })
  },
  sendImage(conversationId: number, formData: FormData) {
    return http.post('/chat/send', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
  markRead(conversationId: number) {
    return http.post('/chat/mark-read', { conversation_id: conversationId })
  },
  recallMessage(messageId: number) {
    return http.post('/chat/recall', { message_id: messageId })
  },
  deleteMessage(messageId: number) {
    return http.post('/chat/delete-message', { message_id: messageId })
  },
}

export const uploadApi = {
  uploadImage(formData: FormData) {
    return http.post('/upload/image', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
}

export const albumApi = {
  getAlbums() {
    return http.get('/albums')
  },
  getAlbumDetail(id: number) {
    return http.get(`/albums/${id}`)
  },
}
