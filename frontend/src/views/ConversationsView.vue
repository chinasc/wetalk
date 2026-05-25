<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { showToast } from 'vant'
import { useChatStore } from '../stores/chat'

const router = useRouter()
const chatStore = useChatStore()

onMounted(() => {
  chatStore.fetchConversations().catch(() => {
    showToast('加载失败')
  })
})

function openChat(convId: number) {
  router.push(`/chat/${convId}`)
}

function goBack() {
  router.back()
}
</script>

<template>
  <div class="page">
    <van-nav-bar
      title="消息列表"
      left-arrow
      @click-left="goBack"
      :border="false"
    />
    <div class="page-content">
      <van-empty
        v-if="chatStore.sortedConversations.length === 0"
        description="暂无会话记录"
      />
      <van-cell-group v-else inset style="margin: 12px 0;">
        <van-cell
          v-for="conv in chatStore.sortedConversations"
          :key="conv.id"
          :title="conv.customer_service_name"
          :label="conv.last_message || '暂无消息'"
          is-link
          @click="openChat(conv.id)"
        >
          <template #icon>
            <van-image
              round
              width="48"
              height="48"
              fit="cover"
              :src="conv.customer_service_avatar"
              class="conv-avatar"
            >
              <template #error>
                <van-icon name="manager-o" size="26" />
              </template>
            </van-image>
          </template>
          <template #value>
            <div class="conv-meta">
              <span class="conv-time">
                {{ new Date(conv.updated_at).toLocaleDateString('zh-CN', { month: 'numeric', day: 'numeric' }) }}
              </span>
              <van-badge v-if="conv.unread_count > 0" :content="conv.unread_count" :max="99" />
            </div>
          </template>
        </van-cell>
      </van-cell-group>
    </div>
  </div>
</template>

<style scoped>
.conv-avatar {
  margin-right: 12px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f2f3f5;
  overflow: hidden;
}

.conv-meta {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 6px;
}

.conv-time {
  font-size: 11px;
  color: #969799;
}
</style>
