<template>
  <div class="album-manage">
    <!-- Album List View -->
    <div v-if="!currentAlbum">
      <div class="toolbar">
        <span class="section-title">相册列表</span>
        <el-button type="primary" @click="showAlbumDialog()" icon="Plus">创建相册</el-button>
      </div>

      <el-table :data="albumList" v-loading="loading" border stripe>
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
        <el-table-column prop="name" label="相册名称" min-width="200" />
        <el-table-column prop="image_count" label="图片数量" width="100" />
        <el-table-column prop="sort_order" label="排序" width="80" />
        <el-table-column prop="created_at" label="创建时间" width="180" />
        <el-table-column label="操作" width="260">
          <template #default="{ row }">
            <el-button size="small" type="primary" @click="openAlbum(row)">管理图片</el-button>
            <el-button size="small" @click="showAlbumDialog(row)">编辑</el-button>
            <el-button size="small" type="danger" @click="handleDeleteAlbum(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- Album Detail / Image Management View -->
    <div v-else>
      <div class="toolbar">
        <div class="toolbar-left">
          <el-button @click="currentAlbum = null" icon="ArrowLeft">返回相册列表</el-button>
          <span class="section-title">{{ currentAlbum.name }} - 图片管理</span>
        </div>
        <el-button type="primary" @click="showImageDialog()" icon="Plus">上传图片</el-button>
      </div>

      <div class="image-grid" v-loading="imageLoading">
        <div v-for="img in imageList" :key="img.id" class="image-card">
          <el-image :src="img.url" fit="cover" class="image-thumb" />
          <div class="image-info">
            <p class="image-remark">{{ img.remark || '无备注' }}</p>
            <p class="image-sort">排序: {{ img.sort_order }}</p>
          </div>
          <div class="image-actions">
            <el-button size="small" @click="handleSetCover(img)" :disabled="currentAlbum?.cover === img.url">
              设为封面
            </el-button>
            <el-button size="small" @click="showImageDialog(img)">编辑</el-button>
            <el-button size="small" type="danger" @click="handleDeleteImage(img)">删除</el-button>
          </div>
        </div>
        <el-empty v-if="!imageLoading && imageList.length === 0" description="暂无图片" />
      </div>
    </div>

    <!-- Album Create/Edit Dialog -->
    <el-dialog v-model="albumDialogVisible" :title="editingAlbum ? '编辑相册' : '创建相册'" width="480px" @close="resetAlbumForm">
      <el-form :model="albumForm" label-width="80px">
        <el-form-item label="相册名称">
          <el-input v-model="albumForm.name" placeholder="请输入相册名称" />
        </el-form-item>
        <el-form-item label="封面图片">
          <el-upload
            :auto-upload="false"
            :limit="1"
            accept="image/*"
            :on-change="(file: UploadFile) => albumForm.cover = file.raw!"
            :file-list="albumFileList"
          >
            <el-button size="small">选择图片</el-button>
          </el-upload>
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="albumForm.sort_order" :min="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="albumDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="albumSaving" @click="handleSaveAlbum">确定</el-button>
      </template>
    </el-dialog>

    <!-- Image Upload/Edit Dialog -->
    <el-dialog v-model="imageDialogVisible" :title="editingImage ? '编辑图片' : '上传图片'" width="480px" @close="resetImageForm">
      <el-form :model="imageForm" label-width="80px">
        <el-form-item v-if="!editingImage" label="图片">
          <el-upload
            :auto-upload="false"
            :limit="20"
            accept="image/*"
            multiple
            :on-change="handleImageFileChange"
            :file-list="imageFileList"
          >
            <el-button size="small">选择图片</el-button>
            <template #tip>
              <div class="el-upload__tip">支持多选，最多20张</div>
            </template>
          </el-upload>
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="imageForm.remark" type="textarea" :rows="2" placeholder="请输入备注（批量上传时统一设置）" />
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="imageForm.sort_order" :min="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="imageDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="imageSaving" @click="handleSaveImage">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import type { UploadFile } from 'element-plus'
import {
  getAlbumList,
  getAlbumDetail,
  createAlbum,
  updateAlbum,
  deleteAlbum,
  uploadAlbumImage,
  updateAlbumImage,
  deleteAlbumImage,
} from '@/api'
import type { Album, AlbumImage } from '@/types'

const loading = ref(false)
const albumList = ref<Album[]>([])
const currentAlbum = ref<Album | null>(null)
const imageLoading = ref(false)
const imageList = ref<AlbumImage[]>([])

// Album dialog
const albumDialogVisible = ref(false)
const albumSaving = ref(false)
const editingAlbum = ref<Album | null>(null)
const albumFileList = ref<UploadFile[]>([])
const albumForm = reactive({
  name: '',
  cover: null as File | null,
  sort_order: 0,
})

// Image dialog
const imageDialogVisible = ref(false)
const imageSaving = ref(false)
const editingImage = ref<AlbumImage | null>(null)
const imageFileList = ref<UploadFile[]>([])
const imageForm = reactive({
  remark: '',
  sort_order: 0,
})

async function fetchAlbums() {
  loading.value = true
  try {
    const res = await getAlbumList()
    const data = res.data
    albumList.value = Array.isArray(data) ? data : data.list || []
  } catch (err: any) {
    ElMessage.error(err.message || '获取相册列表失败')
  } finally {
    loading.value = false
  }
}

function showAlbumDialog(album?: Album) {
  editingAlbum.value = album || null
  if (album) {
    albumForm.name = album.name
    albumForm.sort_order = album.sort_order
    albumForm.cover = null
    albumFileList.value = []
  } else {
    albumForm.name = ''
    albumForm.sort_order = 0
    albumForm.cover = null
    albumFileList.value = []
  }
  albumDialogVisible.value = true
}

function resetAlbumForm() {
  albumForm.name = ''
  albumForm.sort_order = 0
  albumForm.cover = null
  albumFileList.value = []
  editingAlbum.value = null
}

async function handleSaveAlbum() {
  if (!albumForm.name.trim()) {
    ElMessage.warning('请输入相册名称')
    return
  }

  albumSaving.value = true
  try {
    const fd = new FormData()
    fd.append('name', albumForm.name)
    fd.append('sort_order', String(albumForm.sort_order))
    if (albumForm.cover) {
      fd.append('cover', albumForm.cover)
    }

    if (editingAlbum.value) {
      await updateAlbum(editingAlbum.value.id, fd)
    } else {
      await createAlbum(fd)
    }

    ElMessage.success(editingAlbum.value ? '更新成功' : '创建成功')
    albumDialogVisible.value = false
    fetchAlbums()
  } catch (err: any) {
    ElMessage.error(err.message || '操作失败')
  } finally {
    albumSaving.value = false
  }
}

async function handleDeleteAlbum(row: Album) {
  try {
    await ElMessageBox.confirm(`确定删除相册 "${row.name}"？所有图片将被删除。`, '警告', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    })
    await deleteAlbum(row.id)
    ElMessage.success('删除成功')
    fetchAlbums()
  } catch (err: any) {
    if (err !== 'cancel') {
      ElMessage.error(err.message || '删除失败')
    }
  }
}

async function openAlbum(album: Album) {
  currentAlbum.value = album
  await fetchImages(album.id)
}

async function fetchImages(albumId: number) {
  imageLoading.value = true
  try {
    const res = await getAlbumDetail(albumId)
    const data = res.data
    imageList.value = data.images || []
    currentAlbum.value = data
  } catch (err: any) {
    ElMessage.error(err.message || '获取图片列表失败')
  } finally {
    imageLoading.value = false
  }
}

function handleImageFileChange(file: UploadFile, fileList: UploadFile[]) {
  imageFileList.value = fileList
}

function showImageDialog(img?: AlbumImage) {
  editingImage.value = img || null
  if (img) {
    imageForm.remark = img.remark
    imageForm.sort_order = img.sort_order
  } else {
    imageForm.remark = ''
    imageForm.sort_order = 0
  }
  imageFileList.value = []
  imageDialogVisible.value = true
}

function resetImageForm() {
  imageForm.remark = ''
  imageForm.sort_order = 0
  imageFileList.value = []
  editingImage.value = null
}

async function handleSaveImage() {
  if (editingImage.value) {
    imageSaving.value = true
    try {
      await updateAlbumImage(editingImage.value.id, {
        remark: imageForm.remark,
        sort_order: imageForm.sort_order,
      })
      ElMessage.success('更新成功')
      imageDialogVisible.value = false
      if (currentAlbum.value) {
        fetchImages(currentAlbum.value.id)
      }
    } catch (err: any) {
      ElMessage.error(err.message || '操作失败')
    } finally {
      imageSaving.value = false
    }
    return
  }

  const files = imageFileList.value.map(f => f.raw).filter(Boolean) as File[]
  if (files.length === 0) {
    ElMessage.warning('请选择图片')
    return
  }

  imageSaving.value = true
  let successCount = 0
  try {
    for (const file of files) {
      const fd = new FormData()
      fd.append('image', file)
      fd.append('remark', imageForm.remark)
      fd.append('sort_order', String(imageForm.sort_order))
      try {
        await uploadAlbumImage(currentAlbum.value!.id, fd)
        successCount++
      } catch { /* continue uploading remaining */ }
    }
    if (successCount > 0) {
      ElMessage.success(`成功上传 ${successCount} 张图片`)
      imageDialogVisible.value = false
      if (currentAlbum.value) {
        fetchImages(currentAlbum.value.id)
      }
    } else {
      ElMessage.error('上传失败')
    }
  } finally {
    imageSaving.value = false
  }
}

async function handleSetCover(img: AlbumImage) {
  if (!currentAlbum.value) return
  try {
    const fd = new FormData()
    fd.append('name', currentAlbum.value.name)
    fd.append('sort_order', String(currentAlbum.value.sort_order))
    fd.append('cover', img.url)
    await updateAlbum(currentAlbum.value.id, fd)
    ElMessage.success('封面已更新')
    fetchImages(currentAlbum.value.id)
  } catch (err: any) {
    ElMessage.error(err.message || '设置封面失败')
  }
}

async function handleDeleteImage(img: AlbumImage) {
  try {
    await ElMessageBox.confirm('确定删除此图片？', '警告', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    })
    await deleteAlbumImage(img.id)
    ElMessage.success('删除成功')
    if (currentAlbum.value) {
      fetchImages(currentAlbum.value.id)
    }
  } catch (err: any) {
    if (err !== 'cancel') {
      ElMessage.error(err.message || '删除失败')
    }
  }
}

onMounted(() => {
  fetchAlbums()
})
</script>

<style scoped>
.album-manage {
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

.toolbar-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.section-title {
  font-size: 16px;
  font-weight: 600;
  color: #303133;
}

.image-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 16px;
}

.image-card {
  border: 1px solid #ebeef5;
  border-radius: 4px;
  overflow: hidden;
}

.image-thumb {
  width: 100%;
  height: 180px;
  display: block;
}

.image-info {
  padding: 8px 12px;
}

.image-remark {
  font-size: 13px;
  color: #303133;
  margin-bottom: 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.image-sort {
  font-size: 12px;
  color: #909399;
}

.image-actions {
  padding: 8px 12px;
  border-top: 1px solid #ebeef5;
  display: flex;
  gap: 4px;
  flex-wrap: wrap;
}
</style>
