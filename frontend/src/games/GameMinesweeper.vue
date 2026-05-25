<script setup lang="ts">
import { ref, computed, onUnmounted } from 'vue'

const ROWS = 9
const COLS = 9
const MINE_COUNT = 10
const STORAGE_KEY = 'wetalk_minesweeper_best'

interface Cell {
  mine: boolean
  revealed: boolean
  flagged: boolean
  adjacent: number
}

const cells = ref<Cell[]>([])
const gameOver = ref(false)
const gameWon = ref(false)
const firstClick = ref(true)
const timer = ref(0)
const flagMode = ref(false)
let timerInterval: ReturnType<typeof setInterval> | null = null
let started = false

const flagCount = computed(() => cells.value.filter(c => c.flagged).length)
const bestTime = ref(Number(localStorage.getItem(STORAGE_KEY) || 0))

function createCells(): Cell[] {
  return Array.from({ length: ROWS * COLS }, () => ({
    mine: false,
    revealed: false,
    flagged: false,
    adjacent: 0,
  }))
}

function idx(r: number, c: number) {
  return r * COLS + c
}

function neighbors(r: number, c: number): [number, number][] {
  const result: [number, number][] = []
  for (let dr = -1; dr <= 1; dr++)
    for (let dc = -1; dc <= 1; dc++) {
      if (dr === 0 && dc === 0) continue
      const nr = r + dr, nc = c + dc
      if (nr >= 0 && nr < ROWS && nc >= 0 && nc < COLS)
        result.push([nr, nc])
    }
  return result
}

function placeMines(safeR: number, safeC: number) {
  const safeSet = new Set<string>()
  safeSet.add(`${safeR},${safeC}`)
  for (const [nr, nc] of neighbors(safeR, safeC)) {
    safeSet.add(`${nr},${nc}`)
  }

  let placed = 0
  while (placed < MINE_COUNT) {
    const r = Math.floor(Math.random() * ROWS)
    const c = Math.floor(Math.random() * COLS)
    if (safeSet.has(`${r},${c}`)) continue
    const i = idx(r, c)
    if (cells.value[i].mine) continue
    cells.value[i].mine = true
    placed++
  }

  for (let r = 0; r < ROWS; r++)
    for (let c = 0; c < COLS; c++) {
      const i = idx(r, c)
      if (cells.value[i].mine) continue
      let count = 0
      for (const [nr, nc] of neighbors(r, c))
        if (cells.value[idx(nr, nc)].mine) count++
      cells.value[i].adjacent = count
    }
}

function startTimer() {
  if (started) return
  started = true
  timerInterval = setInterval(() => timer.value++, 1000)
}

function reveal(r: number, c: number) {
  if (gameOver.value || gameWon.value) return
  const i = idx(r, c)
  const cell = cells.value[i]
  if (cell.revealed || cell.flagged) return

  if (firstClick.value) {
    firstClick.value = false
    placeMines(r, c)
    startTimer()
  }

  cell.revealed = true

  if (cell.mine) {
    gameOver.value = true
    if (timerInterval) { clearInterval(timerInterval); timerInterval = null }
    cells.value.forEach(c => { if (c.mine) c.revealed = true })
    return
  }

  if (cell.adjacent === 0) {
    const queue: [number, number][] = neighbors(r, c)
    while (queue.length) {
      const [nr, nc] = queue.shift()!
      const ni = idx(nr, nc)
      const nCell = cells.value[ni]
      if (nCell.revealed || nCell.mine || nCell.flagged) continue
      nCell.revealed = true
      if (nCell.adjacent === 0) {
        for (const [nnr, nnc] of neighbors(nr, nc)) {
          if (!cells.value[idx(nnr, nnc)].revealed)
            queue.push([nnr, nnc])
        }
      }
    }
  }

  checkWin()
}

function toggleFlag(r: number, c: number) {
  if (gameOver.value || gameWon.value) return
  const cell = cells.value[idx(r, c)]
  if (cell.revealed) return
  cell.flagged = !cell.flagged
}

function handleCell(r: number, c: number) {
  if (flagMode.value) {
    toggleFlag(r, c)
  } else {
    reveal(r, c)
  }
}

function checkWin() {
  const unrevealed = cells.value.filter(c => !c.revealed)
  if (unrevealed.length === MINE_COUNT && unrevealed.every(c => c.mine)) {
    gameWon.value = true
    if (timerInterval) { clearInterval(timerInterval); timerInterval = null }
    cells.value.forEach(c => { if (c.mine) c.flagged = true })
    const best = bestTime.value
    if (best === 0 || timer.value < best) {
      bestTime.value = timer.value
      localStorage.setItem(STORAGE_KEY, String(timer.value))
    }
  }
}

function init() {
  cells.value = createCells()
  gameOver.value = false
  gameWon.value = false
  firstClick.value = true
  timer.value = 0
  started = false
  flagMode.value = false
  if (timerInterval) { clearInterval(timerInterval); timerInterval = null }
  bestTime.value = Number(localStorage.getItem(STORAGE_KEY) || 0)
}

const numColors: Record<number, string> = {
  1: '#1976d2', 2: '#388e3c', 3: '#d32f2f', 4: '#1a237e',
  5: '#880e4f', 6: '#00838f', 7: '#424242', 8: '#9e9e9e',
}

function numColor(n: number) {
  return numColors[n] || '#424242'
}

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval)
})

init()
</script>

<template>
  <div class="mine-wrapper" @contextmenu.prevent>
    <div class="mine-header">
      <div class="mine-stats">
        <div class="stat-box">
          <span>💣 {{ MINE_COUNT - flagCount }}</span>
        </div>
        <div class="stat-box">
          <span>⏱ {{ timer }}s</span>
        </div>
        <div class="stat-box">
          <span>🏆 {{ bestTime ? bestTime + 's' : '-' }}</span>
        </div>
      </div>
      <van-button size="small" icon="replay" round @click="init">重来</van-button>
    </div>

    <div class="mine-toolbar">
      <van-button
        size="small"
        :type="flagMode ? 'danger' : 'default'"
        round
        @click="flagMode = !flagMode"
      >
        {{ flagMode ? '🚩 标旗模式' : '👆 翻开模式' }}
      </van-button>
    </div>

    <div class="mine-board">
      <div
        v-for="(_, r) in ROWS"
        :key="r"
        class="mine-row"
      >
        <div
          v-for="(_, c) in COLS"
          :key="c"
          class="mine-cell"
          :class="{
            revealed: cells[idx(r,c)].revealed,
            'is-mine': cells[idx(r,c)].revealed && cells[idx(r,c)].mine,
            boom: gameOver && cells[idx(r,c)].revealed && cells[idx(r,c)].mine,
          }"
          @click="handleCell(r, c)"
        >
          <template v-if="cells[idx(r,c)].revealed">
            <span v-if="cells[idx(r,c)].mine" class="mine-icon">💣</span>
            <span v-else-if="cells[idx(r,c)].adjacent > 0" class="mine-num" :style="{ color: numColor(cells[idx(r,c)].adjacent) }">
              {{ cells[idx(r,c)].adjacent }}
            </span>
          </template>
          <span v-else-if="cells[idx(r,c)].flagged" class="mine-flag">🚩</span>
        </div>
      </div>
    </div>

    <p class="mine-hint">点击翻开 · 切换标旗模式插旗</p>

    <!-- Game Over / Win overlay -->
    <div v-if="gameOver || gameWon" class="mine-overlay" @touchstart.stop @click.stop>
      <div class="overlay-content">
        <p class="overlay-title">{{ gameWon ? '🎉 胜利!' : '💥 游戏结束' }}</p>
        <p class="overlay-info">{{ gameWon ? `用时 ${timer} 秒` : '踩到地雷了' }}</p>
        <van-button type="primary" round @click="init">再来一局</van-button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.mine-wrapper {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 12px;
  position: relative;
}

.mine-header {
  width: min(92vw, 400px);
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.mine-stats {
  display: flex;
  gap: 6px;
}

.stat-box {
  background: #323233;
  border-radius: 6px;
  padding: 6px 10px;
  font-size: 13px;
  color: #fff;
  font-weight: 600;
}

.mine-toolbar {
  width: min(92vw, 400px);
  margin-bottom: 8px;
}

.mine-board {
  width: min(92vw, 400px);
  display: flex;
  flex-direction: column;
  gap: 2px;
  background: #bdbdbd;
  padding: 2px;
  border-radius: 4px;
}

.mine-row {
  display: flex;
  gap: 2px;
}

.mine-cell {
  flex: 1;
  aspect-ratio: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #c0c0c0;
  box-shadow: inset -1.5px -1.5px 0 #808080, inset 1.5px 1.5px 0 #fff;
  cursor: pointer;
  font-size: 16px;
  user-select: none;
  -webkit-user-select: none;
  -webkit-tap-highlight-color: transparent;
}

.mine-cell.revealed {
  background: #e0e0e0;
  box-shadow: inset 0.5px 0.5px 0 #bbb;
}

.mine-cell.boom {
  background: #ff5252;
}

.mine-num {
  font-size: 15px;
  font-weight: 700;
}

.mine-icon {
  font-size: 14px;
}

.mine-flag {
  font-size: 14px;
}

.mine-hint {
  margin-top: 10px;
  font-size: 12px;
  color: #999;
}

.mine-overlay {
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
