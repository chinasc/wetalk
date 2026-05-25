<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { showToast, showLoadingToast, closeToast } from 'vant'
import { getAlbums, getAlbumDetail } from '../api'

const router = useRouter()

const albums = ref<any[]>([])
const loading = ref(false)

onMounted(async () => {
  loading.value = true
  try {
    albums.value = await getAlbums()
  } catch {
    showToast('获取相册列表失败')
  } finally {
    loading.value = false
  }
})

function selectAlbum(album: any) {
  router.push({
    path: '/album-send',
    query: {
      album_id: String(album.id),
      album_title: album.name || album.title,
      album_cover: album.cover,
    },
  })
}

function goBack() {
  router.back()
}
</script>

<template>
  <div class="page">
    <van-nav-bar title="相册集" left-arrow @click-left="goBack" :border="false" />

    <div class="page-content" style="padding: 12px 16px;">
      <van-loading v-if="loading" class="page-loading" size="36px" vertical>加载中...</van-loading>

      <van-empty v-else-if="albums.length === 0" description="暂无相册集" />

      <div v-else class="album-list">
        <div
          v-for="album in albums"
          :key="album.id"
          class="album-card"
          @click="selectAlbum(album)"
        >
          <van-image
            :src="album.cover"
            width="80"
            height="80"
            fit="cover"
            radius="8"
            class="album-cover"
          >
            <template #error>
              <div class="album-cover-placeholder">
                <van-icon name="photo-o" size="32" color="#969799" />
              </div>
            </template>
          </van-image>
          <div class="album-info">
            <div class="album-title">{{ album.name || album.title }}</div>
            <div class="album-desc">{{ album.images_count || album.photo_count || 0 }} 张照片</div>
          </div>
          <van-icon name="arrow" color="#c8c9cc" />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.page-loading {
  display: flex;
  justify-content: center;
  padding: 60px 0;
}

.album-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.album-card {
  display: flex;
  align-items: center;
  padding: 14px;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
  cursor: pointer;
}

.album-card:active {
  background: #f9f9f9;
}

.album-cover {
  flex-shrink: 0;
  overflow: hidden;
}

.album-cover-placeholder {
  width: 80px;
  height: 80px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f2f3f5;
  border-radius: 8px;
}

.album-info {
  flex: 1;
  margin-left: 14px;
  min-width: 0;
}

.album-title {
  font-size: 15px;
  font-weight: 500;
  color: #323233;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.album-desc {
  font-size: 13px;
  color: #969799;
  margin-top: 4px;
}
</style>
