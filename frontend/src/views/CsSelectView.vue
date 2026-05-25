<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { showToast, showLoadingToast, closeToast } from 'vant'
import { csApi } from '../api'
import { useChatStore } from '../stores/chat'
import type { CustomerService } from '../types'

const router = useRouter()
const chatStore = useChatStore()

const csList = ref<CustomerService[]>([])
const loading = ref(false)

onMounted(async () => {
  loading.value = true
  try {
    const res = await csApi.getList()
    csList.value = res.data
  } catch {
    showToast('获取客服列表失败')
  } finally {
    loading.value = false
  }
})

async function selectCS(cs: CustomerService) {
  if (!cs.online) {
    showToast('该客服当前不在线')
    return
  }

  showLoadingToast({ message: '正在连接...', forbidClick: true, duration: 0 })
  try {
    const conv = await chatStore.createConversation(cs.id)
    closeToast()
    router.replace(`/chat/${conv.id}`)
  } catch {
    closeToast()
    showToast('连接失败，请重试')
  }
}

function goBack() {
  router.back()
}
</script>

<template>
  <div class="page">
    <van-nav-bar
      title="选择客服"
      left-arrow
      @click-left="goBack"
      :border="false"
    />
    <div class="page-content" style="padding: 12px 16px;">
      <van-loading v-if="loading" class="page-loading" size="36px" vertical>
        加载中...
      </van-loading>
      <van-empty v-else-if="csList.length === 0" description="暂无在线客服" />
      <div v-else class="cs-list">
        <div
          v-for="cs in csList"
          :key="cs.id"
          class="cs-item"
          @click="selectCS(cs)"
        >
          <div class="cs-avatar-wrap">
            <van-image
              round
              width="52"
              height="52"
              fit="cover"
              :src="cs.avatar"
            >
              <template #error>
                <van-icon name="manager-o" size="28" color="#969799" />
              </template>
            </van-image>
            <span :class="['cs-status-dot', cs.online ? 'online' : 'offline']"></span>
          </div>
          <div class="cs-info">
            <div class="cs-name">{{ cs.nickname }}</div>
            <div class="cs-status-text">
              {{ cs.online ? '在线' : '离线' }}
            </div>
          </div>
          <van-button
            size="small"
            :type="cs.online ? 'primary' : 'default'"
            round
            :disabled="!cs.online"
          >
            {{ cs.online ? '咨询' : '离线' }}
          </van-button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.page-loading {
  margin-top: 60px;
}

.cs-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.cs-item {
  display: flex;
  align-items: center;
  padding: 16px;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
  cursor: pointer;
  min-height: 80px;
}

.cs-avatar-wrap {
  position: relative;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.cs-status-dot {
  position: absolute;
  bottom: 2px;
  right: 2px;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: 2px solid #fff;
}

.cs-status-dot.online {
  background: #07c160;
}

.cs-status-dot.offline {
  background: #c8c9cc;
}

.cs-info {
  flex: 1;
  margin-left: 14px;
}

.cs-name {
  font-size: 16px;
  font-weight: 600;
  color: #323233;
}

.cs-status-text {
  font-size: 13px;
  color: #969799;
  margin-top: 4px;
}
</style>
