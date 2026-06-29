<script setup lang="ts">
import { ref, computed, watch, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { showToast, showLoadingToast, closeToast } from 'vant'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const username = ref('')
const verifyCode = ref('')
const loading = ref(false)
const needVerifyCode = ref(false)

let checkTimer: ReturnType<typeof setTimeout> | null = null

const canLogin = computed(() => {
  if (!username.value.trim()) return false
  if (needVerifyCode.value && !verifyCode.value.trim()) return false
  return true
})

async function checkUsername() {
  const name = username.value.trim()
  if (!name) {
    needVerifyCode.value = false
    return
  }
  try {
    needVerifyCode.value = await authStore.checkFirstLogin(name)
  } catch {
    needVerifyCode.value = false
  }
}

// 用户名变化时防抖检查（不依赖 blur，移动端更可靠）
watch(username, (val) => {
  verifyCode.value = ''
  needVerifyCode.value = false
  if (checkTimer) clearTimeout(checkTimer)
  if (!val.trim()) return
  checkTimer = setTimeout(checkUsername, 400)
})

onUnmounted(() => {
  if (checkTimer) clearTimeout(checkTimer)
})

async function handleLogin() {
  if (!username.value.trim()) return

  const name = username.value.trim()

  // 兜底：手机端可能 watch 防抖还没触发
  if (!needVerifyCode.value) {
    await checkUsername()
  }

  // 需要验证码但未填，停下让用户填
  if (needVerifyCode.value && !verifyCode.value.trim()) {
    showToast('请输入验证码')
    return
  }

  loading.value = true
  showLoadingToast({ message: '登录中...', forbidClick: true, duration: 0 })

  try {
    const code = needVerifyCode.value ? verifyCode.value.trim() : undefined
    await authStore.login(name, code)

    closeToast()
    showToast('登录成功')
    const redirect = (route.query.redirect as string) || '/'
    router.replace(redirect)
  } catch (err: any) {
    closeToast()
    if (err.code === 2) {
      needVerifyCode.value = true
      showToast('首次登录，请输入验证码')
    } else {
      showToast(err.message || '登录失败')
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login-page">
    <div class="login-header">
      <div class="login-logo">
        <van-icon name="chat-o" size="56" color="#1989fa" />
      </div>
      <h1 class="login-title">WeTalk</h1>
      <p class="login-subtitle">欢迎回来</p>
    </div>

    <div class="login-form">
      <van-cell-group inset>
        <van-field
          v-model="username"
          label="账号"
          placeholder="请输入账号"
          clearable
        />
        <van-field
          v-if="needVerifyCode"
          v-model="verifyCode"
          label="验证码"
          placeholder="首次登录，请输入验证码"
          clearable
        />
      </van-cell-group>

      <div class="login-actions">
        <van-button
          type="primary"
          block
          round
          size="large"
          :disabled="!canLogin"
          :loading="loading"
          loading-text="登录中..."
          @click="handleLogin"
        >
          登录
        </van-button>
      </div>

      <p class="login-tip">
        {{ needVerifyCode ? '首次登录请输入管理员提供的验证码' : '输入账号即可登录' }}
      </p>
    </div>
  </div>
</template>

<style scoped>
.login-page {
  min-height: 100vh;
  min-height: 100dvh;
  background: linear-gradient(180deg, #e8f3ff 0%, #f7f8fa 50%);
  display: flex;
  flex-direction: column;
  padding: 0 20px;
}

.login-header {
  text-align: center;
  padding-top: 80px;
  padding-bottom: 40px;
}

.login-logo {
  width: 88px;
  height: 88px;
  border-radius: 22px;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;
  box-shadow: 0 4px 12px rgba(25, 137, 250, 0.15);
}

.login-title {
  margin: 0;
  font-size: 28px;
  font-weight: 700;
  color: #323233;
}

.login-subtitle {
  margin: 8px 0 0;
  font-size: 14px;
  color: #969799;
}

.login-form {
  flex: 1;
}

.login-form :deep(.van-cell-group--inset) {
  margin: 0;
  border-radius: 12px;
  overflow: hidden;
}

.login-form :deep(.van-field__label) {
  width: 56px;
}

.login-actions {
  margin-top: 32px;
  padding: 0 4px;
}

.login-tip {
  text-align: center;
  margin-top: 16px;
  font-size: 13px;
  color: #969799;
  padding: 0 8px;
}
</style>
