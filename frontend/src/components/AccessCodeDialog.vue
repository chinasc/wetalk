<script setup lang="ts">
import { ref, watch } from 'vue'
import { showToast, showLoadingToast, closeToast } from 'vant'
import { chatApi } from '../api'

const props = defineProps<{
  show: boolean
}>()

const emit = defineEmits<{
  (e: 'update:show', value: boolean): void
  (e: 'verified'): void
}>()

const code = ref('')
const loading = ref(false)

watch(() => props.show, (val) => {
  if (val) {
    code.value = ''
  }
})

async function handleVerify() {
  if (!code.value.trim()) {
    showToast('请输入验证码')
    return
  }
  loading.value = true
  showLoadingToast({ message: '验证中...', forbidClick: true, duration: 0 })
  try {
    await chatApi.verifyCode(code.value.trim())
    closeToast()
    showToast('验证成功')
    emit('update:show', false)
    emit('verified')
  } catch {
    closeToast()
    showToast('验证码错误')
  } finally {
    loading.value = false
  }
}

function handleCancel() {
  emit('update:show', false)
}
</script>

<template>
  <van-popup
    :show="show"
    position="center"
    round
    close-on-click-overlay
    @update:show="emit('update:show', $event)"
  >
    <div class="access-dialog">
      <div class="access-dialog-title">访问验证</div>
      <p class="access-dialog-hint">请输入客服访问验证码</p>
      <van-field
        v-model="code"
        placeholder="请输入验证码"
        type="digit"
        maxlength="6"
        clearable
        center
        size="large"
      />
      <div class="access-dialog-actions">
        <van-button block round @click="handleCancel">取消</van-button>
        <van-button block round type="primary" :loading="loading" @click="handleVerify">验证</van-button>
      </div>
    </div>
  </van-popup>
</template>

<style scoped>
.access-dialog {
  width: 300px;
  padding: 24px 20px 20px;
}

.access-dialog-title {
  font-size: 16px;
  font-weight: 600;
  text-align: center;
  color: #323233;
}

.access-dialog-hint {
  margin: 12px 0;
  font-size: 14px;
  color: #969799;
  text-align: center;
}

.access-dialog-actions {
  display: flex;
  gap: 12px;
  margin-top: 20px;
}
</style>
