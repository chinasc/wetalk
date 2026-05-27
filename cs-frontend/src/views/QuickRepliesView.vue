<template>
  <div class="qr-page">
    <van-nav-bar title="常用语管理" left-arrow fixed placeholder @click-left="router.back()" />

    <div class="qr-toolbar">
      <van-button type="primary" size="small" round icon="plus" @click="showDialog()">添加</van-button>
    </div>

    <van-loading v-if="loading" class="page-loading" />

    <van-empty v-else-if="list.length === 0" description="暂无常用语" image="search" />

    <van-cell-group v-else inset class="qr-list">
      <van-swipe-cell v-for="item in list" :key="item.id">
        <van-cell class="qr-cell">
          <template #title>
            <div class="qr-title">{{ item.title }}</div>
            <div class="qr-content">{{ item.content }}</div>
          </template>
          <template #value>
            <van-button size="mini" plain type="primary" @click="showDialog(item)">编辑</van-button>
          </template>
        </van-cell>
        <template #right>
          <van-button square type="danger" text="删除" class="delete-btn" @click="onDelete(item)" />
        </template>
      </van-swipe-cell>
    </van-cell-group>

    <!-- 新增/编辑弹窗 -->
    <van-dialog
      v-model:show="dialogVisible"
      :title="editing ? '编辑常用语' : '添加常用语'"
      show-cancel-button
      :before-close="onBeforeClose"
    >
      <div class="dialog-form">
        <van-field v-model="form.title" label="标题" placeholder="如：问候语" maxlength="50" />
        <van-field
          v-model="form.content"
          label="内容"
          type="textarea"
          rows="3"
          placeholder="请输入常用语内容"
          maxlength="500"
          show-word-limit
        />
        <van-field v-model.number="form.sort_order" label="排序" type="digit" placeholder="越小越靠前" />
      </div>
    </van-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { showToast, showConfirmDialog } from 'vant'
import {
  getQuickReplies,
  createQuickReply,
  updateQuickReply,
  deleteQuickReply,
} from '../api'
import type { QuickReply } from '../types'

const router = useRouter()

const loading = ref(false)
const list = ref<QuickReply[]>([])
const dialogVisible = ref(false)
const editing = ref<QuickReply | null>(null)

const form = reactive({
  title: '',
  content: '',
  sort_order: 0,
})

async function fetchList() {
  loading.value = true
  try {
    list.value = await getQuickReplies()
  } catch (err: any) {
    showToast(err.message || '获取失败')
  } finally {
    loading.value = false
  }
}

function showDialog(item?: QuickReply) {
  editing.value = item || null
  if (item) {
    form.title = item.title
    form.content = item.content
    form.sort_order = item.sort_order
  } else {
    form.title = ''
    form.content = ''
    form.sort_order = 0
  }
  dialogVisible.value = true
}

async function onBeforeClose(action: string) {
  if (action !== 'confirm') return true

  if (!form.title.trim() || !form.content.trim()) {
    showToast('标题和内容不能为空')
    return false
  }

  try {
    const data = { title: form.title, content: form.content, sort_order: form.sort_order }
    if (editing.value) {
      await updateQuickReply(editing.value.id, data)
    } else {
      await createQuickReply(data)
    }
    showToast(editing.value ? '更新成功' : '添加成功')
    fetchList()
    return true
  } catch (err: any) {
    showToast(err.message || '操作失败')
    return false
  }
}

async function onDelete(item: QuickReply) {
  try {
    await showConfirmDialog({ title: '确认删除', message: `删除"${item.title}"？` })
    await deleteQuickReply(item.id)
    showToast('已删除')
    fetchList()
  } catch {
    // cancelled or error
  }
}

onMounted(() => {
  fetchList()
})
</script>

<style scoped>
.qr-page {
  min-height: 100vh;
  min-height: 100dvh;
  background: #f5f5f5;
}

.qr-page :deep(.van-nav-bar) {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.qr-page :deep(.van-nav-bar__title) {
  color: #fff;
  font-weight: 600;
}

.qr-page :deep(.van-nav-bar .van-icon) {
  color: #fff !important;
}

.qr-toolbar {
  display: flex;
  justify-content: flex-end;
  padding: 12px 16px 0;
}

.page-loading {
  display: flex;
  justify-content: center;
  padding: 60px 0;
}

.qr-list {
  margin-top: 8px;
}

.qr-cell {
  padding: 12px 16px;
}

.qr-title {
  font-size: 15px;
  font-weight: 500;
  color: #333;
  margin-bottom: 4px;
}

.qr-content {
  font-size: 13px;
  color: #999;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.delete-btn {
  height: 100%;
}

.dialog-form {
  padding: 16px;
}

.dialog-form :deep(.van-field) {
  margin-bottom: 8px;
}
</style>
