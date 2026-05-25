<template>
  <div class="cs-manage">
    <div class="toolbar">
      <div class="search-area">
        <el-input
          v-model="userIdInput"
          placeholder="输入用户ID设为客服"
          style="width: 240px"
          type="number"
        >
          <template #append>
            <el-button @click="handleSetCs" icon="Plus" :loading="setLoading">设为客服</el-button>
          </template>
        </el-input>
      </div>
      <el-button @click="fetchCsList" icon="Refresh">刷新</el-button>
    </div>

    <el-table :data="csList" v-loading="loading" border stripe>
      <el-table-column prop="id" label="ID" width="80" />
      <el-table-column prop="user_id" label="用户ID" width="100" />
      <el-table-column prop="username" label="用户名" width="150" />
      <el-table-column prop="nickname" label="昵称" width="150" />
      <el-table-column label="头像" width="80">
        <template #default="{ row }">
          <el-avatar :size="36" :src="row.avatar || undefined" icon="User" />
        </template>
      </el-table-column>
      <el-table-column label="在线状态" width="120">
        <template #default="{ row }">
          <el-tag :type="row.online ? 'success' : 'info'" size="small">
            <span class="status-dot" :class="row.is_online ? 'online' : 'offline'" />
            {{ row.online ? '在线' : '离线' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="操作" width="120">
        <template #default="{ row }">
          <el-button size="small" type="danger" @click="handleRemoveCs(row)">移除</el-button>
        </template>
      </el-table-column>
    </el-table>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getCsList, setCs, removeCs } from '@/api'
import type { CsItem } from '@/types'

const loading = ref(false)
const csList = ref<CsItem[]>([])
const userIdInput = ref('')
const setLoading = ref(false)

async function fetchCsList() {
  loading.value = true
  try {
    const res = await getCsList()
    const data = res.data
    csList.value = Array.isArray(data) ? data : data.list || []
  } catch (err: any) {
    ElMessage.error(err.message || '获取客服列表失败')
  } finally {
    loading.value = false
  }
}

async function handleSetCs() {
  const userId = Number(userIdInput.value)
  if (!userId || userId <= 0) {
    ElMessage.warning('请输入有效的用户ID')
    return
  }

  setLoading.value = true
  try {
    await setCs(userId)
    ElMessage.success('设置成功')
    userIdInput.value = ''
    fetchCsList()
  } catch (err: any) {
    ElMessage.error(err.message || '设置客服失败')
  } finally {
    setLoading.value = false
  }
}

async function handleRemoveCs(row: CsItem) {
  try {
    await ElMessageBox.confirm(`确定移除客服 ${row.nickname || row.username}？`, '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    })
    await removeCs(row.id)
    ElMessage.success('移除成功')
    fetchCsList()
  } catch (err: any) {
    if (err !== 'cancel') {
      ElMessage.error(err.message || '移除失败')
    }
  }
}

onMounted(() => {
  fetchCsList()
})
</script>

<style scoped>
.cs-manage {
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

.status-dot {
  display: inline-block;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  margin-right: 4px;
  vertical-align: middle;
}

.status-dot.online {
  background-color: #67c23a;
  box-shadow: 0 0 4px #67c23a;
}

.status-dot.offline {
  background-color: #909399;
}
</style>
