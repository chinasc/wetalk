<script setup lang="ts">
import { ref, onMounted, nextTick, onUnmounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { showToast, showLoadingToast, closeToast, showImagePreview } from 'vant'
import type { ChatMessage } from '../types'
import { useChatStore } from '../stores/chat'
import { uploadApi } from '../api'

const route = useRoute()
const router = useRouter()
const chatStore = useChatStore()

const conversationId = Number(route.params.id)
const messageInput = ref('')
const messagesContainer = ref<HTMLElement | null>(null)
const sending = ref(false)
const loadingMore = ref(false)

const messages = computed(() => chatStore.currentMessages)
const conversation = computed(() =>
  chatStore.conversations.find((c) => c.id === conversationId),
)

const csName = computed(() => conversation.value?.customer_service_name || '客服')
const csAvatar = computed(() => conversation.value?.customer_service_avatar || '')

onMounted(async () => {
  try {
    await chatStore.fetchMessages(conversationId)
    await nextTick()
    scrollToBottom()
  } catch {
    showToast('加载消息失败')
  }
})

onUnmounted(() => {
  chatStore.clearCurrentChat()
})

// Auto-scroll when new WS messages arrive
watch(() => chatStore.currentMessages.length, (newLen, oldLen) => {
  if (newLen > oldLen) {
    const el = messagesContainer.value
    if (el) {
      const nearBottom = el.scrollHeight - el.scrollTop - el.clientHeight < 150
      if (nearBottom) {
        scrollToBottom()
      }
    }
  }
})

function scrollToBottom() {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
  })
}

async function handleLoadMore() {
  if (loadingMore.value || messages.value.length === 0) return
  loadingMore.value = true
  try {
    const oldestId = messages.value[0]?.id
    if (oldestId) {
      await chatStore.fetchMessages(conversationId, oldestId)
    }
  } finally {
    loadingMore.value = false
  }
}

async function sendMessage() {
  const text = messageInput.value.trim()
  if (!text || sending.value) return

  sending.value = true
  messageInput.value = ''

  try {
    await chatStore.sendMessage(conversationId, 'text', text)
    scrollToBottom()
  } catch {
    showToast('发送失败')
  } finally {
    sending.value = false
  }
}

async function handleImageUpload() {
  const input = document.createElement('input')
  input.type = 'file'
  input.accept = 'image/*'
  input.onchange = async (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0]
    if (!file) return

    showLoadingToast({ message: '发送中...', forbidClick: true, duration: 0 })
    try {
      // Upload image first
      const formData = new FormData()
      formData.append('file', file)
      const uploadRes = await uploadApi.uploadImage(formData)
      const imageUrl = uploadRes.data.url

      // Send as message via HTTP
      await chatStore.sendMessage(conversationId, 'image', imageUrl)
      scrollToBottom()
    } catch {
      showToast('图片发送失败')
    } finally {
      closeToast()
    }
  }
  input.click()
}

function previewImage(url: string) {
  showImagePreview([url])
}

function parseAlbumCover(content: string): string {
  try {
    const data = JSON.parse(content)
    return data.cover || ''
  } catch {
    return ''
  }
}

function parseAlbumTitle(content: string): string {
  try {
    const data = JSON.parse(content)
    return data.title || '相册'
  } catch {
    return '相册'
  }
}

function openAlbum(msg: ChatMessage) {
  try {
    const data = JSON.parse(msg.content)
    if (data.id) {
      router.push(`/album/${data.id}`)
    }
  } catch {
    // ignore
  }
}

function formatTime(dateStr: string) {
  const date = new Date(dateStr)
  return date.toLocaleTimeString('zh-CN', { hour: '2-digit', minute: '2-digit' })
}

function isSelf(msg: ChatMessage) {
  return msg.sender_type === 'user'
}

function goBack() {
  router.back()
}
</script>

<template>
  <div class="chat-page">
    <!-- Header -->
    <van-nav-bar
      :title="csName"
      left-arrow
      @click-left="goBack"
      :border="false"
      class="chat-nav"
    />

    <!-- Messages -->
    <div
      ref="messagesContainer"
      class="chat-messages hide-scrollbar"
      @scroll="onScroll"
    >
      <div class="load-more-wrap" v-if="messages.length > 20">
        <van-button
          size="small"
          :loading="loadingMore"
          @click="handleLoadMore"
        >
          加载更多
        </van-button>
      </div>

      <div
        v-for="msg in messages"
        :key="msg.id"
        :class="['chat-message-row', isSelf(msg) ? 'self' : 'other']"
      >
        <!-- Avatar for other -->
        <van-image
          v-if="!isSelf(msg)"
          round
          width="36"
          height="36"
          fit="cover"
          :src="csAvatar"
          class="msg-avatar"
        >
          <template #error>
            <van-icon name="manager-o" size="20" />
          </template>
        </van-image>

        <div class="msg-body">
          <!-- Text bubble -->
          <div v-if="msg.type === 'text'" class="msg-bubble">
            {{ msg.content }}
          </div>
          <!-- Image bubble -->
          <div v-else-if="msg.type === 'image'" class="msg-image-wrap">
            <van-image
              :src="msg.content"
              fit="cover"
              width="160"
              radius="8"
              class="msg-image"
              @click="previewImage(msg.content)"
            >
              <template #error>
                <div class="msg-image-error">
                  <van-icon name="photo-o" size="24" />
                </div>
              </template>
            </van-image>
          </div>
          <!-- Album bubble -->
          <div v-else-if="msg.type === 'album'" class="msg-album-bubble" @click="openAlbum(msg)">
            <van-image
              :src="parseAlbumCover(msg.content)"
              fit="cover"
              width="160"
              height="120"
              radius="8"
              class="msg-album-cover"
            >
              <template #error>
                <div class="msg-image-error">
                  <van-icon name="photo-o" size="24" />
                </div>
              </template>
            </van-image>
            <div class="msg-album-info">
              <van-icon name="photo-o" size="14" color="#999" />
              <span class="msg-album-title">{{ parseAlbumTitle(msg.content) }}</span>
            </div>
          </div>
          <div class="msg-time">{{ formatTime(msg.created_at) }}</div>
        </div>

        <!-- Avatar for self -->
        <div v-if="isSelf(msg)" class="msg-avatar-self">
          <van-icon name="user-o" size="20" color="#fff" />
        </div>
      </div>

      <div class="chat-bottom-spacer"></div>
    </div>

    <!-- Input bar -->
    <div class="chat-input-bar">
      <van-button
        icon="photo-o"
        size="small"
        round
        class="chat-input-btn"
        @click="handleImageUpload"
      />
      <van-field
        v-model="messageInput"
        placeholder="输入消息..."
        type="textarea"
        rows="1"
        autosize
        class="chat-input-field"
        @keydown.enter.prevent="sendMessage"
      />
      <van-button
        type="primary"
        size="small"
        round
        class="chat-send-btn"
        :disabled="!messageInput.trim()"
        @click="sendMessage"
      >
        发送
      </van-button>
    </div>
  </div>
</template>

<style scoped>
.chat-page {
  height: 100vh;
  height: 100dvh;
  display: flex;
  flex-direction: column;
  background: #f0f0f0;
}

.chat-nav {
  background: #fff;
  flex-shrink: 0;
}

.chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 12px 16px;
  -webkit-overflow-scrolling: touch;
}

.load-more-wrap {
  text-align: center;
  padding: 8px 0 16px;
}

.chat-message-row {
  display: flex;
  align-items: flex-start;
  margin-bottom: 16px;
}

.chat-message-row.self {
  flex-direction: row-reverse;
}

.msg-avatar {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f2f3f5;
  overflow: hidden;
}

.msg-avatar-self {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #1989fa;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.msg-body {
  max-width: 65%;
  margin: 0 10px;
}

.msg-bubble {
  padding: 10px 14px;
  border-radius: 12px;
  font-size: 15px;
  line-height: 1.5;
  word-break: break-word;
}

.chat-message-row.other .msg-bubble {
  background: #fff;
  color: #323233;
  border-top-left-radius: 4px;
}

.chat-message-row.self .msg-bubble {
  background: #95ec69;
  color: #000;
  border-top-right-radius: 4px;
}

.msg-image-wrap {
  border-radius: 8px;
  overflow: hidden;
}

.msg-image {
  display: block;
}

.msg-image-error {
  width: 160px;
  height: 120px;
  background: #f2f3f5;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
}

.msg-album-bubble {
  border-radius: 12px;
  overflow: hidden;
  background: #fff;
  border: 1px solid #eee;
  cursor: pointer;
}

.msg-album-cover {
  display: block;
}

.msg-album-info {
  display: flex;
  align-items: center;
  padding: 8px 10px;
  gap: 4px;
}

.msg-album-title {
  font-size: 13px;
  color: #666;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.msg-time {
  font-size: 11px;
  color: #999;
  margin-top: 4px;
  text-align: center;
}

.chat-message-row.self .msg-time {
  text-align: right;
}

.chat-message-row.other .msg-time {
  text-align: left;
}

.chat-bottom-spacer {
  height: 4px;
}

.chat-input-bar {
  display: flex;
  align-items: flex-end;
  padding: 8px 12px;
  padding-bottom: calc(8px + var(--wt-safe-bottom));
  background: #fff;
  border-top: 1px solid #ebedf0;
  gap: 8px;
  flex-shrink: 0;
}

.chat-input-btn {
  flex-shrink: 0;
  border: none;
  background: transparent;
  min-height: 44px;
  min-width: 44px;
}

.chat-input-field {
  flex: 1;
  padding: 0;
  background: #f2f3f5;
  border-radius: 20px;
  overflow: hidden;
}

.chat-input-field :deep(.van-field__control) {
  padding: 10px 16px;
  font-size: 15px;
  max-height: 100px;
}

.chat-input-field :deep(.van-field__body) {
  padding: 0;
}

.chat-send-btn {
  flex-shrink: 0;
  min-height: 44px;
  min-width: 60px;
}
</style>
