<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useGameStore } from '../stores/game'
import { gameRegistry } from '../games'

const route = useRoute()
const router = useRouter()
const gameStore = useGameStore()
const gameId = Number(route.params.id)

const game = computed(() => gameStore.games.find(g => g.id === gameId))
const gameComponent = computed(() => {
  const slug = game.value?.url
  if (!slug) return null
  return gameRegistry[slug] || null
})

onMounted(async () => {
  if (gameStore.games.length === 0) {
    await gameStore.fetchGames()
  }
})

function goBack() {
  router.back()
}
</script>

<template>
  <div class="game-page">
    <van-nav-bar
      :title="game?.name || '游戏'"
      left-arrow
      @click-left="goBack"
      :border="false"
      class="game-nav"
    />

    <div class="game-container" v-if="game">
      <component v-if="gameComponent" :is="gameComponent" />
      <div v-else class="game-placeholder">
        <van-icon name="games-o" size="80" color="#dcdee0" />
        <p class="placeholder-title">{{ game.name }}</p>
        <p class="placeholder-desc">{{ game.description }}</p>
        <van-button type="primary" disabled>即将开放</van-button>
      </div>
    </div>

    <div v-else class="game-placeholder">
      <van-loading size="36px" vertical>加载中...</van-loading>
    </div>
  </div>
</template>

<style scoped>
.game-page {
  height: 100vh;
  height: 100dvh;
  display: flex;
  flex-direction: column;
  background: #f7f8fa;
}

.game-container {
  flex: 1;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  position: relative;
}

.game-nav {
  position: relative;
  z-index: 100;
  flex-shrink: 0;
}

.game-placeholder {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 16px;
  padding: 40px;
}

.placeholder-title {
  font-size: 20px;
  font-weight: 600;
  color: #323233;
  margin: 0;
}

.placeholder-desc {
  font-size: 14px;
  color: #969799;
  text-align: center;
  margin: 0;
}
</style>
