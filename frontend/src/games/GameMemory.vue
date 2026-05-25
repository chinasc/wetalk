<script setup lang="ts">
import { ref, computed, onUnmounted } from 'vue'

const STORAGE_KEY = 'wetalk_memory_best'
const EMOJIS = ['🐶', '🐱', '🐸', '🦊', '🐻', '🐼', '🐨', '🦁']

interface Card {
  id: number
  emoji: string
  flipped: boolean
  matched: boolean
}

const cards = ref<Card[]>([])
const flippedIndices = ref<number[]>([])
const moves = ref(0)
const elapsed = ref(0)
const gameComplete = ref(false)
const isLocked = ref(false)
let timer: ReturnType<typeof setInterval> | null = null
let started = false

function shuffle<T>(arr: T[]): T[] {
  const a = [...arr]
  for (let i = a.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [a[i], a[j]] = [a[j], a[i]]
  }
  return a
}

function init() {
  const pairs = [...EMOJIS, ...EMOJIS]
  const shuffled = shuffle(pairs)
  cards.value = shuffled.map((emoji, i) => ({
    id: i,
    emoji,
    flipped: false,
    matched: false,
  }))
  flippedIndices.value = []
  moves.value = 0
  elapsed.value = 0
  gameComplete.value = false
  isLocked.value = false
  started = false
  if (timer) { clearInterval(timer); timer = null }
}

function restart() {
  init()
  bestMoves.value = Number(localStorage.getItem(STORAGE_KEY) || 0)
}

function startTimer() {
  if (started) return
  started = true
  timer = setInterval(() => { elapsed.value++ }, 1000)
}

function flipCard(index: number) {
  if (isLocked.value) return
  const card = cards.value[index]
  if (card.flipped || card.matched) return

  startTimer()
  card.flipped = true
  flippedIndices.value.push(index)

  if (flippedIndices.value.length === 2) {
    isLocked.value = true
    moves.value++
    const [i1, i2] = flippedIndices.value
    const c1 = cards.value[i1]
    const c2 = cards.value[i2]

    if (c1.emoji === c2.emoji) {
      c1.matched = true
      c2.matched = true
      flippedIndices.value = []
      isLocked.value = false
      if (cards.value.every(c => c.matched)) {
        gameComplete.value = true
        if (timer) { clearInterval(timer); timer = null }
        const best = Number(localStorage.getItem(STORAGE_KEY) || 0)
        if (moves.value < best || best === 0) {
          localStorage.setItem(STORAGE_KEY, String(moves.value))
        }
      }
    } else {
      setTimeout(() => {
        c1.flipped = false
        c2.flipped = false
        flippedIndices.value = []
        isLocked.value = false
      }, 800)
    }
  }
}

const bestMoves = ref(Number(localStorage.getItem(STORAGE_KEY) || 0))
const formattedTime = computed(() => {
  const m = Math.floor(elapsed.value / 60)
  const s = elapsed.value % 60
  return `${m}:${s.toString().padStart(2, '0')}`
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
})

init()
</script>

<template>
  <div class="memory-wrapper">
    <div class="memory-header">
      <div class="memory-stats">
        <div class="stat-box">
          <div class="stat-label">步数</div>
          <div class="stat-value">{{ moves }}</div>
        </div>
        <div class="stat-box">
          <div class="stat-label">时间</div>
          <div class="stat-value">{{ formattedTime }}</div>
        </div>
        <div class="stat-box">
          <div class="stat-label">最佳</div>
          <div class="stat-value">{{ bestMoves || '-' }}</div>
        </div>
      </div>
      <van-button size="small" icon="replay" round @click="restart">重来</van-button>
    </div>

    <div class="memory-board">
      <div
        v-for="(card, idx) in cards"
        :key="card.id"
        class="memory-card"
        :class="{ flipped: card.flipped || card.matched, matched: card.matched }"
        @click="flipCard(idx)"
      >
        <div class="card-inner">
          <div class="card-front">?</div>
          <div class="card-back">{{ card.emoji }}</div>
        </div>
      </div>
    </div>

    <!-- Win overlay -->
    <div v-if="gameComplete" class="memory-overlay" @touchstart.stop @click.stop>
      <div class="overlay-content">
        <p class="overlay-title">恭喜通关!</p>
        <p class="overlay-info">{{ moves }} 步 / {{ formattedTime }}</p>
        <van-button type="primary" round @click="restart">再来一局</van-button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.memory-wrapper {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 12px;
  position: relative;
}

.memory-header {
  width: min(90vw, 400px);
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.memory-stats {
  display: flex;
  gap: 8px;
}

.stat-box {
  background: #1989fa;
  border-radius: 6px;
  padding: 4px 12px;
  text-align: center;
  min-width: 60px;
}

.stat-label {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.8);
}

.stat-value {
  font-size: 16px;
  font-weight: 700;
  color: #fff;
}

.memory-board {
  width: min(90vw, 400px);
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
}

.memory-card {
  aspect-ratio: 1;
  perspective: 600px;
  cursor: pointer;
}

.card-inner {
  width: 100%;
  height: 100%;
  position: relative;
  transform-style: preserve-3d;
  transition: transform 0.4s ease;
}

.memory-card.flipped .card-inner {
  transform: rotateY(180deg);
}

.card-front,
.card-back {
  position: absolute;
  inset: 0;
  backface-visibility: hidden;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  font-weight: 700;
}

.card-front {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: #fff;
  font-size: 24px;
}

.card-back {
  background: #fff;
  transform: rotateY(180deg);
  border: 2px solid #e8e8e8;
  font-size: 36px;
}

.memory-card.matched .card-back {
  border-color: #07c160;
  background: #f0fff4;
}

.memory-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
  animation: fadeIn 0.3s;
}

.overlay-content {
  text-align: center;
}

.overlay-title {
  font-size: 28px;
  font-weight: 700;
  color: #fff;
  margin: 0 0 8px;
}

.overlay-info {
  font-size: 18px;
  color: rgba(255, 255, 255, 0.8);
  margin: 0 0 20px;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}
</style>
