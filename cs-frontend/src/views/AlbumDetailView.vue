<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { showToast, showLoadingToast, closeToast } from 'vant'
import { getAlbumDetail } from '../api'

const route = useRoute()
const router = useRouter()
const albumId = Number(route.params.id)

const images = ref<any[]>([])
const title = ref('')
const currentIndex = ref(0)
const loading = ref(true)

const currentImage = computed(() => images.value[currentIndex.value])
const currentRemark = computed(() => currentImage.value?.remark || '')

onMounted(async () => {
  showLoadingToast({ message: '加载中...', forbidClick: true, duration: 0 })
  try {
    const data = await getAlbumDetail(albumId)
    title.value = data.name || data.title || '相册'
    images.value = data.images || data.photos || []
    closeToast()
  } catch {
    closeToast()
    showToast('加载相册失败')
  } finally {
    loading.value = false
  }
})

function prevImage() {
  if (currentIndex.value > 0) {
    currentIndex.value--
  }
}

function nextImage() {
  if (currentIndex.value < images.value.length - 1) {
    currentIndex.value++
  }
}

function selectImage(index: number) {
  currentIndex.value = index
}

// Touch swipe
let touchStartX = 0
let touchStartY = 0

function onTouchStart(e: TouchEvent) {
  touchStartX = e.touches[0].clientX
  touchStartY = e.touches[0].clientY
}

function onTouchEnd(e: TouchEvent) {
  const dx = e.changedTouches[0].clientX - touchStartX
  const dy = e.changedTouches[0].clientY - touchStartY
  if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 50) {
    if (dx < 0) nextImage()
    else prevImage()
  }
}

function goBack() {
  router.back()
}
</script>

<template>
  <div class="album-page">
    <van-nav-bar
      :title="title || '相册'"
      left-arrow
      @click-left="goBack"
      :border="false"
      class="album-nav"
    />

    <van-loading v-if="loading" class="album-loading" size="36px" vertical>
      加载中...
    </van-loading>

    <template v-else>
      <div
        class="album-main"
        @touchstart="onTouchStart"
        @touchend="onTouchEnd"
      >
        <div class="album-counter">
          {{ currentIndex + 1 }} / {{ images.length }}
        </div>
        <div class="album-image-wrap" v-if="currentImage">
          <img
            :src="currentImage.url || currentImage.image_path"
            :alt="currentImage.remark || currentImage.caption"
            class="album-image"
          />
        </div>
        <div class="album-nav-arrows" v-if="images.length > 1">
          <button
            class="album-arrow album-arrow-left"
            :disabled="currentIndex === 0"
            @click="prevImage"
          >
            <van-icon name="arrow-left" />
          </button>
          <button
            class="album-arrow album-arrow-right"
            :disabled="currentIndex === images.length - 1"
            @click="nextImage"
          >
            <van-icon name="arrow" />
          </button>
        </div>
      </div>

      <div class="album-remark" v-if="currentRemark">
        <p>{{ currentRemark }}</p>
      </div>

      <div class="album-thumbs" v-if="images.length > 1">
        <div class="album-thumbs-scroll hide-scrollbar">
          <div
            v-for="(img, idx) in images"
            :key="img.id"
            :class="['album-thumb', { active: idx === currentIndex }]"
            @click="selectImage(idx)"
          >
            <img :src="img.url || img.image_path" :alt="img.remark || img.caption" />
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.album-page {
  height: 100vh;
  height: 100dvh;
  display: flex;
  flex-direction: column;
  background: #000;
  color: #fff;
}

.album-nav {
  background: rgba(0, 0, 0, 0.8);
}

.album-nav :deep(.van-nav-bar__title) {
  color: #fff;
}

.album-nav :deep(.van-icon) {
  color: #fff;
}

.album-loading {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}

.album-main {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
  user-select: none;
  -webkit-user-select: none;
}

.album-counter {
  position: absolute;
  top: 12px;
  right: 16px;
  font-size: 13px;
  background: rgba(0, 0, 0, 0.5);
  padding: 4px 10px;
  border-radius: 12px;
  z-index: 2;
}

.album-image-wrap {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 8px;
}

.album-image {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}

.album-nav-arrows {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  pointer-events: none;
  padding: 0 8px;
}

.album-arrow {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  border: none;
  background: rgba(255, 255, 255, 0.2);
  color: #fff;
  font-size: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: auto;
  cursor: pointer;
}

.album-arrow:disabled {
  opacity: 0.3;
}

.album-remark {
  padding: 12px 16px;
  background: rgba(0, 0, 0, 0.6);
}

.album-remark p {
  margin: 0;
  font-size: 14px;
  line-height: 1.5;
  color: rgba(255, 255, 255, 0.9);
}

.album-thumbs {
  padding: 8px 0;
  padding-bottom: calc(8px + env(safe-area-inset-bottom, 0px));
  background: rgba(0, 0, 0, 0.8);
}

.album-thumbs-scroll {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  padding: 0 12px;
}

.album-thumb {
  width: 56px;
  height: 56px;
  border-radius: 6px;
  overflow: hidden;
  flex-shrink: 0;
  border: 2px solid transparent;
  cursor: pointer;
  transition: border-color 0.2s;
}

.album-thumb.active {
  border-color: #1989fa;
}

.album-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
</style>
