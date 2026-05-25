<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

const SIZE = 4
const STORAGE_KEY = 'wetalk_2048_best'

type Grid = number[][]

const grid = ref<Grid>(createEmpty())
const score = ref(0)
const bestScore = ref(Number(localStorage.getItem(STORAGE_KEY) || 0))
const gameOver = ref(false)
const won = ref(false)

function createEmpty(): Grid {
  return Array.from({ length: SIZE }, () => Array(SIZE).fill(0))
}

function clone(g: Grid): Grid {
  return g.map(row => [...row])
}

function addRandom(g: Grid): Grid {
  const empty: [number, number][] = []
  for (let r = 0; r < SIZE; r++)
    for (let c = 0; c < SIZE; c++)
      if (g[r][c] === 0) empty.push([r, c])
  if (empty.length === 0) return g
  const [r, c] = empty[Math.floor(Math.random() * empty.length)]
  g[r][c] = Math.random() < 0.9 ? 2 : 4
  return g
}

function init() {
  const g = createEmpty()
  addRandom(g)
  addRandom(g)
  grid.value = g
  score.value = 0
  gameOver.value = false
  won.value = false
}

function slideRow(row: number[]): { result: number[]; points: number } {
  const filtered = row.filter(v => v !== 0)
  const result: number[] = []
  let points = 0
  let i = 0
  while (i < filtered.length) {
    if (i + 1 < filtered.length && filtered[i] === filtered[i + 1]) {
      const merged = filtered[i] * 2
      result.push(merged)
      points += merged
      if (merged === 2048) won.value = true
      i += 2
    } else {
      result.push(filtered[i])
      i++
    }
  }
  while (result.length < SIZE) result.push(0)
  return { result, points }
}

function rotateLeft(g: Grid): Grid {
  const n = createEmpty()
  for (let r = 0; r < SIZE; r++)
    for (let c = 0; c < SIZE; c++)
      n[SIZE - 1 - c][r] = g[r][c]
  return n
}

function rotateRight(g: Grid): Grid {
  const n = createEmpty()
  for (let r = 0; r < SIZE; r++)
    for (let c = 0; c < SIZE; c++)
      n[c][SIZE - 1 - r] = g[r][c]
  return n
}

function move(direction: 'left' | 'right' | 'up' | 'down') {
  if (gameOver.value) return

  let g = clone(grid.value)

  if (direction === 'up') g = rotateLeft(g)
  else if (direction === 'right') { g = rotateLeft(g); g = rotateLeft(g) }
  else if (direction === 'down') g = rotateRight(g)

  let totalPoints = 0
  let changed = false
  for (let r = 0; r < SIZE; r++) {
    const { result, points } = slideRow(g[r])
    if (g[r].join(',') !== result.join(',')) changed = true
    g[r] = result
    totalPoints += points
  }

  if (direction === 'up') g = rotateRight(g)
  else if (direction === 'right') { g = rotateRight(g); g = rotateRight(g) }
  else if (direction === 'down') g = rotateLeft(g)

  if (!changed) return

  addRandom(g)
  grid.value = g
  score.value += totalPoints

  if (score.value > bestScore.value) {
    bestScore.value = score.value
    localStorage.setItem(STORAGE_KEY, String(bestScore.value))
  }

  if (!canMove(g)) {
    gameOver.value = true
  }
}

function canMove(g: Grid): boolean {
  for (let r = 0; r < SIZE; r++)
    for (let c = 0; c < SIZE; c++) {
      if (g[r][c] === 0) return true
      if (c + 1 < SIZE && g[r][c] === g[r][c + 1]) return true
      if (r + 1 < SIZE && g[r][c] === g[r + 1][c]) return true
    }
  return false
}

const tileColors: Record<number, { bg: string; color: string }> = {
  0: { bg: '#cdc1b4', color: 'transparent' },
  2: { bg: '#eee4da', color: '#776e65' },
  4: { bg: '#ede0c8', color: '#776e65' },
  8: { bg: '#f2b179', color: '#f9f6f2' },
  16: { bg: '#f59563', color: '#f9f6f2' },
  32: { bg: '#f67c5f', color: '#f9f6f2' },
  64: { bg: '#f65e3b', color: '#f9f6f2' },
  128: { bg: '#edcf72', color: '#f9f6f2' },
  256: { bg: '#edcc61', color: '#f9f6f2' },
  512: { bg: '#edc850', color: '#f9f6f2' },
  1024: { bg: '#edc53f', color: '#f9f6f2' },
  2048: { bg: '#edc22e', color: '#f9f6f2' },
}

function tileStyle(val: number) {
  const s = tileColors[val] || { bg: '#3c3a32', color: '#f9f6f2' }
  return { background: s.bg, color: s.color }
}

// Touch handling — only on the board
let touchStartX = 0
let touchStartY = 0

function onTouchStart(e: TouchEvent) {
  touchStartX = e.touches[0].clientX
  touchStartY = e.touches[0].clientY
}

function onTouchEnd(e: TouchEvent) {
  const dx = e.changedTouches[0].clientX - touchStartX
  const dy = e.changedTouches[0].clientY - touchStartY
  const absDx = Math.abs(dx)
  const absDy = Math.abs(dy)
  if (Math.max(absDx, absDy) < 30) return
  if (absDx > absDy) {
    move(dx > 0 ? 'right' : 'left')
  } else {
    move(dy > 0 ? 'down' : 'up')
  }
}

function onKeyDown(e: KeyboardEvent) {
  const map: Record<string, 'left' | 'right' | 'up' | 'down'> = {
    ArrowLeft: 'left', ArrowRight: 'right', ArrowUp: 'up', ArrowDown: 'down',
  }
  const dir = map[e.key]
  if (dir) {
    e.preventDefault()
    move(dir)
  }
}

onMounted(() => {
  init()
  window.addEventListener('keydown', onKeyDown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', onKeyDown)
})
</script>

<template>
  <div class="g2048-wrapper">
    <div class="g2048-header">
      <div class="g2048-scores">
        <div class="g2048-score-box">
          <div class="score-label">分数</div>
          <div class="score-value">{{ score }}</div>
        </div>
        <div class="g2048-score-box">
          <div class="score-label">最佳</div>
          <div class="score-value">{{ bestScore }}</div>
        </div>
      </div>
      <van-button size="small" icon="replay" round @click="init">重来</van-button>
    </div>

    <div
      class="g2048-board"
      @touchstart.prevent="onTouchStart"
      @touchend.prevent="onTouchEnd"
    >
      <div v-for="(row, r) in grid" :key="r" class="g2048-row">
        <div
          v-for="(val, c) in row"
          :key="c"
          class="g2048-tile"
          :style="tileStyle(val)"
        >
          <span v-if="val > 0" class="tile-num" :class="{ 'tile-large': val >= 1024 }">{{ val }}</span>
        </div>
      </div>
    </div>

    <!-- Game Over overlay -->
    <div v-if="gameOver" class="g2048-overlay" @touchstart.stop @touchend.stop @click.stop>
      <div class="overlay-content">
        <p class="overlay-title">游戏结束!</p>
        <p class="overlay-score">得分: {{ score }}</p>
        <van-button type="primary" round @click="init">再来一局</van-button>
      </div>
    </div>

    <!-- Won overlay -->
    <div v-if="won && !gameOver" class="g2048-overlay won" @touchstart.stop @touchend.stop @click.stop>
      <div class="overlay-content">
        <p class="overlay-title">恭喜! 达到 2048!</p>
        <van-button type="primary" round @click="won = false">继续游戏</van-button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.g2048-wrapper {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 12px;
  position: relative;
  user-select: none;
  -webkit-user-select: none;
}

.g2048-header {
  width: min(90vw, 400px);
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.g2048-scores {
  display: flex;
  gap: 8px;
}

.g2048-score-box {
  background: #bbada0;
  border-radius: 6px;
  padding: 6px 16px;
  text-align: center;
  min-width: 70px;
}

.score-label {
  font-size: 11px;
  color: #eee4da;
  font-weight: 600;
}

.score-value {
  font-size: 18px;
  font-weight: 700;
  color: #fff;
}

.g2048-board {
  width: min(90vw, 400px);
  height: min(90vw, 400px);
  background: #bbada0;
  border-radius: 8px;
  padding: 8px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  touch-action: none;
}

.g2048-row {
  flex: 1;
  display: flex;
  gap: 8px;
}

.g2048-tile {
  flex: 1;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  transition: background 0.12s;
}

.tile-num {
  font-size: 28px;
}

.tile-large {
  font-size: 20px;
}

.g2048-overlay {
  position: absolute;
  inset: 0;
  background: rgba(238, 228, 218, 0.73);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
  animation: fadeIn 0.3s;
}

.g2048-overlay.won {
  background: rgba(237, 194, 46, 0.5);
}

.overlay-content {
  text-align: center;
}

.overlay-title {
  font-size: 28px;
  font-weight: 700;
  color: #776e65;
  margin: 0 0 12px;
}

.overlay-score {
  font-size: 18px;
  color: #776e65;
  margin: 0 0 20px;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}
</style>
