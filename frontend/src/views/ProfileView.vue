<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { showToast, showLoadingToast, closeToast } from 'vant'
import { useAuthStore } from '../stores/auth'
import { isSoundEnabled, setSoundEnabled } from '../services/notification'

const router = useRouter()
const authStore = useAuthStore()

const nickname = ref('')
const phone = ref('')
const avatarUrl = ref('')
const soundEnabled = ref(isSoundEnabled())

function onSoundToggle(val: boolean) {
  soundEnabled.value = val
  setSoundEnabled(val)
}

onMounted(() => {
  if (authStore.user) {
    nickname.value = authStore.user.nickname || ''
    phone.value = authStore.user.phone || ''
    avatarUrl.value = authStore.user.avatar || ''
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
      if (phone.value) formData.append('phone', phone.value)

      const user = await authStore.updateProfile(formData)
      avatarUrl.value = user.avatar
      closeToast()
      showToast('头像更新成功')
    } catch {
      closeToast()
      showToast('上传失败')
    }
  }
  input.click()
}

async function handleSave() {
  showLoadingToast({ message: '保存中...', forbidClick: true, duration: 0 })
  try {
    const formData = new FormData()
    formData.append('nickname', nickname.value)
    formData.append('phone', phone.value)

    await authStore.updateProfile(formData)
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
    <van-nav-bar
      title="个人资料"
      left-arrow
      @click-left="goBack"
      :border="false"
    />
    <div class="page-content" style="padding: 16px;">
      <!-- Avatar section -->
      <div class="avatar-section" @click="handleAvatarChange">
        <van-image
          round
          width="80"
          height="80"
          fit="cover"
          :src="avatarUrl"
          class="profile-big-avatar"
        >
          <template #error>
            <van-icon name="manager-o" size="44" color="#969799" />
          </template>
        </van-image>
        <div class="avatar-edit-hint">
          <van-icon name="photograph" size="14" />
          <span>更换头像</span>
        </div>
      </div>

      <!-- Form -->
      <van-cell-group inset style="margin-top: 20px; margin-left: 0; margin-right: 0;">
        <van-field
          v-model="nickname"
          label="昵称"
          placeholder="请输入昵称"
          clearable
          maxlength="20"
        />
        <van-field
          v-model="phone"
          label="手机号"
          placeholder="请输入手机号"
          type="tel"
          clearable
          maxlength="11"
        />
      </van-cell-group>

      <van-cell-group inset style="margin-top: 16px; margin-left: 0; margin-right: 0;">
        <van-cell title="消息提示音" center>
          <template #right-icon>
            <van-switch :model-value="soundEnabled" size="20" @update:model-value="onSoundToggle" />
          </template>
        </van-cell>
      </van-cell-group>

      <div style="margin-top: 24px; padding: 0 4px;">
        <van-button type="primary" block round size="large" @click="handleSave">
          保存
        </van-button>
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

.profile-big-avatar {
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f2f3f5;
  overflow: hidden;
}

.avatar-edit-hint {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-top: 8px;
  font-size: 13px;
  color: #1989fa;
}
</style>
