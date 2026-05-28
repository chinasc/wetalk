<template>
  <div class="conversations-page">
    <van-nav-bar fixed placeholder>
      <template #title>
        <span>客服工作台</span>
        <van-badge v-if="chatStore.totalUnread > 0" :content="chatStore.totalUnread" :max="99" class="title-badge" />
      </template>
      <template #left>
        <van-icon name="manager-o" size="20" @click="goProfile" />
      </template>
      <template #right>
        <van-icon name="setting-o" size="20" @click="showMenu = true" />
      </template>
    </van-nav-bar>

    <van-pull-refresh v-model="refreshing" @refresh="onRefresh">
      <van-loading v-if="chatStore.loading && conversations.length === 0" class="page-loading" />

      <van-empty
        v-else-if="conversations.length === 0"
        description="暂无会话"
        image="search"
      />

      <van-cell-group v-else inset class="conversation-list">
        <van-cell
          v-for="conv in conversations"
          :key="conv.id"
          class="conversation-cell"
          :class="{ 'has-unread': conv.unread_count > 0 }"
          clickable
          @click="openChat(conv)"
        >
          <template #title>
            <div class="conv-content">
              <van-badge :content="conv.unread_count > 0 ? conv.unread_count : undefined" :max="99">
                <van-image
                  round
                  width="48"
                  height="48"
                  :src="conv.user_avatar || defaultAvatar"
                  fit="cover"
                  class="conv-avatar"
                />
              </van-badge>
              <div class="conv-info">
                <div class="conv-top-row">
                  <span class="conv-nickname">{{ conv.user_nickname }}</span>
                  <span class="conv-time">{{ formatTime(conv.last_message_time) }}</span>
                </div>
                <div class="conv-last-msg" :class="{ 'unread-msg': conv.unread_count > 0 }">
                  <template v-if="chatStore.getDraft(conv.id)">
                    <span class="draft-tag">[草稿]</span>{{ chatStore.getDraft(conv.id) }}
                  </template>
                  <template v-else>
                    {{ formatLastMessage(conv.last_message_type, conv.last_message) }}
                  </template>
                </div>
              </div>
              <div class="conv-actions">
                <van-button size="mini" type="warning" plain @click.stop="handleClear(conv)">清除</van-button>
                <van-button size="mini" type="danger" plain @click.stop="handleDelete(conv)">删除</van-button>
              </div>
            </div>
          </template>
        </van-cell>
      </van-cell-group>
    </van-pull-refresh>

    <van-action-sheet v-model:show="showMenu" :actions="menuActions" @select="onMenuSelect" cancel-text="取消" />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { showConfirmDialog, showToast } from 'vant'
import type { ConvItem } from '../stores/chat'
import { useChatStore } from '../stores/chat'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const chatStore = useChatStore()
const authStore = useAuthStore()
const refreshing = ref(false)
const showMenu = ref(false)

const defaultAvatar = 'https://fastly.jsdelivr.net/npm/@vant/assets/cat.jpeg'

const conversations = computed(() => chatStore.conversations)

const menuActions = [
  { name: '个人信息', value: 'profile' },
  { name: '用户管理', value: 'user-manage' },
  { name: '相册管理', value: 'album-manage' },
  { name: '常用语管理', value: 'quick-replies' },
  { name: '退出登录', value: 'logout', color: '#ee0a24' },
]

function formatTime(timeStr: string): string {
  if (!timeStr) return ''
  const date = new Date(timeStr)
  const now = new Date()
  const diff = now.getTime() - date.getTime()
  const oneDay = 86400000

  if (diff < 60000) return '刚刚'
  if (diff < 3600000) return `${Math.floor(diff / 60000)}分钟前`
  if (diff < oneDay && now.getDate() === date.getDate()) {
    return date.toLocaleTimeString('zh-CN', { hour: '2-digit', minute: '2-digit' })
  }
  if (diff < oneDay * 2) return '昨天'
  if (diff < oneDay * 7) {
    const days = ['周日', '周一', '周二', '周三', '周四', '周五', '周六']
    return days[date.getDay()]
  }
  return `${date.getMonth() + 1}/${date.getDate()}`
}

function formatLastMessage(type: string, content: string): string {
  if (type === 'image') return '[图片]'
  if (type === 'album') {
    try {
      const album = JSON.parse(content)
      return `[相册] ${album.title || ''}`
    } catch {
      return '[相册]'
    }
  }
  if (!content) return ''
  return content.length > 30 ? content.slice(0, 30) + '...' : content
}

function openChat(conv: ConvItem): void {
  router.push(`/chat/${conv.id}`)
}

async function handleClear(conv: ConvItem): Promise<void> {
  try {
    await showConfirmDialog({ title: '清除聊天记录', message: `确定清除与 ${conv.user_nickname} 的聊天记录吗？此操作不可恢复。` })
  } catch {
    return
  }
  try {
    await chatStore.clearMessages(conv.id)
    showToast('记录已清除')
  } catch {
    showToast('操作失败')
  }
}

async function handleDelete(conv: ConvItem): Promise<void> {
  try {
    await showConfirmDialog({ title: '删除会话', message: `确定删除与 ${conv.user_nickname} 的会话吗？此操作不可恢复。` })
  } catch {
    return
  }
  try {
    await chatStore.deleteConversation(conv.id)
    showToast('会话已删除')
  } catch {
    showToast('操作失败')
  }
}

function goProfile(): void {
  router.push('/profile')
}

async function onMenuSelect(action: any): Promise<void> {
  showMenu.value = false
  if (action.value === 'profile') {
    router.push('/profile')
  } else if (action.value === 'user-manage') {
    router.push('/user-manage')
  } else if (action.value === 'album-manage') {
    router.push('/album-manage')
  } else if (action.value === 'quick-replies') {
    router.push('/quick-replies')
  } else if (action.value === 'logout') {
    chatStore.clearCurrentChat()
    chatStore.resetWsListeners()
    authStore.logout()
    router.replace('/login')
  }
}

async function onRefresh(): Promise<void> {
  try {
    await chatStore.loadConversations()
  } finally {
    refreshing.value = false
  }
}

onMounted(() => {
  chatStore.loadConversations()
})
</script>

<style scoped>
.conversations-page {
  min-height: 100vh;
  min-height: 100dvh;
  background: #f5f5f5;
}

.conversations-page :deep(.van-nav-bar) {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.conversations-page :deep(.van-nav-bar__title) {
  color: #fff;
  font-weight: 600;
}

.conversations-page :deep(.van-nav-bar .van-icon) {
  color: #fff !important;
}

.page-loading {
  display: flex;
  justify-content: center;
  padding: 60px 0;
}

.conversation-list {
  margin-top: 8px;
}

.conversation-cell {
  padding: 12px 16px;
}

.conv-content {
  display: flex;
  align-items: center;
  width: 100%;
}

.conv-avatar {
  flex-shrink: 0;
  overflow: hidden;
}

.conv-info {
  flex: 1;
  margin-left: 12px;
  min-width: 0;
  overflow: hidden;
}

.conv-top-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 4px;
}

.conv-nickname {
  font-size: 15px;
  font-weight: 500;
  color: #333;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.conv-time {
  font-size: 12px;
  color: #999;
  flex-shrink: 0;
  margin-left: 8px;
}

.conv-last-msg {
  font-size: 13px;
  color: #999;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.conv-last-msg.unread-msg {
  color: #333;
  font-weight: 500;
}

.conversation-cell.has-unread {
  background: #f0f5ff;
}

.conversation-cell.has-unread:active {
  background: #e6efff;
}

.title-badge {
  margin-left: 6px;
  vertical-align: middle;
}

.draft-tag {
  color: #ee0a24;
  font-weight: 500;
  margin-right: 2px;
}

.conv-actions {
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex-shrink: 0;
  margin-left: 8px;
}
</style>
