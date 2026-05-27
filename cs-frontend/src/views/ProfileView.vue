<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { showToast, showLoadingToast, closeToast } from 'vant'
import { useAuthStore } from '../stores/auth'
import { getUserInfo, http } from '../api'
import { isSoundEnabled, setSoundEnabled } from '../services/notification'

const router = useRouter()
const authStore = useAuthStore()

const nickname = ref('')
const avatarUrl = ref('')
const soundEnabled = ref(isSoundEnabled())

function onSoundToggle(val: boolean) {
  soundEnabled.value = val
  setSoundEnabled(val)
}

onMounted(async () => {
  try {
    const info = await getUserInfo()
    nickname.value = info.nickname || ''
    avatarUrl.value = info.avatar || ''
    authStore.user = info
  } catch {
    // use store data
    nickname.value = authStore.user?.nickname || ''
    avatarUrl.value = authStore.user?.avatar || ''
  }
})

async function handleAvatarChange() {
  const input = document.createElement('input')
  input.type = 'file'
  input.accept = 'image/*'
  input.onchange = async (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0]
    if (!file) return

    showLoadingToast({ message: '上传中...', forbidClick: true, duration: 0 })
    try {
      const formData = new FormData()
      formData.append('avatar', file)
      if (nickname.value) formData.append('nickname', nickname.value)
      const res = await http.post('/auth/profile', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      const d = (res.data as any)
      avatarUrl.value = d.avatar || ''
      showToast('头像更新成功')
    } catch {
      showToast('上传失败')
    } finally {
      closeToast()
    }
  }
  input.click()
}

async function handleSave() {
  showLoadingToast({ message: '保存中...', forbidClick: true, duration: 0 })
  try {
    const formData = new FormData()
    formData.append('nickname', nickname.value)
    await http.post('/auth/profile', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    closeToast()
    showToast('保存成功')
    router.back()
  } catch {
    closeToast()
    showToast('保存失败')
  }
}

function goBack() {
  router.back()
}
</script>

<template>
  <div class="page">
    <van-nav-bar title="个人信息" left-arrow @click-left="goBack" :border="false" />
    <div class="page-content" style="padding: 16px;">
      <div class="avatar-section" @click="handleAvatarChange">
        <van-image round width="80" height="80" fit="cover" :src="avatarUrl" class="profile-avatar">
          <template #error>
            <van-icon name="manager-o" size="44" color="#969799" />
          </template>
        </van-image>
        <div class="avatar-hint">
          <van-icon name="photograph" size="14" />
          <span>更换头像</span>
        </div>
      </div>

      <van-cell-group inset style="margin-top: 20px; margin-left: 0; margin-right: 0;">
        <van-field v-model="nickname" label="昵称" placeholder="请输入昵称" clearable maxlength="20" />
        <van-field :model-value="authStore.user?.username || ''" label="用户名" readonly />
        <van-field :model-value="authStore.user?.role === 'customer_service' ? '客服' : authStore.user?.role" label="角色" readonly />
      </van-cell-group>

      <van-cell-group inset style="margin-top: 16px; margin-left: 0; margin-right: 0;">
        <van-cell title="消息提示音" center>
          <template #right-icon>
            <van-switch :model-value="soundEnabled" size="20" @update:model-value="onSoundToggle" />
          </template>
        </van-cell>
      </van-cell-group>

      <div style="margin-top: 24px; padding: 0 4px;">
        <van-button type="primary" block round size="large" @click="handleSave">保存</van-button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.avatar-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 16px 0;
  cursor: pointer;
}

.profile-avatar {
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f2f3f5;
  overflow: hidden;
}

.avatar-hint {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-top: 8px;
  font-size: 13px;
  color: #1989fa;
}
</style>
