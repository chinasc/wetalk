import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Conversation, ChatMessage } from '../types'
import { chatApi } from '../api'
import { wsService } from '../services/websocket'
import { playNotification } from '../services/notification'
import { useAuthStore } from './auth'

export const useChatStore = defineStore('chat', () => {
  const conversations = ref<Conversation[]>([])
  const currentMessages = ref<ChatMessage[]>([])
  const currentConversationId = ref<number | null>(null)
  const connected = ref(false)
  let wsInited = false

  const totalUnread = computed(() =>
    conversations.value.reduce((sum, c) => sum + c.unread_count, 0),
  )

  const sortedConversations = computed(() =>
    [...conversations.value].sort(
      (a, b) => new Date(b.updated_at).getTime() - new Date(a.updated_at).getTime(),
    ),
  )

  function setupWsListeners() {
    if (wsInited) return
    wsInited = true

    wsService.on('auth.success', () => {
      connected.value = true
      // Reconnect: refresh data to catch up on missed messages
      fetchConversations().catch(() => {})
      if (currentConversationId.value) {
        fetchMessages(currentConversationId.value).catch(() => {})
      }
    })

    wsService.on('message.new', (data) => {
      const msg = data as unknown as ChatMessage
      const authStore = useAuthStore()
      if (msg.sender_id !== authStore.user?.id) playNotification()

      if (msg.conversation_id === currentConversationId.value) {
        const exists = currentMessages.value.some((m) => m.id === msg.id)
        if (!exists) {
          currentMessages.value.push(msg)
        }
        wsService.send('chat.read', { conversation_id: currentConversationId.value })
        chatApi.markRead(msg.conversation_id).catch(() => {})
      }
      const conv = conversations.value.find((c) => c.id === msg.conversation_id)
      if (conv) {
        conv.last_message = msg.type === 'text' ? msg.content : msg.type === 'album' ? '[相册]' : '[图片]'
        conv.last_message_time = msg.created_at
        conv.updated_at = msg.created_at
        if (msg.conversation_id !== currentConversationId.value) {
          conv.unread_count = (conv.unread_count || 0) + 1
        }
      }
    })

    wsService.on('message.recalled', (data) => {
      const recalled = data as any
      if (recalled.conversation_id && recalled.conversation_id !== currentConversationId.value) return
      const idx = currentMessages.value.findIndex((m) => m.id === recalled.id)
      if (idx !== -1) {
        currentMessages.value[idx] = { ...currentMessages.value[idx], type: 'system', content: '消息已撤回' }
        currentMessages.value = [...currentMessages.value]
      }
    })

    wsService.on('message.read', () => {
      // Could update read status display
    })

    wsService.on('pong', () => {
      connected.value = true
    })
  }

  async function fetchConversations() {
    const res = await chatApi.getConversations()
    conversations.value = res.data
  }

  async function createConversation(csId: number) {
    const res = await chatApi.createConversation(csId)
    const exists = conversations.value.find((c) => c.id === res.data.id)
    if (!exists) {
      conversations.value.push(res.data)
    }
    return res.data
  }

  async function fetchMessages(conversationId: number, beforeId?: number) {
    currentConversationId.value = conversationId
    const res = await chatApi.getMessages(conversationId, beforeId)
    if (beforeId) {
      currentMessages.value = [...res.data, ...currentMessages.value]
    } else {
      currentMessages.value = res.data
    }
    // Mark as read
    wsService.send('chat.read', { conversation_id: conversationId })
    chatApi.markRead(conversationId).catch(() => {})
    // Update unread count
    const conv = conversations.value.find((c) => c.id === conversationId)
    if (conv) {
      conv.unread_count = 0
    }
    return res.data
  }

  async function sendMessage(conversationId: number, type: string, content: string) {
    const res = await chatApi.send(conversationId, type, content)
    const msg = res.data || res
    if (msg && currentConversationId.value === conversationId && !currentMessages.value.some((m) => m.id === msg.id)) {
      currentMessages.value.push(msg)
    }
    const conv = conversations.value.find((c) => c.id === conversationId)
    if (conv) {
      conv.last_message = type === 'text' ? content : type === 'album' ? '[相册]' : '[图片]'
      conv.last_message_time = msg.created_at || new Date().toISOString()
      conv.updated_at = msg.created_at || new Date().toISOString()
    }
    return msg
  }

  function clearCurrentChat() {
    currentConversationId.value = null
    currentMessages.value = []
  }

  async function recallMessage(msgId: number) {
    await chatApi.recallMessage(msgId)
    const idx = currentMessages.value.findIndex((m) => m.id === msgId)
    if (idx !== -1) {
      currentMessages.value[idx] = { ...currentMessages.value[idx], type: 'system', content: '消息已撤回' }
      currentMessages.value = [...currentMessages.value]
    }
  }

  async function deleteMessage(msgId: number) {
    await chatApi.deleteMessage(msgId)
    currentMessages.value = currentMessages.value.filter((m) => m.id !== msgId)
  }

  return {
    conversations,
    currentMessages,
    currentConversationId,
    connected,
    totalUnread,
    sortedConversations,
    setupWsListeners,
    fetchConversations,
    createConversation,
    fetchMessages,
    sendMessage,
    clearCurrentChat,
    recallMessage,
    deleteMessage,
  }
})
