<template>
  <div class="chat-page">
    <van-nav-bar
      :title="conversationTitle"
      left-arrow
      fixed
      placeholder
      @click-left="onBack"
    />

    <div
      ref="messageListRef"
      class="message-list"
      @scroll="onScroll"
    >
      <div
        v-if="hasMore"
        class="load-more"
        @click="loadMore"
      >
        <van-loading v-if="chatStore.loading" size="18" />
        <span v-else class="load-more-text">加载更多</span>
      </div>

      <div
        v-for="msg in messages"
        :key="msg.id"
        class="message-wrapper"
        :class="msg.sender_type === 'customer_service' ? 'message-self' : 'message-other'"
      >
        <!-- Avatar -->
        <van-image
          v-if="msg.sender_type !== 'customer_service'"
          round
          width="36"
          height="36"
          :src="userAvatar"
          fit="cover"
          class="msg-avatar"
        />

        <!-- Bubble -->
        <div class="msg-bubble" @click="onBubbleClick(msg)">
          <!-- System message (recalled) -->
          <span v-if="msg.type === 'system'" class="msg-system">{{ msg.content }}</span>

          <!-- Text message -->
          <span v-if="msg.type === 'text'" class="msg-text">{{ msg.content }}</span>

          <!-- Image message -->
          <van-image
            v-else-if="msg.type === 'image'"
            :src="msg.content"
            fit="cover"
            class="msg-image"
          />

          <!-- Album message -->
          <div v-else-if="msg.type === 'album'" class="msg-album">
            <van-image
              :src="albumCover(msg.content)"
              fit="cover"
              class="album-cover"
            />
            <div class="album-info">
              <van-icon name="photo-o" size="14" color="#999" />
              <span class="album-title">{{ albumTitle(msg.content) }}</span>
            </div>
          </div>
        </div>

        <!-- Self avatar -->
        <van-image
          v-if="msg.sender_type === 'customer_service'"
          round
          width="36"
          height="36"
          :src="csAvatar"
          fit="cover"
          class="msg-avatar"
        />
      </div>

      <div v-if="messages.length === 0 && !chatStore.loading" class="empty-chat">
        <van-empty description="暂无消息" image="search" />
      </div>
    </div>

    <!-- Bottom toolbar -->
    <div class="chat-toolbar">
      <div class="plus-trigger" @click="showPlusMenu = !showPlusMenu">
        <van-icon :name="showPlusMenu ? 'cross' : 'add-o'" size="24" color="#666" />
      </div>
      <van-field
        v-model="inputText"
        type="textarea"
        rows="1"
        autosize
        maxlength="2000"
        placeholder="输入消息..."
        class="chat-input"
        @keyup.enter.prevent="onSend"
        @focus="showPlusMenu = false"
      />
      <van-button
        type="primary"
        size="small"
        class="send-btn"
        :disabled="!inputText.trim()"
        :loading="chatStore.sending"
        @click="onSend"
      >
        发送
      </van-button>
    </div>

    <!-- Plus menu panel -->
    <div v-if="showPlusMenu" class="plus-menu">
      <div class="plus-menu-item" @click="showPlusMenu = false; showQuickReplySheet = true">
        <van-icon name="chat-o" size="22" />
        <span>常用语</span>
      </div>
      <div class="plus-menu-item" @click="showPlusMenu = false; showAlbumSheet = true">
        <van-icon name="photo-o" size="22" />
        <span>相册</span>
      </div>
      <div class="plus-menu-item" @click="showPlusMenu = false; onPickImage()">
        <van-icon name="description" size="22" />
        <span>图片</span>
      </div>
    </div>

    <!-- Album ActionSheet -->
    <van-action-sheet
      v-model:show="showAlbumSheet"
      title="选择相册"
      class="album-sheet"
    >
      <div class="album-list">
        <van-loading v-if="albumsLoading" class="album-loading" />
        <van-empty v-else-if="albums.length === 0" description="暂无相册" />
        <div
          v-for="album in albums"
          :key="album.id"
          class="album-item"
          @click="onSelectAlbum(album)"
        >
          <van-image
            :src="album.cover"
            width="60"
            height="60"
            fit="cover"
            radius="6"
          />
          <div class="album-item-info">
            <div class="album-item-title">{{ album.name || album.title }}</div>
            <div class="album-item-desc">{{ album.images_count || album.photo_count || 0 }} 张照片</div>
          </div>
        </div>
      </div>
    </van-action-sheet>

    <!-- Quick Reply ActionSheet -->
    <van-action-sheet
      v-model:show="showQuickReplySheet"
      title="常用语"
      class="album-sheet"
    >
      <div class="album-list">
        <van-loading v-if="quickRepliesLoading" class="album-loading" />
        <van-empty v-else-if="quickReplies.length === 0" description="暂无常用语" />
        <div
          v-for="item in quickReplies"
          :key="item.id"
          class="quick-reply-item"
          @click="onSelectQuickReply(item)"
        >
          <div class="quick-reply-title">{{ item.title }}</div>
          <div class="quick-reply-content">{{ item.content }}</div>
        </div>
      </div>
    </van-action-sheet>

    <!-- Hidden file input for image upload -->
    <input
      ref="fileInputRef"
      type="file"
      accept="image/*"
      multiple
      style="display: none"
      @change="onFileSelected"
    />

    <!-- Image preview -->
    <van-image-preview v-model:show="previewVisible" :images="previewImages" :closeable="true" @close="previewVisible = false" />

    <!-- Message action sheet -->
    <van-action-sheet v-model:show="actionVisible" :actions="actionItems" @select="onActionSelect" cancel-text="取消" />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { showToast, showLoadingToast, closeToast } from 'vant'
import type { ChatMessage, ConvItem } from '../stores/chat'
import { useChatStore } from '../stores/chat'
import { useAuthStore } from '../stores/auth'
import { http, getAlbums, getQuickReplies } from '../api'
import type { QuickReply } from '../types'

const route = useRoute()
const router = useRouter()
const chatStore = useChatStore()
const authStore = useAuthStore()

const conversationId = computed(() => Number(route.params.id))
const messages = computed(() => chatStore.currentMessages)

const inputText = ref('')
const messageListRef = ref<HTMLElement | null>(null)
const fileInputRef = ref<HTMLInputElement | null>(null)
const previewVisible = ref(false)
const previewImages = ref<string[]>([])
const showPlusMenu = ref(false)

// Message action sheet
const actionVisible = ref(false)
const actionItems = ref<{ name: string; value: string; color?: string }[]>([])
const activeMsg = ref<any>(null)

// Album sheet
const showAlbumSheet = ref(false)
const albums = ref<any[]>([])
const albumsLoading = ref(false)

// Quick reply sheet
const showQuickReplySheet = ref(false)
const quickReplies = ref<QuickReply[]>([])
const quickRepliesLoading = ref(false)

// Conversation info for title/avatar
const conversationInfo = ref<ConvItem | null>(null)

const conversationTitle = computed(() => {
  return conversationInfo.value?.user_nickname || '聊天'
})

const csAvatar = computed(() => {
  return authStore.user?.avatar || 'https://fastly.jsdelivr.net/npm/@vant/assets/cat.jpeg'
})

const userAvatar = computed(() => {
  return conversationInfo.value?.user_avatar || 'https://fastly.jsdelivr.net/npm/@vant/assets/cat.jpeg'
})

// Track if more history is available
const hasMore = ref(true)

function albumCover(content: string): string {
  try {
    const data = JSON.parse(content)
    return data.cover || ''
  } catch {
    return ''
  }
}

function albumTitle(content: string): string {
  try {
    const data = JSON.parse(content)
    return data.title || '相册'
  } catch {
    return '相册'
  }
}

function previewImage(url: string): void {
  previewImages.value = [url]
  previewVisible.value = true
}

function scrollToBottom(smooth = false): void {
  nextTick(() => {
    const el = messageListRef.value
    if (el) {
      el.scrollTo({
        top: el.scrollHeight,
        behavior: smooth ? 'smooth' : 'instant',
      })
    }
  })
}

function onBack(): void {
  chatStore.saveDraft(conversationId.value, inputText.value)
  chatStore.clearCurrentChat()
  router.push('/')
}

// ============ Send ============

async function onSend(): Promise<void> {
  const text = inputText.value.trim()
  if (!text) return
  inputText.value = ''
  chatStore.saveDraft(conversationId.value, '')
  try {
    await chatStore.sendTextMessage(conversationId.value, text)
    scrollToBottom(true)
  } catch {
    showToast('发送失败')
  }
}

// ============ Image Upload ============

function onPickImage(): void {
  fileInputRef.value?.click()
}

async function onFileSelected(event: Event): Promise<void> {
  const target = event.target as HTMLInputElement
  const fileList = target.files
  if (!fileList || fileList.length === 0) return

  const files = Array.from(fileList)
  target.value = ''

  const validFiles: File[] = []
  for (const file of files) {
    const isImage = file.type.startsWith('image/') || /\.(jpe?g|png|gif|bmp|webp|heic|heif)$/i.test(file.name)
    if (!isImage) continue
    if (file.size > 10 * 1024 * 1024) continue
    validFiles.push(file)
  }

  if (validFiles.length === 0) {
    showToast('请选择有效的图片文件')
    return
  }

  showLoadingToast({ message: `发送中 (0/${validFiles.length})...`, forbidClick: true, duration: 0 })
  try {
    for (let i = 0; i < validFiles.length; i++) {
      showLoadingToast({ message: `发送中 (${i + 1}/${validFiles.length})...`, forbidClick: true, duration: 0 })
      await chatStore.sendImage(conversationId.value, validFiles[i])
    }
    scrollToBottom(true)
  } catch {
    showToast('发送失败')
  } finally {
    closeToast()
  }
}

// ============ Album ============

async function loadAlbums(): Promise<void> {
  albumsLoading.value = true
  try {
    albums.value = await getAlbums()
  } catch {
    showToast('获取相册列表失败')
  } finally {
    albumsLoading.value = false
  }
}

async function onSelectAlbum(album: any): Promise<void> {
  showAlbumSheet.value = false
  showLoadingToast({ message: '发送中...', forbidClick: true, duration: 0 })
  try {
    await chatStore.sendAlbumMessage(
      conversationId.value,
      album.id,
      album.name || album.title,
      album.cover,
    )
    scrollToBottom(true)
  } catch {
    showToast('发送失败')
  } finally {
    closeToast()
  }
}

// ============ Quick Reply ============

async function loadQuickReplies(): Promise<void> {
  quickRepliesLoading.value = true
  try {
    quickReplies.value = await getQuickReplies()
  } catch {
    showToast('获取常用语失败')
  } finally {
    quickRepliesLoading.value = false
  }
}

async function onSelectQuickReply(item: QuickReply): Promise<void> {
  showQuickReplySheet.value = false
  try {
    await chatStore.sendTextMessage(conversationId.value, item.content)
    scrollToBottom(true)
  } catch {
    showToast('发送失败')
  }
}

async function onActionSelect(item: any): Promise<void> {
  actionVisible.value = false
  if (!activeMsg.value) return
  const msg = activeMsg.value
  if (item.value === 'preview') {
    previewImages.value = [msg.content]
    previewVisible.value = true
  } else if (item.value === 'album') {
    try {
      const data = JSON.parse(msg.content)
      if (data.id) router.push(`/album/${data.id}`)
    } catch { /* ignore */ }
  } else if (item.value === 'recall') {
    try { await chatStore.recallMessage(msg.id) } catch { showToast('撤回失败') }
  } else if (item.value === 'delete') {
    try { await chatStore.deleteMessage(msg.id) } catch { showToast('删除失败') }
  }
}

function onBubbleClick(msg: any): void {
  if (msg.type === 'system') return
  activeMsg.value = msg
  const myId = authStore.user?.id
  const isSelfMsg = msg.sender_id === myId
  const actions: { name: string; value: string; color?: string }[] = []
  if (msg.type === 'image') actions.push({ name: '查看大图', value: 'preview' })
  if (msg.type === 'album') actions.push({ name: '查看相册', value: 'album' })
  if (isSelfMsg) actions.push({ name: '撤回', value: 'recall' })
  actions.push({ name: '删除', value: 'delete', color: '#ee0a24' })
  actionItems.value = actions
  actionVisible.value = true
}

// ============ Scroll / Load More ============

function onScroll(): void {
  const el = messageListRef.value
  if (!el) return
  if (el.scrollTop < 50 && hasMore.value && !chatStore.loading) {
    loadMore()
  }
}

async function loadMore(): Promise<void> {
  if (!messages.value.length) return
  const oldestId = messages.value[0].id
  const previousHeight = messageListRef.value?.scrollHeight || 0

  await chatStore.loadMessages(conversationId.value, oldestId)

  // Maintain scroll position after prepending
  nextTick(() => {
    const el = messageListRef.value
    if (el) {
      const newHeight = el.scrollHeight
      el.scrollTop = newHeight - previousHeight
    }
  })

  // If we got fewer than expected, no more history
  if (chatStore.currentMessages.length < 20) {
    hasMore.value = false
  }
}

// ============ Lifecycle ============

watch(showAlbumSheet, (visible) => {
  if (visible && albums.value.length === 0) {
    loadAlbums()
  }
})

watch(showQuickReplySheet, (visible) => {
  if (visible && quickReplies.value.length === 0) {
    loadQuickReplies()
  }
})

// Auto-scroll when new WS messages arrive
watch(() => chatStore.currentMessages.length, (newLen, oldLen) => {
  if (newLen > oldLen) {
    const el = messageListRef.value
    if (el) {
      const nearBottom = el.scrollHeight - el.scrollTop - el.clientHeight < 150
      if (nearBottom) {
        scrollToBottom(true)
      }
    }
  }
})

onMounted(async () => {
  inputText.value = chatStore.getDraft(conversationId.value)

  try {
    const convs = chatStore.conversations.length > 0
      ? chatStore.conversations
      : (await chatStore.loadConversations(), chatStore.conversations)
    const conv = convs.find((c: any) => c.id === conversationId.value)
    if (conv) {
      conversationInfo.value = conv
    }
  } catch {
    // ignore
  }

  try {
    await chatStore.loadMessages(conversationId.value)
    hasMore.value = messages.value.length >= 20
    chatStore.markConversationRead(conversationId.value)
    scrollToBottom()
  } catch (e) {
    console.error('loadMessages failed', e)
  }
})

onUnmounted(() => {
  chatStore.saveDraft(conversationId.value, inputText.value)
})
</script>

<style scoped>
.chat-page {
  display: flex;
  flex-direction: column;
  height: 100vh;
  height: 100dvh;
  background: #f0f0f0;
  overflow: hidden;
}

.chat-page :deep(.van-nav-bar) {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  flex-shrink: 0;
}

.chat-page :deep(.van-nav-bar__title) {
  color: #fff;
  font-weight: 600;
}

.chat-page :deep(.van-nav-bar .van-icon) {
  color: #fff !important;
}

/* Message list */
.message-list {
  flex: 1;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  padding: 8px 12px;
  padding-bottom: 12px;
}

.load-more {
  text-align: center;
  padding: 8px 0;
  cursor: pointer;
}

.load-more-text {
  font-size: 13px;
  color: #999;
}

.empty-chat {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
}

/* Message wrapper */
.message-wrapper {
  display: flex;
  align-items: flex-start;
  margin-bottom: 12px;
}

.message-self {
  justify-content: flex-end;
}

.message-other {
  justify-content: flex-start;
}

.msg-avatar {
  flex-shrink: 0;
  overflow: hidden;
}

.msg-bubble {
  max-width: 65%;
  padding: 10px 12px;
  border-radius: 12px;
  word-break: break-all;
  position: relative;
  -webkit-user-select: none;
  user-select: none;
}

.message-other .msg-bubble {
  background: #fff;
  margin-left: 8px;
  border-top-left-radius: 4px;
  color: #333;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.message-self .msg-bubble {
  background: #95ec69;
  margin-right: 8px;
  border-top-right-radius: 4px;
  color: #333;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.msg-text {
  font-size: 15px;
  line-height: 1.5;
  white-space: pre-wrap;
}

.msg-system {
  font-size: 13px;
  color: #999;
  font-style: italic;
}

/* Image message */
.msg-image {
  display: block;
  max-width: 200px;
  max-height: 200px;
  border-radius: 6px;
  overflow: hidden;
}

/* Album message */
.msg-album {
  min-width: 160px;
  max-width: 200px;
  border-radius: 6px;
  overflow: hidden;
  background: #fff;
  border: 1px solid #eee;
}

.album-cover {
  display: block;
  width: 100%;
  height: 120px;
}

.album-info {
  display: flex;
  align-items: center;
  padding: 8px 10px;
  gap: 4px;
}

.album-title {
  font-size: 13px;
  color: #666;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* Bottom toolbar */
.chat-toolbar {
  display: flex;
  align-items: flex-end;
  gap: 8px;
  padding: 8px 10px;
  padding-bottom: calc(8px + env(safe-area-inset-bottom, 0px));
  background: #fff;
  border-top: 1px solid #e8e8e8;
  flex-shrink: 0;
}

.plus-trigger {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 1.5px solid #ccc;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  margin-bottom: 2px;
  cursor: pointer;
}

.plus-trigger:active {
  background: #f5f5f5;
}

.chat-input {
  flex: 1;
  min-width: 0;
  padding: 0;
  background: transparent;
}

.chat-input :deep(.van-field__control) {
  background: #f5f5f5;
  border-radius: 12px;
  padding: 6px 14px;
  font-size: 15px;
  min-height: 24px;
  max-height: 100px;
  line-height: 1.4;
}

.chat-input :deep(.van-field__body) {
  padding: 0;
}

.send-btn {
  flex-shrink: 0;
  height: 32px;
  padding: 0 12px;
  border-radius: 16px;
  font-size: 14px;
}

.plus-menu {
  display: flex;
  justify-content: flex-start;
  gap: 20px;
  padding: 12px 16px;
  background: #f7f8fa;
  border-top: 1px solid #e8e8e8;
  flex-shrink: 0;
}

.plus-menu-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  cursor: pointer;
  min-width: 52px;
}

.plus-menu-item:active {
  opacity: 0.7;
}

.plus-menu-item span {
  font-size: 11px;
  color: #666;
}

/* Album action sheet */
.album-sheet {
  max-height: 60vh;
}

.album-list {
  padding: 0 16px 16px;
  max-height: 50vh;
  overflow-y: auto;
}

.album-loading {
  display: flex;
  justify-content: center;
  padding: 20px 0;
}

.album-item {
  display: flex;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid #f0f0f0;
  cursor: pointer;
}

.album-item:active {
  background: #f9f9f9;
}

.album-item:last-child {
  border-bottom: none;
}

.album-item-info {
  margin-left: 12px;
  flex: 1;
  min-width: 0;
}

.album-item-title {
  font-size: 15px;
  color: #333;
  font-weight: 500;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.album-item-desc {
  font-size: 12px;
  color: #999;
  margin-top: 4px;
}

/* Quick reply items */
.quick-reply-item {
  padding: 12px 0;
  border-bottom: 1px solid #f0f0f0;
  cursor: pointer;
}

.quick-reply-item:active {
  background: #f9f9f9;
}

.quick-reply-item:last-child {
  border-bottom: none;
}

.quick-reply-title {
  font-size: 15px;
  color: #333;
  font-weight: 500;
}

.quick-reply-content {
  font-size: 13px;
  color: #999;
  margin-top: 4px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
