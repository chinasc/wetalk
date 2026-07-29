import axios from 'axios'
import type {
  ApiResponse,
  LoginForm,
  UserInfo,
  Conversation,
  ChatMessage,
  Album,
  AlbumDetail,
  QuickReply,
} from '../types'

const http = axios.create({
  baseURL: '/api',
  timeout: 15000,
})

// JWT interceptor: attach token to every request
http.interceptors.request.use((config) => {
  const token = localStorage.getItem('cs_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Response interceptor: handle 401 by forcing re-login
http.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('cs_token')
      localStorage.removeItem('cs_user')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  },
)

// Unwrap ApiResponse envelope
function unwrap<T>(response: { data: ApiResponse<T> | T }): T {
  const d = response.data as ApiResponse<T>
  if (d && typeof d === 'object' && 'code' in d) {
    if (d.code !== 0 && d.code !== 200) {
      const err: any = new Error((d as any).msg || '请求失败')
      err.code = d.code
      throw err
    }
    return d.data
  }
  return response.data as T
}

// ============ Auth ============

export async function login(form: LoginForm): Promise<{ token: string; user: UserInfo }> {
  const res = await http.post('/auth/cs-login', form)
  return unwrap(res)
}

export async function getUserInfo(): Promise<UserInfo> {
  const res = await http.get('/auth/info')
  return unwrap(res)
}

// ============ Chat ============

export async function getConversations(): Promise<Conversation[]> {
  const res = await http.get('/chat/conversations')
  return unwrap(res)
}

export async function getMessages(
  conversationId: number,
  beforeId?: number,
): Promise<ChatMessage[]> {
  const params: Record<string, unknown> = {}
  if (beforeId) {
    params.before_id = beforeId
  }
  const res = await http.get(`/chat/messages/${conversationId}`, { params })
  return unwrap(res)
}

export async function sendMessage(
  conversationId: number,
  type: string,
  content: string,
): Promise<ChatMessage> {
  const res = await http.post('/chat/send', {
    conversation_id: conversationId,
    type,
    content,
  })
  return unwrap(res)
}

export async function sendImageMessage(
  conversationId: number,
  file: File,
): Promise<ChatMessage> {
  const formData = new FormData()
  formData.append('conversation_id', String(conversationId))
  formData.append('type', 'image')
  formData.append('file', file)
  const res = await http.post('/chat/send', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  return unwrap(res)
}

export async function markRead(conversationId: number): Promise<void> {
  await http.post('/chat/mark-read', { conversation_id: conversationId })
}

export async function clearMessages(conversationId: number): Promise<void> {
  await http.delete(`/chat/messages/${conversationId}`)
}

export async function deleteConversation(conversationId: number): Promise<void> {
  await http.delete(`/chat/conversation/${conversationId}`)
}

export async function recallMessage(messageId: number): Promise<void> {
  await http.post('/chat/recall', { message_id: messageId })
}

export async function deleteMessage(messageId: number): Promise<void> {
  await http.post('/chat/delete-message', { message_id: messageId })
}

export async function togglePin(conversationId: number): Promise<{ cs_pinned: boolean }> {
  const res = await http.post(`/chat/pin/${conversationId}`)
  return unwrap(res)
}

// ============ Upload ============

export async function uploadImage(file: File): Promise<{ url: string }> {
  const formData = new FormData()
  formData.append('file', file)
  const res = await http.post('/upload/image', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  return unwrap(res)
}

// ============ Albums ============

export async function getAlbums(): Promise<Album[]> {
  const res = await http.get('/albums')
  return unwrap(res)
}

export async function getAlbumDetail(id: number): Promise<AlbumDetail> {
  const res = await http.get(`/albums/${id}`)
  return unwrap(res)
}

export async function createAlbum(formData: FormData): Promise<any> {
  const res = await http.post('/cs/albums', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  return unwrap(res)
}

export async function updateAlbum(id: number, formData: FormData): Promise<any> {
  const res = await http.post(`/cs/albums/${id}`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  return unwrap(res)
}

export async function deleteAlbum(id: number): Promise<void> {
  await http.delete(`/cs/albums/${id}`)
}

export async function uploadAlbumImage(albumId: number, formData: FormData): Promise<any> {
  const res = await http.post(`/cs/albums/${albumId}/images`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  return unwrap(res)
}

export async function updateAlbumImage(imageId: number, data: { remark: string; sort_order: number }): Promise<any> {
  const res = await http.post(`/cs/albums/images/${imageId}`, data)
  return unwrap(res)
}

export async function deleteAlbumImage(imageId: number): Promise<void> {
  await http.delete(`/cs/albums/images/${imageId}`)
}

// ============ Quick Replies ============

export async function getQuickReplies(): Promise<QuickReply[]> {
  const res = await http.get('/quick-replies')
  return unwrap(res)
}

export async function createQuickReply(data: { title: string; content: string; sort_order: number }): Promise<QuickReply> {
  const res = await http.post('/cs/quick-replies', data)
  return unwrap(res)
}

export async function updateQuickReply(id: number, data: { title?: string; content?: string; sort_order?: number }): Promise<QuickReply> {
  const res = await http.post(`/cs/quick-replies/${id}`, data)
  return unwrap(res)
}

export async function deleteQuickReply(id: number): Promise<void> {
  await http.delete(`/cs/quick-replies/${id}`)
}

// ============ CS Users ============

export async function getCsUsers(keyword?: string, page?: number): Promise<any> {
  const params: Record<string, unknown> = {}
  if (keyword) params.keyword = keyword
  if (page) params.page = page
  const res = await http.get('/cs/users', { params })
  return unwrap(res)
}

export async function createCsUser(data: { username: string; nickname?: string; password?: string }): Promise<any> {
  const res = await http.post('/cs/users', data)
  return unwrap(res)
}

export async function resetUserPassword(id: number): Promise<any> {
  const res = await http.post(`/cs/users/${id}/reset-password`)
  return unwrap(res)
}

export async function updateCsUser(id: number, data: { nickname?: string; password?: string }): Promise<any> {
  const res = await http.post(`/cs/users/${id}`, data)
  return unwrap(res)
}

export async function deleteCsUser(id: number): Promise<void> {
  await http.delete(`/cs/users/${id}`)
}

export async function csCreateConversation(userId: number): Promise<any> {
  const res = await http.post(`/cs/users/${userId}/chat`)
  return unwrap(res)
}

export { http }
