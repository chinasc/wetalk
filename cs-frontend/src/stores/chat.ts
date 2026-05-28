import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { http, clearMessages as apiClearMessages, deleteConversation as apiDeleteConv, recallMessage as apiRecall, deleteMessage as apiDeleteMsg } from '../api'
import { wsService } from '../services/websocket'
import { playNotification } from '../services/notification'

export interface ChatMessage {
  id: number
  conversation_id: number
  sender_id: number
  sender_type: string
  type: string
  content: string
  created_at: string
}

export interface ConvItem {
  id: number
  user_id: number
  user_nickname: string
  user_avatar: string
  last_message: string
  last_message_type: string
  last_message_time: string
  unread_count: number
}

export const useChatStore = defineStore('chat', () => {
  const conversations = ref<ConvItem[]>([])
  const currentMessages = ref<ChatMessage[]>([])
  const currentConversationId = ref<number | null>(null)
  const loading = ref(false)
  const sending = ref(false)
  const DRAFT_KEY = 'cs_chat_drafts'

  function loadDrafts(): Map<number, string> {
    try {
      const raw = localStorage.getItem(DRAFT_KEY)
      if (!raw) return new Map()
      const obj = JSON.parse(raw)
      return new Map(Object.entries(obj).map(([k, v]) => [Number(k), v as string]))
    } catch {
      return new Map()
    }
  }

  function persistDrafts(drafts: Map<number, string>) {
    const obj: Record<number, string> = {}
    drafts.forEach((v, k) => { obj[k] = v })
    localStorage.setItem(DRAFT_KEY, JSON.stringify(obj))
  }

  const drafts = loadDrafts()

  const totalUnread = computed(() =>
    conversations.value.reduce((sum, c) => sum + (c.unread_count || 0), 0),
  )

  let wsInited = false

  function extractData<T>(res: { data: any }): T {
    const d = res.data
    if (d && typeof d === 'object' && 'code' in d) {
      if (d.code !== 0 && d.code !== 200) {
        throw new Error(d.msg || d.message || '请求失败')
      }
      return d.data as T
    }
    return d as T
  }

  function initWsListeners() {
    if (wsInited) return
    wsInited = true

    wsService.onMessage((data: any) => {
      const msg = data as ChatMessage
      const myId = Number(localStorage.getItem('cs_user') ? JSON.parse(localStorage.getItem('cs_user')!).id : 0)
      if (msg.sender_id !== myId) playNotification()

      if (currentConversationId.value && msg.conversation_id === currentConversationId.value) {
        const exists = currentMessages.value.some((m) => m.id === msg.id)
        if (!exists) {
          currentMessages.value = [...currentMessages.value, msg]
        }
        http.post('/chat/mark-read', { conversation_id: msg.conversation_id }).catch(() => {})
        wsService.sendRead(msg.conversation_id)
      } else {
        const conv = conversations.value.find((c) => c.id === msg.conversation_id)
        if (conv) {
          conv.unread_count = (conv.unread_count || 0) + 1
        }
      }
      loadConversations().catch(() => {})
    })

    wsService.onRecall((data: any) => {
      const idx = currentMessages.value.findIndex((m) => m.id === data.id)
      if (idx !== -1) {
        currentMessages.value[idx] = { ...currentMessages.value[idx], type: 'system', content: '消息已撤回' }
        currentMessages.value = [...currentMessages.value]
      }
    })
  }

  function resetWsListeners() {
    wsInited = false
  }

  async function loadConversations() {
    const data = extractData<ConvItem[]>(await http.get('/chat/conversations'))
    conversations.value = Array.isArray(data) ? data : []
  }

  async function loadMessages(convId: number, beforeId?: number) {
    currentConversationId.value = convId
    loading.value = true
    try {
      const params: Record<string, unknown> = {}
      if (beforeId) params.before_id = beforeId
      const msgs = extractData<ChatMessage[]>(await http.get(`/chat/messages/${convId}`, { params }))
      const arr = Array.isArray(msgs) ? msgs : []
      if (beforeId) {
        currentMessages.value = [...arr, ...currentMessages.value]
      } else {
        currentMessages.value = arr
      }
    } finally {
      loading.value = false
    }
  }

  async function sendTextMessage(convId: number, content: string) {
    sending.value = true
    try {
      const msg = extractData<ChatMessage>(await http.post('/chat/send', {
        conversation_id: convId,
        type: 'text',
        content,
      }))
      if (msg && !currentMessages.value.some((m) => m.id === msg.id)) {
        currentMessages.value = [...currentMessages.value, msg]
      }
      loadConversations().catch(() => {})
    } finally {
      sending.value = false
    }
  }

  async function sendImage(convId: number, file: File) {
    sending.value = true
    try {
      const formData = new FormData()
      formData.append('conversation_id', String(convId))
      formData.append('type', 'image')
      formData.append('file', file)
      const msg = extractData<ChatMessage>(await http.post('/chat/send', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      }))
      if (msg && !currentMessages.value.some((m) => m.id === msg.id)) {
        currentMessages.value = [...currentMessages.value, msg]
      }
      loadConversations().catch(() => {})
    } finally {
      sending.value = false
    }
  }

  async function sendAlbumMessage(convId: number, albumId: number, albumTitle: string, albumCover: string) {
    sending.value = true
    try {
      const content = JSON.stringify({ id: albumId, title: albumTitle, cover: albumCover })
      const msg = extractData<ChatMessage>(await http.post('/chat/send', {
        conversation_id: convId,
        type: 'album',
        content,
      }))
      if (msg && !currentMessages.value.some((m) => m.id === msg.id)) {
        currentMessages.value = [...currentMessages.value, msg]
      }
      loadConversations().catch(() => {})
    } finally {
      sending.value = false
    }
  }

  async function markConversationRead(convId: number) {
    http.post('/chat/mark-read', { conversation_id: convId }).catch(() => {})
    wsService.sendRead(convId)
    const conv = conversations.value.find((c) => c.id === convId)
    if (conv) conv.unread_count = 0
  }

  function clearCurrentChat() {
    currentConversationId.value = null
    currentMessages.value = []
  }

  function saveDraft(convId: number, text: string) {
    if (text.trim()) {
      drafts.set(convId, text)
    } else {
      drafts.delete(convId)
    }
    persistDrafts(drafts)
  }

  function getDraft(convId: number): string {
    return drafts.get(convId) || ''
  }

  async function clearMessages(convId: number) {
    await apiClearMessages(convId)
    if (currentConversationId.value === convId) {
      currentMessages.value = []
    }
    await loadConversations()
  }

  async function deleteConversation(convId: number) {
    await apiDeleteConv(convId)
    if (currentConversationId.value === convId) {
      currentMessages.value = []
      currentConversationId.value = null
    }
    drafts.delete(convId)
    conversations.value = conversations.value.filter((c) => c.id !== convId)
  }

  async function recallMessage(msgId: number) {
    await apiRecall(msgId)
    const idx = currentMessages.value.findIndex((m) => m.id === msgId)
    if (idx !== -1) {
      currentMessages.value[idx] = { ...currentMessages.value[idx], type: 'system', content: '消息已撤回' }
      currentMessages.value = [...currentMessages.value]
    }
  }

  async function deleteMessage(msgId: number) {
    await apiDeleteMsg(msgId)
    currentMessages.value = currentMessages.value.filter((m) => m.id !== msgId)
  }

  return {
    conversations,
    currentMessages,
    currentConversationId,
    loading,
    sending,
    totalUnread,
    initWsListeners,
    resetWsListeners,
    loadConversations,
    loadMessages,
    sendTextMessage,
    sendImage,
    sendAlbumMessage,
    markConversationRead,
    clearCurrentChat,
    saveDraft,
    getDraft,
    clearMessages,
    deleteConversation,
    recallMessage,
    deleteMessage,
  }
})
