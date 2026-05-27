<template>
  <div class="user-manage">
    <div class="toolbar">
      <div class="search-area">
        <el-input
          v-model="keyword"
          placeholder="搜索用户名/昵称"
          clearable
          style="width: 260px"
          @keyup.enter="handleSearch"
          @clear="handleSearch"
        >
          <template #append>
            <el-button @click="handleSearch" icon="Search" />
          </template>
        </el-input>
      </div>
      <el-button type="primary" @click="showCreateDialog" icon="Plus">创建用户</el-button>
    </div>

    <el-table :data="userList" v-loading="loading" border stripe>
      <el-table-column prop="id" label="ID" width="80" />
      <el-table-column prop="username" label="用户名" width="150" />
      <el-table-column prop="nickname" label="昵称" width="150" />
      <el-table-column label="头像" width="80">
        <template #default="{ row }">
          <el-avatar :size="36" :src="row.avatar || undefined" icon="User" />
        </template>
      </el-table-column>
      <el-table-column label="状态" width="100">
        <template #default="{ row }">
          <el-tag :type="row.status === 1 ? 'success' : 'danger'" size="small">
            {{ row.status === 1 ? '正常' : '禁用' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="验证码" width="120">
        <template #default="{ row }">
          <span v-if="row.verify_code && !row.verify_code_used" class="code-tag">{{ row.verify_code }}</span>
          <span v-else class="used-tag">已使用</span>
        </template>
      </el-table-column>
      <el-table-column label="角色" width="100">
        <template #default="{ row }">
          <el-tag v-if="row.role === 'customer_service'" type="warning" size="small">客服</el-tag>
          <el-tag v-else-if="row.role === 'admin'" type="danger" size="small">管理员</el-tag>
          <el-tag v-else size="small">用户</el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="created_at" label="注册时间" width="180" />
      <el-table-column label="操作" min-width="320">
        <template #default="{ row }">
          <el-button
            v-if="row.role === 'user'"
            size="small"
            type="warning"
            @click="handleSetCs(row)"
          >设为客服</el-button>
          <el-button size="small" @click="handleResetPassword(row)">重置密码</el-button>
          <el-button
            size="small"
            :type="row.status === 1 ? 'warning' : 'success'"
            @click="handleToggleStatus(row)"
          >
            {{ row.status === 1 ? '禁用' : '启用' }}
          </el-button>
          <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>

    <div class="pagination-area" v-if="total > 0">
      <el-pagination
        v-model:current-page="page"
        :page-size="20"
        :total="total"
        layout="total, prev, pager, next"
        @current-change="fetchUsers"
      />
    </div>

    <!-- Create User Dialog -->
    <el-dialog v-model="createDialogVisible" title="创建用户" width="460px" @close="resetCreateForm">
      <el-form :model="createForm" :rules="createRules" ref="createFormRef" label-width="80px">
        <el-form-item label="用户名" prop="username">
          <el-input v-model="createForm.username" placeholder="请输入用户名" />
        </el-form-item>
        <el-form-item label="昵称" prop="nickname">
          <el-input v-model="createForm.nickname" placeholder="请输入昵称" />
        </el-form-item>
        <el-form-item label="密码" prop="password">
          <el-input v-model="createForm.password" type="password" show-password placeholder="请输入密码（用于客服端登录）" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="createDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="createLoading" @click="handleCreate">确定</el-button>
      </template>
    </el-dialog>

    <!-- Verify Code Result Dialog -->
    <el-dialog v-model="codeDialogVisible" title="验证码" width="400px">
      <el-result icon="success" title="操作成功">
        <template #extra>
          <div class="code-display">
            <p>验证码：</p>
            <div class="code-value">{{ verifyCode }}</div>
          </div>
        </template>
      </el-result>
      <template #footer>
        <el-button type="primary" @click="codeDialogVisible = false">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import type { FormInstance } from 'element-plus'
import { getUserList, createUser, resetUserPassword, toggleUserStatus, deleteUser, setCs } from '@/api'
import type { User } from '@/types'

const loading = ref(false)
const userList = ref<User[]>([])
const keyword = ref('')
const page = ref(1)
const total = ref(0)

const createDialogVisible = ref(false)
const createLoading = ref(false)
const createFormRef = ref<FormInstance>()
const createForm = reactive({ username: '', nickname: '', password: '' })
const createRules = {
  username: [{ required: true, message: '请输入用户名', trigger: 'blur' }],
  nickname: [{ required: true, message: '请输入昵称', trigger: 'blur' }],
  password: [{ required: true, message: '请输入密码', trigger: 'blur' }],
}

const codeDialogVisible = ref(false)
const verifyCode = ref('')

async function fetchUsers() {
  loading.value = true
  try {
    const res = await getUserList(keyword.value, page.value)
    const pageData = res.data
    userList.value = pageData.data
    total.value = pageData.total
  } catch (err: any) {
    ElMessage.error(err.message || '获取用户列表失败')
  } finally {
    loading.value = false
  }
}

function handleSearch() {
  page.value = 1
  fetchUsers()
}

function showCreateDialog() {
  createDialogVisible.value = true
}

function resetCreateForm() {
  createForm.username = ''
  createForm.nickname = ''
  createForm.password = ''
  createFormRef.value?.resetFields()
}

async function handleCreate() {
  const valid = await createFormRef.value?.validate().catch(() => false)
  if (!valid) return

  createLoading.value = true
  try {
    const res = await createUser({ username: createForm.username, nickname: createForm.nickname, password: createForm.password })
    createDialogVisible.value = false
    verifyCode.value = res.data.verify_code
    codeDialogVisible.value = true
    fetchUsers()
  } catch (err: any) {
    ElMessage.error(err.message || '创建用户失败')
  } finally {
    createLoading.value = false
  }
}

async function handleResetPassword(row: any) {
  try {
    await ElMessageBox.confirm(`确定重置用户 ${row.nickname || row.username} 的密码？`, '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    })
    const res = await resetUserPassword(row.id)
    verifyCode.value = res.data.verify_code
    codeDialogVisible.value = true
  } catch (err: any) {
    if (err !== 'cancel') {
      ElMessage.error(err.message || '重置密码失败')
    }
  }
}

async function handleSetCs(row: any) {
  try {
    await ElMessageBox.confirm(`确定将用户 ${row.nickname || row.username} 设为客服？`, '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    })
    await setCs(row.id)
    ElMessage.success('设置成功')
    fetchUsers()
  } catch (err: any) {
    if (err !== 'cancel') {
      ElMessage.error(err.message || '设置失败')
    }
  }
}

async function handleToggleStatus(row: any) {
  try {
    await toggleUserStatus(row.id)
    ElMessage.success('操作成功')
    fetchUsers()
  } catch (err: any) {
    ElMessage.error(err.message || '操作失败')
  }
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm(`确定删除用户 ${row.nickname || row.username}？此操作不可恢复。`, '警告', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    })
    await deleteUser(row.id)
    ElMessage.success('删除成功')
    fetchUsers()
  } catch (err: any) {
    if (err !== 'cancel') {
      ElMessage.error(err.message || '删除失败')
    }
  }
}

onMounted(() => {
  fetchUsers()
})
</script>

<style scoped>
.user-manage {
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

.pagination-area {
  display: flex;
  justify-content: flex-end;
  margin-top: 16px;
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
