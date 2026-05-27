<template>
  <div class="manage-page">
    <van-nav-bar :title="currentAlbum ? currentAlbum.name : '相册管理'" left-arrow fixed placeholder @click-left="onBack" />

    <!-- 相册列表 -->
    <template v-if="!currentAlbum">
      <div class="toolbar">
        <van-button type="primary" size="small" round icon="plus" @click="showAlbumDialog()">创建相册</van-button>
      </div>

      <van-loading v-if="loading" class="page-loading" />

      <van-empty v-else-if="albums.length === 0" description="暂无相册" image="search" />

      <van-cell-group v-else inset class="list-group">
        <van-swipe-cell v-for="album in albums" :key="album.id">
          <van-cell class="album-cell" clickable @click="openAlbum(album)">
            <template #title>
              <div class="album-row">
                <van-image :src="album.cover" width="56" height="56" fit="cover" radius="6" class="album-cover">
                  <template #error>
                    <div class="cover-placeholder"><van-icon name="photo-o" size="24" color="#969799" /></div>
                  </template>
                </van-image>
                <div class="album-info">
                  <div class="album-name">{{ album.name }}</div>
                  <div class="album-count">{{ album.images_count || 0 }} 张照片</div>
                </div>
              </div>
            </template>
            <template #value>
              <div class="cell-actions">
                <van-button size="mini" plain type="primary" @click.stop="showAlbumDialog(album)">编辑</van-button>
              </div>
            </template>
          </van-cell>
          <template #right>
            <van-button square type="danger" text="删除" class="delete-btn" @click="onDeleteAlbum(album)" />
          </template>
        </van-swipe-cell>
      </van-cell-group>
    </template>

    <!-- 相册详情（图片管理） -->
    <template v-else>
      <div class="toolbar">
        <van-button type="primary" size="small" round icon="plus" @click="onPickImage">上传图片</van-button>
      </div>

      <van-loading v-if="imagesLoading" class="page-loading" />

      <van-empty v-else-if="images.length === 0" description="暂无图片" image="search" />

      <van-cell-group v-else inset class="list-group">
        <van-swipe-cell v-for="img in images" :key="img.id">
          <van-cell class="img-cell">
            <template #title>
              <div class="img-row">
                <van-image :src="img.image_path || img.url" width="56" height="56" fit="cover" radius="6" />
                <div class="img-info">
                  <div class="img-remark">{{ img.remark || '无备注' }}</div>
                </div>
              </div>
            </template>
            <template #value>
              <div class="cell-actions">
                <van-button size="mini" plain type="primary" @click="showImageDialog(img)">编辑</van-button>
                <van-button size="mini" plain type="danger" @click="onDeleteImage(img)">删除</van-button>
              </div>
            </template>
          </van-cell>
        </van-swipe-cell>
      </van-cell-group>

      <input ref="fileInputRef" type="file" accept="image/*" multiple style="display: none" @change="onFileSelected" />
    </template>

    <!-- 相册弹窗 -->
    <van-dialog
      v-model:show="albumDialogVisible"
      :title="editingAlbum ? '编辑相册' : '创建相册'"
      show-cancel-button
      :before-close="onAlbumDialogClose"
    >
      <div class="dialog-form">
        <van-field v-model="albumForm.name" label="名称" placeholder="请输入相册名称" />
        <van-field v-model.number="albumForm.sort_order" label="排序" type="digit" placeholder="越小越靠前" />
        <van-field label="封面">
          <template #input>
            <input type="file" accept="image/*" @change="onCoverSelected" class="file-input" />
          </template>
        </van-field>
      </div>
    </van-dialog>

    <!-- 图片备注弹窗 -->
    <van-dialog
      v-model:show="imageDialogVisible"
      title="编辑图片"
      show-cancel-button
      :before-close="onImageDialogClose"
    >
      <div class="dialog-form">
        <van-field v-model="imageForm.remark" label="备注" type="textarea" rows="2" placeholder="展示给用户的备注" />
        <van-field v-model.number="imageForm.sort_order" label="排序" type="digit" />
      </div>
    </van-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { showToast, showConfirmDialog, showLoadingToast, closeToast } from 'vant'
import {
  getAlbums,
  getAlbumDetail,
  createAlbum,
  updateAlbum,
  deleteAlbum,
  uploadAlbumImage,
  updateAlbumImage,
  deleteAlbumImage,
} from '../api'

const route = useRoute()

const loading = ref(false)
const albums = ref<any[]>([])
const currentAlbum = ref<any>(null)
const images = ref<any[]>([])
const imagesLoading = ref(false)

// 相册弹窗
const albumDialogVisible = ref(false)
const editingAlbum = ref<any>(null)
const coverFile = ref<File | null>(null)
const albumForm = reactive({ name: '', sort_order: 0 })

// 图片弹窗
const imageDialogVisible = ref(false)
const editingImage = ref<any>(null)
const imageForm = reactive({ remark: '', sort_order: 0 })

const fileInputRef = ref<HTMLInputElement | null>(null)

// ============ 相册列表 ============

async function fetchAlbums() {
  loading.value = true
  try {
    albums.value = await getAlbums()
  } catch (err: any) {
    showToast(err.message || '获取失败')
  } finally {
    loading.value = false
  }
}

function showAlbumDialog(album?: any) {
  editingAlbum.value = album || null
  coverFile.value = null
  if (album) {
    albumForm.name = album.name
    albumForm.sort_order = album.sort_order
  } else {
    albumForm.name = ''
    albumForm.sort_order = 0
  }
  albumDialogVisible.value = true
}

function onCoverSelected(e: Event) {
  const input = e.target as HTMLInputElement
  coverFile.value = input.files?.[0] || null
}

async function onAlbumDialogClose(action: string) {
  if (action !== 'confirm') return true
  if (!albumForm.name.trim()) {
    showToast('请输入名称')
    return false
  }
  try {
    const fd = new FormData()
    fd.append('name', albumForm.name)
    fd.append('sort_order', String(albumForm.sort_order))
    if (coverFile.value) fd.append('cover', coverFile.value)

    if (editingAlbum.value) {
      await updateAlbum(editingAlbum.value.id, fd)
    } else {
      await createAlbum(fd)
    }
    showToast(editingAlbum.value ? '更新成功' : '创建成功')
    fetchAlbums()
    return true
  } catch (err: any) {
    showToast(err.message || '操作失败')
    return false
  }
}

async function onDeleteAlbum(album: any) {
  try {
    await showConfirmDialog({ title: '确认删除', message: `删除相册"${album.name}"及其所有图片？` })
    await deleteAlbum(album.id)
    showToast('已删除')
    fetchAlbums()
  } catch { /* cancelled */ }
}

// ============ 图片管理 ============

async function openAlbum(album: any) {
  currentAlbum.value = album
  imagesLoading.value = true
  try {
    const detail = await getAlbumDetail(album.id)
    images.value = detail.images || detail.photos || []
  } catch (err: any) {
    showToast(err.message || '获取失败')
  } finally {
    imagesLoading.value = false
  }
}

function onBack() {
  if (currentAlbum.value) {
    currentAlbum.value = null
    images.value = []
    fetchAlbums()
  } else {
    history.back()
  }
}

function onPickImage() {
  fileInputRef.value?.click()
}

async function onFileSelected(e: Event) {
  const input = e.target as HTMLInputElement
  if (!input.files?.length) return
  showLoadingToast({ message: '上传中...', forbidClick: true, duration: 0 })
  try {
    for (const file of Array.from(input.files)) {
      const fd = new FormData()
      fd.append('image', file)
      await uploadAlbumImage(currentAlbum.value.id, fd)
    }
    showToast('上传成功')
    openAlbum(currentAlbum.value)
  } catch (err: any) {
    showToast(err.message || '上传失败')
  } finally {
    closeToast()
    input.value = ''
  }
}

function showImageDialog(img: any) {
  editingImage.value = img
  imageForm.remark = img.remark || ''
  imageForm.sort_order = img.sort_order || 0
  imageDialogVisible.value = true
}

async function onImageDialogClose(action: string) {
  if (action !== 'confirm') return true
  try {
    await updateAlbumImage(editingImage.value.id, {
      remark: imageForm.remark,
      sort_order: imageForm.sort_order,
    })
    showToast('更新成功')
    openAlbum(currentAlbum.value)
    return true
  } catch (err: any) {
    showToast(err.message || '操作失败')
    return false
  }
}

async function onDeleteImage(img: any) {
  try {
    await showConfirmDialog({ title: '确认删除', message: '删除该图片？' })
    await deleteAlbumImage(img.id)
    showToast('已删除')
    openAlbum(currentAlbum.value)
  } catch { /* cancelled */ }
}

// ============ 初始化 ============

onMounted(() => {
  const albumId = route.params.id
  if (albumId) {
    const album = { id: Number(albumId), name: '相册详情' }
    currentAlbum.value = album
    openAlbum(album)
  } else {
    fetchAlbums()
  }
})
</script>

<style scoped>
.manage-page {
  min-height: 100vh;
  min-height: 100dvh;
  background: #f5f5f5;
}

.manage-page :deep(.van-nav-bar) {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.manage-page :deep(.van-nav-bar__title) {
  color: #fff;
  font-weight: 600;
}

.manage-page :deep(.van-nav-bar .van-icon) {
  color: #fff !important;
}

.toolbar {
  display: flex;
  justify-content: flex-end;
  padding: 12px 16px 0;
}

.page-loading {
  display: flex;
  justify-content: center;
  padding: 60px 0;
}

.list-group {
  margin-top: 8px;
}

.album-cell {
  padding: 12px 16px;
}

.album-row, .img-row {
  display: flex;
  align-items: center;
  width: 100%;
}

.album-cover {
  flex-shrink: 0;
  overflow: hidden;
}

.cover-placeholder {
  width: 56px;
  height: 56px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f2f3f5;
  border-radius: 6px;
}

.album-info, .img-info {
  flex: 1;
  margin-left: 12px;
  min-width: 0;
}

.album-name {
  font-size: 15px;
  font-weight: 500;
  color: #333;
}

.album-count {
  font-size: 12px;
  color: #999;
  margin-top: 4px;
}

.cell-actions {
  display: flex;
  gap: 4px;
  flex-shrink: 0;
}

.img-cell {
  padding: 10px 16px;
}

.img-remark {
  font-size: 14px;
  color: #333;
}

.delete-btn {
  height: 100%;
}

.dialog-form {
  padding: 16px;
}

.dialog-form :deep(.van-field) {
  margin-bottom: 8px;
}

.file-input {
  font-size: 13px;
}
</style>
