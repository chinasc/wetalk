<template>
  <div class="quick-reply-manage">
    <div class="toolbar">
      <span class="section-title">常用语列表</span>
      <el-button type="primary" @click="showDialog()" icon="Plus">添加常用语</el-button>
    </div>

    <el-table :data="replyList" v-loading="loading" border stripe>
      <el-table-column prop="id" label="ID" width="80" />
      <el-table-column prop="title" label="标题" width="180" />
      <el-table-column prop="content" label="内容" min-width="300" show-overflow-tooltip />
      <el-table-column prop="sort_order" label="排序" width="80" />
      <el-table-column label="操作" width="180">
        <template #default="{ row }">
          <el-button size="small" @click="showDialog(row)">编辑</el-button>
          <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-dialog v-model="dialogVisible" :title="editing ? '编辑常用语' : '添加常用语'" width="520px" @close="resetForm">
      <el-form :model="form" label-width="80px">
        <el-form-item label="标题">
          <el-input v-model="form.title" placeholder="如：问候语、常见回复" />
        </el-form-item>
        <el-form-item label="内容">
          <el-input v-model="form.content" type="textarea" :rows="4" placeholder="请输入常用语内容" />
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="form.sort_order" :min="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="handleSave">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getQuickReplyList, createQuickReply, updateQuickReply, deleteQuickReply } from '@/api'
import type { QuickReply } from '@/types'

const loading = ref(false)
const replyList = ref<QuickReply[]>([])
const dialogVisible = ref(false)
const saving = ref(false)
const editing = ref<QuickReply | null>(null)

const form = reactive({
  title: '',
  content: '',
  sort_order: 0,
})

async function fetchList() {
  loading.value = true
  try {
    const res = await getQuickReplyList()
    const data = res.data
    replyList.value = Array.isArray(data) ? data : data.list || []
  } catch (err: any) {
    ElMessage.error(err.message || '获取列表失败')
  } finally {
    loading.value = false
  }
}

function showDialog(item?: QuickReply) {
  editing.value = item || null
  if (item) {
    form.title = item.title
    form.content = item.content
    form.sort_order = item.sort_order
  } else {
    form.title = ''
    form.content = ''
    form.sort_order = 0
  }
  dialogVisible.value = true
}

function resetForm() {
  form.title = ''
  form.content = ''
  form.sort_order = 0
  editing.value = null
}

async function handleSave() {
  if (!form.title.trim()) {
    ElMessage.warning('请输入标题')
    return
  }
  if (!form.content.trim()) {
    ElMessage.warning('请输入内容')
    return
  }

  saving.value = true
  try {
    const data = { title: form.title, content: form.content, sort_order: form.sort_order }
    if (editing.value) {
      await updateQuickReply(editing.value.id, data)
    } else {
      await createQuickReply(data)
    }
    ElMessage.success(editing.value ? '更新成功' : '创建成功')
    dialogVisible.value = false
    fetchList()
  } catch (err: any) {
    ElMessage.error(err.message || '操作失败')
  } finally {
    saving.value = false
  }
}

async function handleDelete(row: QuickReply) {
  try {
    await ElMessageBox.confirm(`确定删除常用语 "${row.title}"？`, '警告', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    })
    await deleteQuickReply(row.id)
    ElMessage.success('删除成功')
    fetchList()
  } catch (err: any) {
    if (err !== 'cancel') {
      ElMessage.error(err.message || '删除失败')
    }
  }
}

onMounted(() => {
  fetchList()
})
</script>

<style scoped>
.quick-reply-manage {
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

.section-title {
  font-size: 16px;
  font-weight: 600;
  color: #303133;
}
</style>
