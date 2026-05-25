<template>
  <div class="game-manage">
    <div class="toolbar">
      <span class="section-title">游戏列表</span>
      <el-button type="primary" @click="showGameDialog()" icon="Plus">添加游戏</el-button>
    </div>

    <el-table :data="gameList" v-loading="loading" border stripe>
      <el-table-column prop="id" label="ID" width="80" />
      <el-table-column label="封面" width="100">
        <template #default="{ row }">
          <el-image
            v-if="row.cover"
            :src="row.cover"
            style="width: 60px; height: 60px"
            fit="cover"
          />
          <span v-else>-</span>
        </template>
      </el-table-column>
      <el-table-column prop="name" label="游戏名称" min-width="150" />
      <el-table-column prop="description" label="描述" min-width="200" show-overflow-tooltip />
      <el-table-column prop="url" label="链接" min-width="150" show-overflow-tooltip />
      <el-table-column prop="sort_order" label="排序" width="80" />
      <el-table-column label="状态" width="100">
        <template #default="{ row }">
          <el-tag :type="row.status === 1 ? 'success' : 'info'" size="small">
            {{ row.status === 1 ? '启用' : '禁用' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="操作" width="180">
        <template #default="{ row }">
          <el-button size="small" @click="showGameDialog(row)">编辑</el-button>
          <el-button size="small" type="danger" @click="handleDeleteGame(row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>

    <!-- Game Create/Edit Dialog -->
    <el-dialog v-model="dialogVisible" :title="editingGame ? '编辑游戏' : '添加游戏'" width="520px" @close="resetForm">
      <el-form :model="form" label-width="80px">
        <el-form-item label="游戏名称">
          <el-input v-model="form.name" placeholder="请输入游戏名称" />
        </el-form-item>
        <el-form-item label="封面图片">
          <el-upload
            :auto-upload="false"
            :limit="1"
            accept="image/*"
            :on-change="(file: UploadFile) => form.cover = file.raw!"
            :file-list="fileList"
          >
            <el-button size="small">选择图片</el-button>
          </el-upload>
        </el-form-item>
        <el-form-item label="描述">
          <el-input v-model="form.description" type="textarea" :rows="3" placeholder="请输入游戏描述" />
        </el-form-item>
        <el-form-item label="链接">
          <el-input v-model="form.url" placeholder="请输入游戏链接" />
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="form.sort_order" :min="0" />
        </el-form-item>
        <el-form-item label="状态">
          <el-switch v-model="form.status" :active-value="1" :inactive-value="0" active-text="启用" inactive-text="禁用" />
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
import type { UploadFile } from 'element-plus'
import { getGameList, createGame, updateGame, deleteGame } from '@/api'
import type { Game } from '@/types'

const loading = ref(false)
const gameList = ref<Game[]>([])
const dialogVisible = ref(false)
const saving = ref(false)
const editingGame = ref<Game | null>(null)
const fileList = ref<UploadFile[]>([])

const form = reactive({
  name: '',
  cover: null as File | null,
  description: '',
  url: '',
  sort_order: 0,
  status: 1,
})

async function fetchGames() {
  loading.value = true
  try {
    const res = await getGameList()
    const data = res.data
    gameList.value = Array.isArray(data) ? data : data.list || []
  } catch (err: any) {
    ElMessage.error(err.message || '获取游戏列表失败')
  } finally {
    loading.value = false
  }
}

function showGameDialog(game?: Game) {
  editingGame.value = game || null
  if (game) {
    form.name = game.name
    form.description = game.description || ''
    form.url = game.url || ''
    form.sort_order = game.sort_order
    form.status = game.status
    form.cover = null
    fileList.value = []
  } else {
    form.name = ''
    form.description = ''
    form.url = ''
    form.sort_order = 0
    form.status = 1
    form.cover = null
    fileList.value = []
  }
  dialogVisible.value = true
}

function resetForm() {
  form.name = ''
  form.description = ''
  form.url = ''
  form.sort_order = 0
  form.status = 1
  form.cover = null
  fileList.value = []
  editingGame.value = null
}

async function handleSave() {
  if (!form.name.trim()) {
    ElMessage.warning('请输入游戏名称')
    return
  }

  saving.value = true
  try {
    const fd = new FormData()
    fd.append('name', form.name)
    fd.append('description', form.description)
    fd.append('url', form.url)
    fd.append('sort_order', String(form.sort_order))
    fd.append('status', String(form.status))
    if (form.cover) {
      fd.append('cover', form.cover)
    }

    if (editingGame.value) {
      await updateGame(editingGame.value.id, fd)
    } else {
      await createGame(fd)
    }

    ElMessage.success(editingGame.value ? '更新成功' : '创建成功')
    dialogVisible.value = false
    fetchGames()
  } catch (err: any) {
    ElMessage.error(err.message || '操作失败')
  } finally {
    saving.value = false
  }
}

async function handleDeleteGame(row: Game) {
  try {
    await ElMessageBox.confirm(`确定删除游戏 "${row.name}"？`, '警告', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    })
    await deleteGame(row.id)
    ElMessage.success('删除成功')
    fetchGames()
  } catch (err: any) {
    if (err !== 'cancel') {
      ElMessage.error(err.message || '删除失败')
    }
  }
}

onMounted(() => {
  fetchGames()
})
</script>

<style scoped>
.game-manage {
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
