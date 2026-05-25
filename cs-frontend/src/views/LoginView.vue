<template>
  <div class="login-page">
    <div class="login-header">
      <h1 class="login-title">WeTalk</h1>
      <p class="login-subtitle">客服工作台</p>
    </div>

    <div class="login-form">
      <van-cell-group inset>
        <van-field
          v-model="form.username"
          placeholder="请输入用户名"
          :rules="[{ required: true, message: '请输入用户名' }]"
          clearable
          @keyup.enter="onLogin"
        >
          <template #left-icon>
            <van-icon name="manager-o" />
          </template>
        </van-field>

        <van-field
          v-model="form.password"
          type="password"
          placeholder="请输入密码"
          :rules="[{ required: true, message: '请输入密码' }]"
          clearable
          @keyup.enter="onLogin"
        >
          <template #left-icon>
            <van-icon name="lock" />
          </template>
        </van-field>
      </van-cell-group>

      <div class="login-actions">
        <van-button
          type="primary"
          block
          round
          :loading="authStore.loading"
          loading-text="登录中..."
          @click="onLogin"
        >
          登录
        </van-button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { showToast } from 'vant'
import { useAuthStore } from '../stores/auth'
import { useChatStore } from '../stores/chat'
import type { LoginForm } from '../types'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const chatStore = useChatStore()

const form = reactive<LoginForm>({
  username: '',
  password: '',
})

async function onLogin(): Promise<void> {
  if (!form.username.trim()) {
    showToast('请输入用户名')
    return
  }
  if (!form.password.trim()) {
    showToast('请输入密码')
    return
  }

  try {
    await authStore.login({
      username: form.username.trim(),
      password: form.password,
    })
    chatStore.initWsListeners()
    chatStore.loadConversations()
    showToast({ message: '登录成功', duration: 1000 })
    const redirect = (route.query.redirect as string) || '/'
    router.replace(redirect)
  } catch (err: unknown) {
    const message = err instanceof Error ? err.message : '登录失败'
    showToast(message)
  }
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  min-height: 100dvh;
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 0 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.login-header {
  text-align: center;
  margin-bottom: 48px;
}

.login-title {
  font-size: 32px;
  font-weight: 700;
  color: #fff;
  margin: 0 0 8px 0;
}

.login-subtitle {
  font-size: 16px;
  color: rgba(255, 255, 255, 0.8);
  margin: 0;
}

.login-form {
  width: 100%;
  max-width: 400px;
  margin: 0 auto;
}

.login-form :deep(.van-cell-group--inset) {
  margin: 0;
  border-radius: 12px;
  overflow: hidden;
}

.login-form :deep(.van-field) {
  padding: 14px 16px;
}

.login-form :deep(.van-field__left-icon) {
  margin-right: 8px;
  color: #999;
}

.login-actions {
  margin-top: 24px;
  padding: 0 0;
}

.login-actions :deep(.van-button--primary) {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  height: 48px;
  font-size: 16px;
  font-weight: 600;
}
</style>
