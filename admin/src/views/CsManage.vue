<template>
  <div class="cs-manage">
    <div class="toolbar">
      <el-button type="primary" icon="Plus" @click="showCreateDialog">创建客服</el-button>
      <el-button @click="fetchCsList" icon="Refresh">刷新</el-button>
    </div>

    <el-table :data="csList" v-loading="loading" border stripe>
      <el-table-column prop="id" label="ID" width="80" />
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
            <span class="status-dot" :class="row.online ? 'online' : 'offline'" />
            {{ row.online ? '在线' : '离线' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="操作" min-width="200">
        <template #default="{ row }">
          <el-button size="small" type="primary" @click="showEditDialog(row)">编辑</el-button>
          <el-button size="small" @click="handleResetPassword(row)">重置密码</el-button>
          <el-button size="small" type="danger" @click="handleRemoveCs(row)">移除</el-button>
        </template>
      </el-table-column>
    </el-table>

    <!-- 编辑弹窗 -->
    <el-dialog v-model="editDialogVisible" title="编辑客服" width="460px" @close="resetEditForm">
      <el-form :model="editForm" ref="editFormRef" label-width="80px">
        <el-form-item label="用户名">
          <el-input :model-value="editForm.username" disabled />
        </el-form-item>
        <el-form-item label="昵称" prop="nickname">
          <el-input v-model="editForm.nickname" placeholder="请输入昵称" />
        </el-form-item>
        <el-form-item label="新密码" prop="password">
          <el-input v-model="editForm.password" type="password" show-password placeholder="留空则不修改" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="editDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="editLoading" @click="handleEdit">保存</el-button>
      </template>
    </el-dialog>

    <!-- 验证码结果 -->
    <el-dialog v-model="codeDialogVisible" title="验证码" width="400px">
      <el-result icon="success" title="重置成功">
        <template #extra>
          <div class="code-display">
            <p>新验证码：</p>
            <div class="code-value">{{ verifyCode }}</div>
          </div>
        </template>
      </el-result>
      <template #footer>
        <el-button type="primary" @click="codeDialogVisible = false">确定</el-button>
      </template>
    </el-dialog>

    <!-- 创建客服弹窗 -->
    <el-dialog v-model="createDialogVisible" title="创建客服" width="460px" @close="resetCreateForm">
      <el-form :model="createForm" ref="createFormRef" :rules="createRules" label-width="80px">
        <el-form-item label="用户名" prop="username">
          <el-input v-model="createForm.username" placeholder="请输入用户名（登录账号）" />
        </el-form-item>
        <el-form-item label="昵称" prop="nickname">
          <el-input v-model="createForm.nickname" placeholder="请输入昵称" />
        </el-form-item>
        <el-form-item label="密码" prop="password">
          <el-input v-model="createForm.password" type="password" show-password placeholder="请输入登录密码" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="createDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="createLoading" @click="handleCreate">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import type { FormInstance } from 'element-plus'
import { getCsList, removeCs, resetUserPassword, updateUser, createUser, setCs } from '@/api'

const loading = ref(false)
const csList = ref<any[]>([])

const editDialogVisible = ref(false)
const editLoading = ref(false)
const editFormRef = ref<FormInstance>()
const editForm = reactive({ id: 0, username: '', nickname: '', password: '' })

const codeDialogVisible = ref(false)
const verifyCode = ref('')

const createDialogVisible = ref(false)
const createLoading = ref(false)
const createFormRef = ref<FormInstance>()
const createForm = reactive({ username: '', nickname: '', password: '' })
const createRules = {
  username: [{ required: true, message: '请输入用户名', trigger: 'blur' }],
  nickname: [{ required: true, message: '请输入昵称', trigger: 'blur' }],
  password: [{ required: true, message: '请输入密码', trigger: 'blur' }],
}

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

function showEditDialog(row: any) {
  editForm.id = row.id
  editForm.username = row.username || ''
  editForm.nickname = row.nickname || ''
  editForm.password = ''
  editDialogVisible.value = true
}

function resetEditForm() {
  editForm.id = 0
  editForm.username = ''
  editForm.nickname = ''
  editForm.password = ''
}

function showCreateDialog() {
  createForm.username = ''
  createForm.nickname = ''
  createForm.password = ''
  createDialogVisible.value = true
}

function resetCreateForm() {
  createFormRef.value?.resetFields()
}

async function handleCreate() {
  const valid = await createFormRef.value?.validate().catch(() => false)
  if (!valid) return

  createLoading.value = true
  try {
    const res = await createUser({ username: createForm.username, nickname: createForm.nickname, password: createForm.password })
    const userId = res.data.id
    await setCs(userId)
    ElMessage.success('创建客服成功')
    createDialogVisible.value = false
    fetchCsList()
  } catch (err: any) {
    ElMessage.error(err.message || '创建失败')
  } finally {
    createLoading.value = false
  }
}

async function handleEdit() {
  if (!editForm.nickname.trim()) {
    ElMessage.warning('请输入昵称')
    return
  }
  editLoading.value = true
  try {
    const data: Record<string, string> = { nickname: editForm.nickname.trim() }
    if (editForm.password) data.password = editForm.password
    await updateUser(editForm.id, data)
    ElMessage.success('更新成功')
    editDialogVisible.value = false
    fetchCsList()
  } catch (err: any) {
    ElMessage.error(err.message || '更新失败')
  } finally {
    editLoading.value = false
  }
}

async function handleResetPassword(row: any) {
  try {
    await ElMessageBox.confirm(`确定重置客服 ${row.nickname || row.username} 的密码？`, '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    })
    const res = await resetUserPassword(row.id)
    verifyCode.value = res.data.verify_code
    codeDialogVisible.value = true
  } catch (err: any) {
    if (err !== 'cancel') {
      ElMessage.error(err.message || '重置失败')
    }
  }
}

async function handleRemoveCs(row: any) {
  try {
    await ElMessageBox.confirm(`确定移除客服 ${row.nickname || row.username}？该用户将变为普通用户。`, '提示', {
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

.code-display {
  text-align: center;
}

.code-display p {
  font-size: 14px;
  color: #606266;
  margin-bottom: 8px;
}

.code-value {
  font-size: 28px;
  font-weight: 700;
  color: #409eff;
  letter-spacing: 4px;
  padding: 12px 20px;
  background: #ecf5ff;
  border-radius: 4px;
  user-select: all;
}
</style>
