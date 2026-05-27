import axios from 'axios'

const http = axios.create({
  baseURL: '/api',
  timeout: 30000,
})

http.interceptors.request.use((config) => {
  const token = localStorage.getItem('admin_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

http.interceptors.response.use(
  (response) => {
    const res = response.data
    if (res && typeof res === 'object' && 'code' in res) {
      if (res.code !== 0) {
        return Promise.reject(new Error(res.msg || '请求失败'))
      }
      response.data = res.data ?? res
    }
    return response
  },
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('admin_token')
      window.location.href = '/login'
    }
    const msg = error.response?.data?.msg || error.message || '请求失败'
    return Promise.reject(new Error(msg))
  },
)

export default http

// Auth
export function loginApi(data: { username: string; password: string }) {
  return http.post<{ token: string; user: any }>('/auth/admin-login', data)
}

export function getAdminInfo() {
  return http.get<any>('/auth/info')
}

// Users
export function getUserList(keyword: string, page: number) {
  return http.get<any>('/admin/users', { params: { keyword, page } })
}

export function createUser(data: { username: string; nickname: string; password: string }) {
  return http.post<any>('/admin/users', data)
}

export function resetUserPassword(id: number) {
  return http.post<any>(`/admin/users/reset-password/${id}`)
}

export function toggleUserStatus(id: number) {
  return http.post(`/admin/users/toggle-status/${id}`)
}

export function deleteUser(id: number) {
  return http.delete(`/admin/users/${id}`)
}

export function updateUser(id: number, data: { nickname?: string; password?: string }) {
  return http.post(`/admin/users/update/${id}`, data)
}

// CS
export function getCsList() {
  return http.get<any[]>('/cs/list')
}

export function setCs(userId: number) {
  return http.post('/admin/cs/set', { user_id: userId })
}

export function removeCs(id: number) {
  return http.post(`/admin/cs/remove/${id}`)
}

// Albums
export function getAlbumList() {
  return http.get<any[]>('/albums')
}

export function getAlbumDetail(id: number) {
  return http.get<any>(`/albums/${id}`)
}

export function createAlbum(formData: FormData) {
  return http.post('/admin/albums', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
}

export function updateAlbum(id: number, formData: FormData) {
  return http.post(`/admin/albums/${id}`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
}

export function deleteAlbum(id: number) {
  return http.delete(`/admin/albums/${id}`)
}

export function uploadAlbumImage(albumId: number, formData: FormData) {
  return http.post(`/admin/albums/${albumId}/images`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
}

export function updateAlbumImage(imageId: number, data: { remark: string; sort_order: number }) {
  return http.post(`/admin/albums/images/${imageId}`, data)
}

export function deleteAlbumImage(imageId: number) {
  return http.delete(`/admin/albums/images/${imageId}`)
}

// Chat
export function getConversations(params?: { customer_service_id?: number; user_id?: number }) {
  return http.get<any>('/admin/chat/conversations', { params })
}

export function getChatMessages(conversationId: number) {
  return http.get<any[]>(`/chat/messages/${conversationId}`)
}

// Settings
export function getSettings() {
  return http.get<any>('/admin/settings')
}

export function saveSettings(data: Record<string, any>) {
  return http.post('/admin/settings', data)
}

// Games
export function getGameList() {
  return http.get<any[]>('/games')
}

export function createGame(formData: FormData) {
  return http.post('/admin/games', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
}

export function updateGame(id: number, formData: FormData) {
  return http.post(`/admin/games/${id}`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
}

export function deleteGame(id: number) {
  return http.delete(`/admin/games/${id}`)
}

// Quick Replies
export function getQuickReplyList() {
  return http.get<any>('/admin/quick-replies')
}

export function createQuickReply(data: { title: string; content: string; sort_order: number }) {
  return http.post<any>('/admin/quick-replies', data)
}

export function updateQuickReply(id: number, data: { title?: string; content?: string; sort_order?: number }) {
  return http.post(`/admin/quick-replies/${id}`, data)
}

export function deleteQuickReply(id: number) {
  return http.delete(`/admin/quick-replies/${id}`)
}
