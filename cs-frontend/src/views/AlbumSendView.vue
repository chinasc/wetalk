<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { showToast, showLoadingToast, closeToast } from 'vant'
import { useChatStore } from '../stores/chat'
import { sendMessage } from '../api'

const router = useRouter()
const route = useRoute()
const chatStore = useChatStore()

const albumId = computed(() => Number(route.query.album_id || 0))
const albumTitle = computed(() => (route.query.album_title as string) || '相册')
const albumCover = computed(() => (route.query.album_cover as string) || '')

const sending = ref(false)
const sent = ref(false)

async function sendToConversation(conv: any) {
  if (sending.value) return
  sending.value = true
  showLoadingToast({ message: '发送中...', forbidClick: true, duration: 0 })

  try {
    const content = JSON.stringify({
      id: albumId.value,
      title: albumTitle.value,
      cover: albumCover.value,
    })
    await sendMessage(conv.id, 'album', content)
    closeToast()
    showToast('发送成功')
    sent.value = true
    router.replace(`/chat/${conv.id}`)
  } catch {
    closeToast()
    showToast('发送失败')
  } finally {
    sending.value = false
  }
}

function goBack() {
  router.back()
}

onMounted(() => {
  if (chatStore.conversations.length === 0) {
    chatStore.loadConversations()
  }
})
</script>

<template>
  <div class="page">
    <van-nav-bar title="发送相册" left-arrow @click-left="goBack" :border="false" />

    <div class="page-content" style="padding: 16px;">
      <!-- Album preview -->
      <div class="album-preview">
        <van-image
          :src="albumCover"
          width="64"
          height="64"
          fit="cover"
          radius="8"
        >
          <template #error>
            <van-icon name="photo-o" size="32" color="#969799" />
          </template>
        </van-image>
        <div class="album-preview-info">
          <div class="album-preview-title">{{ albumTitle }}</div>
          <div class="album-preview-hint">选择会话发送此相册</div>
        </div>
      </div>

      <!-- Conversation list to select -->
      <div class="section-title">选择发送给</div>

      <van-loading v-if="chatStore.loading" class="page-loading" size="24px" vertical>加载中...</van-loading>

      <van-empty v-else-if="chatStore.conversations.length === 0" description="暂无会话" />

      <van-cell-group v-else inset style="margin: 0;">
        <van-cell
          v-for="conv in chatStore.conversations"
          :key="conv.id"
          clickable
          @click="sendToConversation(conv)"
        >
          <template #title>
            <div style="display:flex;align-items:center;">
              <van-image
                round
                width="40"
                height="40"
                fit="cover"
                :src="conv.user_avatar || ''"
                style="flex-shrink:0;"
              >
                <template #error>
                  <van-icon name="manager-o" size="22" color="#969799" />
                </template>
              </van-image>
              <span style="margin-left:12px;font-size:15px;color:#323233;">
                {{ conv.user_nickname }}
              </span>
            </div>
          </template>
          <template #value>
            <van-button size="small" type="primary" round :loading="sending">发送</van-button>
          </template>
        </van-cell>
      </van-cell-group>
    </div>
  </div>
</template>

<style scoped>
.album-preview {
  display: flex;
  align-items: center;
  padding: 16px;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
}

.album-preview-info {
  margin-left: 14px;
}

.album-preview-title {
  font-size: 16px;
  font-weight: 500;
  color: #323233;
}

.album-preview-hint {
  font-size: 13px;
  color: #969799;
  margin-top: 4px;
}

.section-title {
  font-size: 14px;
  color: #969799;
  margin: 20px 0 12px 4px;
}

.page-loading {
  display: flex;
  justify-content: center;
  padding: 40px 0;
}
</style>
