<template>
  <div class="manage-page">
    <van-nav-bar title="用户管理" left-arrow fixed placeholder @click-left="router.back()" />

    <div class="toolbar">
      <van-search v-model="keyword" placeholder="搜索用户名/昵称" shape="round" class="search-bar" @search="fetchUsers" @clear="fetchUsers" />
      <van-button type="primary" size="small" round icon="plus" @click="showCreateDialog">创建</van-button>
    </div>

    <van-loading v-if="loading" class="page-loading" />

    <van-empty v-else-if="users.length === 0" description="暂无用户" image="search" />

    <van-cell-group v-else inset class="list-group">
      <van-cell v-for="user in users" :key="user.id" class="user-cell">
        <template #title>
          <div class="user-row">
            <div class="user-avatar-wrap">
              <van-image :src="user.avatar || defaultAvatar" round width="44" height="44" fit="cover" />
              <span class="online-dot" :class="user.online ? 'online' : 'offline'" />
            </div>
            <div class="user-info">
              <div class="user-name">
                  {{ user.nickname || user.username }}
                  <span class="online-tag" :class="user.online ? 'online' : 'offline'">{{ user.online ? '在线' : '离线' }}</span>
                </div>
              <div class="user-meta">
                <span>{{ user.username }}</span>
                <span v-if="user.verify_code" class="verify-code" @click="copyCode(user.verify_code)">验证码: {{ user.verify_code }}</span>
              </div>
            </div>
          </div>
        </template>
        <template #value>
          <div class="cell-actions">
            <van-button size="mini" plain type="primary" @click="showEditDialog(user)">编辑</van-button>
            <van-button size="mini" plain type="danger" @click="onDeleteUser(user)">删除</van-button>
          </div>
        </template>
      </van-cell>
    </van-cell-group>

    <div v-if="hasMore" class="load-more">
      <van-button size="small" plain @click="loadMore">加载更多</van-button>
    </div>

    <!-- 创建用户弹窗 -->
    <van-dialog
      v-model:show="createVisible"
      title="创建用户"
      show-cancel-button
      :before-close="onCreateClose"
    >
      <div class="dialog-form">
        <van-field v-model="createForm.username" label="用户名" placeholder="登录用户名" maxlength="30" />
        <van-field v-model="createForm.nickname" label="昵称" placeholder="显示昵称（可选）" maxlength="30" />
        <van-field v-model="createForm.password" label="密码" type="password" placeholder="留空则使用验证码登录" />
      </div>
    </van-dialog>

    <!-- 编辑用户弹窗 -->
    <van-dialog
      v-model:show="editVisible"
      title="编辑用户"
      show-cancel-button
      :before-close="onEditClose"
    >
      <div class="dialog-form">
        <van-field :model-value="editForm.username" label="用户名" readonly />
        <van-field v-model="editForm.nickname" label="昵称" placeholder="请输入昵称" maxlength="30" />
        <van-field v-model="editForm.password" label="新密码" type="password" placeholder="留空则不修改" />
      </div>
    </van-dialog>

    <!-- 创建成功提示 -->
    <van-dialog
      v-model:show="resultVisible"
      title="创建成功"
      :show-confirm-button="true"
    >
      <div class="result-info">
        <p>用户名：<strong>{{ resultData.username }}</strong></p>
        <p>验证码：<strong class="highlight" @click="copyCode(resultData.verify_code)">{{ resultData.verify_code }}</strong></p>
        <p class="result-tip">请将验证码发给用户，用于首次登录</p>
      </div>
    </van-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { showToast, showConfirmDialog } from 'vant'
import { getCsUsers, createCsUser, resetUserPassword, updateCsUser, deleteCsUser } from '../api'

const router = useRouter()
const defaultAvatar = 'https://fastly.jsdelivr.net/npm/@vant/assets/cat.jpeg'

const loading = ref(false)
const users = ref<any[]>([])
const keyword = ref('')
const page = ref(1)
const hasMore = ref(false)

// 创建用户
const createVisible = ref(false)
const createForm = reactive({ username: '', nickname: '', password: '' })

// 编辑用户
const editVisible = ref(false)
const editForm = reactive({ id: 0, username: '', nickname: '', password: '' })

// 创建结果
const resultVisible = ref(false)
const resultData = reactive({ username: '', verify_code: '' })

async function fetchUsers() {
  loading.value = true
  page.value = 1
  try {
    const res = await getCsUsers(keyword.value || undefined)
    const data = res.data || res
    users.value = data.data || data
    hasMore.value = !!data.next_page_url
  } catch (err: any) {
    showToast(err.message || '获取失败')
  } finally {
    loading.value = false
  }
}

async function loadMore() {
  page.value++
  try {
    const res = await getCsUsers(keyword.value || undefined)
    const data = res.data || res
    const list = data.data || data
    users.value.push(...list)
    hasMore.value = !!data.next_page_url
  } catch (err: any) {
    showToast(err.message || '加载失败')
  }
}

function showCreateDialog() {
  createForm.username = ''
  createForm.nickname = ''
  createForm.password = ''
  createVisible.value = true
}

async function onCreateClose(action: string) {
  if (action !== 'confirm') return true
  if (!createForm.username.trim()) {
    showToast('请输入用户名')
    return false
  }
  try {
    const res = await createCsUser({
      username: createForm.username.trim(),
      nickname: createForm.nickname.trim() || undefined,
      password: createForm.password || undefined,
    })
    resultData.username = res.username || createForm.username
    resultData.verify_code = res.verify_code || ''
    resultVisible.value = true
    fetchUsers()
    return true
  } catch (err: any) {
    showToast(err.message || '创建失败')
    return false
  }
}

function showEditDialog(user: any) {
  editForm.id = user.id
  editForm.username = user.username || ''
  editForm.nickname = user.nickname || ''
  editForm.password = ''
  editVisible.value = true
}

async function onEditClose(action: string) {
  if (action !== 'confirm') return true
  if (!editForm.nickname.trim()) {
    showToast('请输入昵称')
    return false
  }
  try {
    const data: Record<string, string> = { nickname: editForm.nickname.trim() }
    if (editForm.password) data.password = editForm.password
    await updateCsUser(editForm.id, data)
    showToast('更新成功')
    fetchUsers()
    return true
  } catch (err: any) {
    showToast(err.message || '更新失败')
    return false
  }
}

async function onResetPassword(user: any) {
  try {
    await showConfirmDialog({ title: '重置密码', message: `重置用户"${user.nickname || user.username}"的密码？将生成新的验证码。` })
    const res = await resetUserPassword(user.id)
    showToast(`新验证码: ${res.verify_code}`)
    fetchUsers()
  } catch { /* cancelled */ }
}

async function onDeleteUser(user: any) {
  try {
    await showConfirmDialog({ title: '确认删除', message: `删除用户"${user.nickname || user.username}"？此操作不可恢复。` })
    await deleteCsUser(user.id)
    showToast('已删除')
    fetchUsers()
  } catch { /* cancelled */ }
}

function copyCode(code: string) {
  navigator.clipboard.writeText(code).then(() => {
    showToast('已复制')
  }).catch(() => {
    showToast('复制失败，请手动复制')
  })
}

onMounted(() => {
  fetchUsers()
})
</script>

<style scoped>
.manage-page {
  min-height: 100vh;
  min-height: 100dvh;
  background: #f5f5f5;
}

.manage-page :deep(.van-nav-bar) {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.manage-page :deep(.van-nav-bar__title) {
  color: #fff;
  font-weight: 600;
}

.manage-page :deep(.van-nav-bar .van-icon) {
  color: #fff !important;
}

.toolbar {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px 0;
}

.search-bar {
  flex: 1;
  padding: 0;
}

.search-bar :deep(.van-search__content) {
  background: #fff;
}

.list-group {
  margin-top: 8px;
}

.user-cell {
  padding: 10px 16px;
}

.user-row {
  display: flex;
  align-items: center;
  width: 100%;
}

.user-avatar-wrap {
  position: relative;
  flex-shrink: 0;
  overflow: visible;
}

.online-dot {
  position: absolute;
  bottom: 1px;
  right: 1px;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  border: 2px solid #fff;
}

.online-dot.online {
  background: #07c160;
}

.online-dot.offline {
  background: #c8c9cc;
}

.user-info {
  flex: 1;
  margin-left: 10px;
  min-width: 0;
}

.user-name {
  font-size: 15px;
  font-weight: 500;
  color: #333;
  display: flex;
  align-items: center;
  gap: 6px;
}

.online-tag {
  font-size: 11px;
  font-weight: 400;
  padding: 1px 6px;
  border-radius: 8px;
}

.online-tag.online {
  color: #07c160;
  background: rgba(7, 193, 96, 0.1);
}

.online-tag.offline {
  color: #999;
  background: #f5f5f5;
}

.user-meta {
  font-size: 12px;
  color: #999;
  margin-top: 4px;
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.verify-code {
  color: #667eea;
  cursor: pointer;
}

.cell-actions {
  display: flex;
  gap: 4px;
  flex-shrink: 0;
}

.load-more {
  display: flex;
  justify-content: center;
  padding: 16px;
}

.page-loading {
  display: flex;
  justify-content: center;
  padding: 60px 0;
}

.dialog-form {
  padding: 16px;
}

.dialog-form :deep(.van-field) {
  margin-bottom: 8px;
}

.result-info {
  padding: 16px 20px;
  font-size: 14px;
  color: #333;
  line-height: 2;
}

.result-info .highlight {
  color: #667eea;
  cursor: pointer;
  font-size: 16px;
  letter-spacing: 1px;
}

.result-tip {
  font-size: 12px;
  color: #999;
  margin-top: 8px;
}
</style>
