<template>
  <div class="settings-page">
    <div class="toolbar">
      <span class="section-title">系统设置</span>
    </div>

    <el-form v-loading="loading" :model="form" label-width="140px" style="max-width: 600px">
      <el-form-item label="聊天访问码">
        <el-input v-model="form.chat_access_code" placeholder="请输入聊天访问码" />
      </el-form-item>
      <el-form-item label="上传大小限制(MB)">
        <el-input-number v-model="form.upload_max_size" :min="1" :max="100" />
      </el-form-item>
      <el-form-item>
        <el-button type="primary" :loading="saving" @click="handleSave">保存设置</el-button>
      </el-form-item>
    </el-form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getSettings, saveSettings } from '@/api'

const loading = ref(false)
const saving = ref(false)

const form = reactive({
  chat_access_code: '',
  upload_max_size: 5,
})

async function fetchSettings() {
  loading.value = true
  try {
    const res = await getSettings()
    const settings = res.data
    form.chat_access_code = settings.chat_access_code || ''
    form.upload_max_size = settings.upload_max_size || 5
  } catch (err: any) {
    ElMessage.error(err.message || '获取设置失败')
  } finally {
    loading.value = false
  }
}

async function handleSave() {
  saving.value = true
  try {
    await saveSettings({
      chat_access_code: form.chat_access_code,
      upload_max_size: form.upload_max_size,
    })
    ElMessage.success('保存成功')
  } catch (err: any) {
    ElMessage.error(err.message || '保存失败')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  fetchSettings()
})
</script>

<style scoped>
.settings-page {
  background: #fff;
  border-radius: 4px;
  padding: 20px;
}

.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.section-title {
  font-size: 16px;
  font-weight: 600;
  color: #303133;
}
</style>
