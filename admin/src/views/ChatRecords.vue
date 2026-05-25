<template>
  <div class="chat-records">
    <div class="toolbar">
      <div class="filters">
        <el-select v-model="filterCsId" placeholder="筛选客服" clearable style="width: 180px" @change="fetchConversations">
          <el-option v-for="cs in csList" :key="cs.id" :label="cs.nickname || cs.username" :value="cs.user_id" />
        </el-select>
        <el-input
          v-model="filterUserId"
          placeholder="用户ID"
          clearable
          type="number"
          style="width: 150px; margin-left: 12px"
          @keyup.enter="fetchConversations"
          @clear="fetchConversations"
        />
        <el-button type="primary" style="margin-left: 12px" @click="fetchConversations" icon="Search">搜索</el-button>
      </div>
      <el-button @click="fetchConversations" icon="Refresh">刷新</el-button>
    </div>

    <div class="chat-layout">
      <!-- Conversation List -->
      <div class="conversation-list">
        <div
          v-for="conv in conversations"
          :key="conv.id"
          class="conv-item"
          :class="{ active: selectedConv?.id === conv.id }"
          @click="selectConversation(conv)"
        >
          <div class="conv-info">
            <div class="conv-top">
              <span class="conv-cs">客服: {{ conv.cs_name }}</span>
              <span class="conv-user">用户: {{ conv.user_name }}</span>
            </div>
            <div class="conv-bottom">
              <span class="conv-msg">{{ conv.last_message || '暂无消息' }}</span>
              <span class="conv-time">{{ conv.last_time }}</span>
            </div>
          </div>
        </div>
        <el-empty v-if="conversations.length === 0" description="暂无会话" />
      </div>

      <!-- Message Area -->
      <div class="message-area">
        <div v-if="!selectedConv" class="no-select">
          <el-icon :size="48" color="#c0c4cc"><ChatDotRound /></el-icon>
          <p>请选择一个会话查看消息记录</p>
        </div>
        <template v-else>
          <div class="message-header">
            <span>客服 {{ selectedConv.cs_name || '未知' }} 与 用户 {{ selectedConv.user_name || '未知' }} 的对话</span>
          </div>
          <div class="message-list" v-loading="msgLoading">
            <div
              v-for="msg in messages"
              :key="msg.id"
              class="message-item"
              :class="{ 'is-cs': msg.sender_type === 'customer_service', 'is-user': msg.sender_type === 'user' }"
            >
              <div class="msg-meta">
                <span class="msg-sender">{{ msg.sender_type === 'customer_service' ? '客服' : '用户' }}{{ msg.sender_nickname ? ' (' + msg.sender_nickname + ')' : '' }}</span>
                <span class="msg-time">{{ msg.created_at }}</span>
              </div>
              <div class="msg-content" v-if="msg.message_type === 'text' || msg.type === 'text'">
                {{ msg.content }}
              </div>
              <div class="msg-content" v-else-if="msg.message_type === 'image' || msg.type === 'image'">
                <el-image :src="msg.content" style="max-width: 200px; max-height: 200px" fit="contain" :preview-src-list="[msg.content]" />
              </div>
              <div class="msg-content" v-else>
                <a :href="msg.content" target="_blank">{{ msg.content }}</a>
              </div>
            </div>
            <el-empty v-if="!msgLoading && messages.length === 0" description="暂无消息" />
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getCsList, getConversations, getChatMessages } from '@/api'
import type { CsItem, Conversation, ChatMessage } from '@/types'

const csList = ref<CsItem[]>([])
const filterCsId = ref<number | undefined>(undefined)
const filterUserId = ref('')
const conversations = ref<Conversation[]>([])
const selectedConv = ref<Conversation | null>(null)
const messages = ref<ChatMessage[]>([])
const msgLoading = ref(false)

async function fetchCsList() {
  try {
    const res = await getCsList()
    const data = res.data
    csList.value = Array.isArray(data) ? data : data.list || []
  } catch {
    // silent
  }
}

async function fetchConversations() {
  try {
    const params: { customer_service_id?: number; user_id?: number } = {}
    if (filterCsId.value) params.customer_service_id = filterCsId.value
    if (filterUserId.value) params.user_id = Number(filterUserId.value)
    const res = await getConversations(params)
    const data = res.data
    // Paginated response: data = { data: [...items], total, current_page, ... }
    conversations.value = Array.isArray(data) ? data : data.data || data.list || []
  } catch (err: any) {
    ElMessage.error(err.message || '获取会话列表失败')
  }
}

async function selectConversation(conv: Conversation) {
  selectedConv.value = conv
  msgLoading.value = true
  try {
    const res = await getChatMessages(conv.id)
    const data = res.data
    messages.value = Array.isArray(data) ? data : data.data || data.list || []
  } catch (err: any) {
    ElMessage.error(err.message || '获取消息失败')
  } finally {
    msgLoading.value = false
  }
}

onMounted(() => {
  fetchCsList()
  fetchConversations()
})
</script>

<style scoped>
.chat-records {
  background: #fff;
  border-radius: 4px;
  padding: 20px;
}

.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.filters {
  display: flex;
  align-items: center;
}

.chat-layout {
  display: flex;
  height: calc(100vh - 220px);
  min-height: 400px;
  border: 1px solid #ebeef5;
  border-radius: 4px;
  overflow: hidden;
}

.conversation-list {
  width: 360px;
  min-width: 360px;
  border-right: 1px solid #ebeef5;
  overflow-y: auto;
  background: #fafafa;
}

.conv-item {
  padding: 12px 16px;
  cursor: pointer;
  border-bottom: 1px solid #ebeef5;
  transition: background 0.2s;
}

.conv-item:hover {
  background: #ecf5ff;
}

.conv-item.active {
  background: #d9ecff;
}

.conv-top {
  display: flex;
  gap: 16px;
  margin-bottom: 6px;
}

.conv-cs, .conv-user {
  font-size: 13px;
  color: #303133;
  font-weight: 500;
}

.conv-bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.conv-msg {
  font-size: 12px;
  color: #909399;
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-right: 8px;
}

.conv-time {
  font-size: 11px;
  color: #c0c4cc;
  white-space: nowrap;
}

.message-area {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.no-select {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  color: #c0c4cc;
  gap: 12px;
}

.message-header {
  padding: 12px 16px;
  border-bottom: 1px solid #ebeef5;
  font-size: 14px;
  font-weight: 500;
  color: #303133;
  background: #fafafa;
}

.message-list {
  flex: 1;
  padding: 16px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.message-item {
  max-width: 70%;
  padding: 8px 12px;
  border-radius: 8px;
}

.message-item.is-cs {
  align-self: flex-start;
  background: #e1f3d8;
}

.message-item.is-user {
  align-self: flex-end;
  background: #d9ecff;
}

.msg-meta {
  display: flex;
  justify-content: space-between;
  margin-bottom: 4px;
  font-size: 11px;
  color: #909399;
}

.msg-content {
  font-size: 14px;
  color: #303133;
  line-height: 1.5;
  word-break: break-all;
}
</style>
