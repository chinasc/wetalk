<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick } from 'vue'

const GRID = 20
const STORAGE_KEY = 'wetalk_snake_best'

interface Point { x: number; y: number }

const snake = ref<Point[]>([{ x: 10, y: 10 }])
const food = ref<Point>({ x: 15, y: 10 })
const direction = ref<'up' | 'down' | 'left' | 'right'>('right')
const nextDir = ref<'up' | 'down' | 'left' | 'right'>('right')
const score = ref(0)
const bestScore = ref(Number(localStorage.getItem(STORAGE_KEY) || 0))
const gameOver = ref(false)
const isRunning = ref(false)
const speed = ref(200)

const canvasRef = ref<HTMLCanvasElement | null>(null)
let ctx: CanvasRenderingContext2D | null = null
let cellSize = 0
let animFrameId = 0
let lastStepTime = 0
let accumulated = 0

function placeFood() {
  const occupied = new Set(snake.value.map(p => `${p.x},${p.y}`))
  let pos: Point
  do {
    pos = { x: Math.floor(Math.random() * GRID), y: Math.floor(Math.random() * GRID) }
  } while (occupied.has(`${pos.x},${pos.y}`))
  food.value = pos
}

function calcSize() {
  const maxW = Math.min(window.innerWidth * 0.9, 400)
  const maxH = window.innerHeight - 280
  cellSize = Math.floor(Math.min(maxW, maxH) / GRID)
}

function draw() {
  if (!ctx || !canvasRef.value) return
  const w = canvasRef.value.width
  const h = canvasRef.value.height

  ctx.fillStyle = '#1a1a2e'
  ctx.fillRect(0, 0, w, h)

  // Grid lines
  ctx.strokeStyle = 'rgba(255,255,255,0.03)'
  ctx.lineWidth = 0.5
  for (let i = 0; i <= GRID; i++) {
    ctx.beginPath()
    ctx.moveTo(i * cellSize, 0)
    ctx.lineTo(i * cellSize, GRID * cellSize)
    ctx.stroke()
    ctx.beginPath()
    ctx.moveTo(0, i * cellSize)
    ctx.lineTo(GRID * cellSize, i * cellSize)
    ctx.stroke()
  }

  // Food
  const fx = food.value.x * cellSize + cellSize / 2
  const fy = food.value.y * cellSize + cellSize / 2
  const fr = cellSize * 0.35
  ctx.fillStyle = '#ff5252'
  ctx.beginPath()
  ctx.arc(fx, fy, fr, 0, Math.PI * 2)
  ctx.fill()

  // Snake
  snake.value.forEach((p, i) => {
    const x = p.x * cellSize
    const y = p.y * cellSize
    const pad = 1
    const r = 3

    if (i === 0) {
      ctx.fillStyle = '#4caf50'
    } else {
      const ratio = 1 - (i / snake.value.length) * 0.4
      ctx.fillStyle = `rgb(${Math.floor(76 * ratio)}, ${Math.floor(175 * ratio)}, ${Math.floor(80 * ratio)})`
    }

    ctx.beginPath()
    ctx.roundRect(x + pad, y + pad, cellSize - pad * 2, cellSize - pad * 2, r)
    ctx.fill()
  })

  // Game over overlay on canvas
  if (gameOver.value) {
    ctx.fillStyle = 'rgba(0,0,0,0.5)'
    ctx.fillRect(0, 0, w, h)
  }
}

function step() {
  direction.value = nextDir.value

  const head = { ...snake.value[0] }
  switch (direction.value) {
    case 'up': head.y--; break
    case 'down': head.y++; break
    case 'left': head.x--; break
    case 'right': head.x++; break
  }

  // Wall collision
  if (head.x < 0 || head.x >= GRID || head.y < 0 || head.y >= GRID) {
    endGame()
    return
  }

  // Self collision
  if (snake.value.some(p => p.x === head.x && p.y === head.y)) {
    endGame()
    return
  }

  snake.value.unshift(head)

  // Food
  if (head.x === food.value.x && head.y === food.value.y) {
    score.value++
    speed.value = Math.max(80, 200 - score.value * 5)
    if (score.value > bestScore.value) {
      bestScore.value = score.value
      localStorage.setItem(STORAGE_KEY, String(bestScore.value))
    }
    placeFood()
  } else {
    snake.value.pop()
  }
}

function endGame() {
  gameOver.value = true
  isRunning.value = false
}

function gameLoop(timestamp: number) {
  if (!isRunning.value) {
    draw()
    return
  }

  if (!lastStepTime) lastStepTime = timestamp
  accumulated += timestamp - lastStepTime
  lastStepTime = timestamp

  while (accumulated >= speed.value) {
    step()
    accumulated -= speed.value
    if (gameOver.value) break
  }

  draw()
  if (!gameOver.value) {
    animFrameId = requestAnimationFrame(gameLoop)
  }
}

function startGame() {
  if (gameOver.value) return
  isRunning.value = true
  lastStepTime = 0
  accumulated = 0
  animFrameId = requestAnimationFrame(gameLoop)
}

function pauseGame() {
  isRunning.value = false
  if (animFrameId) cancelAnimationFrame(animFrameId)
}

function toggleGame() {
  if (gameOver.value) return
  if (isRunning.value) pauseGame()
  else startGame()
}

function setDirection(dir: 'up' | 'down' | 'left' | 'right') {
  const opposites: Record<string, string> = { up: 'down', down: 'up', left: 'right', right: 'left' }
  if (dir !== opposites[direction.value]) {
    nextDir.value = dir
  }
  if (!isRunning.value && !gameOver.value) startGame()
}

function init() {
  snake.value = [{ x: 10, y: 10 }]
  direction.value = 'right'
  nextDir.value = 'right'
  score.value = 0
  speed.value = 200
  gameOver.value = false
  isRunning.value = false
  lastStepTime = 0
  accumulated = 0
  if (animFrameId) cancelAnimationFrame(animFrameId)
  placeFood()
  nextTick(() => {
    setupCanvas()
    draw()
  })
}

function setupCanvas() {
  if (!canvasRef.value) return
  calcSize()
  canvasRef.value.width = GRID * cellSize
  canvasRef.value.height = GRID * cellSize
  ctx = canvasRef.value.getContext('2d')
}

// Touch swipe on canvas
let touchStartX = 0
let touchStartY = 0

function onTouchStart(e: TouchEvent) {
  touchStartX = e.touches[0].clientX
  touchStartY = e.touches[0].clientY
}

function onTouchEnd(e: TouchEvent) {
  const dx = e.changedTouches[0].clientX - touchStartX
  const dy = e.changedTouches[0].clientY - touchStartY
  if (Math.abs(dx) < 20 && Math.abs(dy) < 20) {
    toggleGame()
    return
  }
  if (Math.abs(dx) > Math.abs(dy)) {
    setDirection(dx > 0 ? 'right' : 'left')
  } else {
    setDirection(dy > 0 ? 'down' : 'up')
  }
}

function onResize() {
  setupCanvas()
  draw()
}

onMounted(() => {
  init()
  window.addEventListener('resize', onResize)
})

onUnmounted(() => {
  if (animFrameId) cancelAnimationFrame(animFrameId)
  window.removeEventListener('resize', onResize)
})
</script>

<template>
  <div class="snake-wrapper">
    <div class="snake-header">
      <div class="snake-scores">
        <div class="score-box">
          <div class="score-label">得分</div>
          <div class="score-value">{{ score }}</div>
        </div>
        <div class="score-box">
          <div class="score-label">最佳</div>
          <div class="score-value">{{ bestScore }}</div>
        </div>
      </div>
      <van-button size="small" icon="replay" round @click="init">重来</van-button>
    </div>

    <div class="snake-canvas-wrap">
      <canvas
        ref="canvasRef"
        @touchstart.prevent="onTouchStart"
        @touchend.prevent="onTouchEnd"
      />
      <!-- Start overlay -->
      <div v-if="!isRunning && !gameOver" class="snake-overlay" @touchstart.stop @click.stop="startGame">
        <p>{{ score > 0 ? '继续' : '点击开始' }}</p>
      </div>
      <!-- Game Over overlay -->
      <div v-if="gameOver" class="snake-overlay" @touchstart.stop @click.stop>
        <p class="snake-over-title">游戏结束</p>
        <p class="snake-over-score">得分: {{ score }}</p>
        <van-button type="primary" round size="small" @click="init">再来一局</van-button>
      </div>
    </div>

    <!-- D-Pad -->
    <div class="snake-dpad">
      <div class="dpad-row">
        <div class="dpad-spacer"></div>
        <button class="dpad-btn" @click="setDirection('up')">
          <van-icon name="arrow-up" size="22" />
        </button>
        <div class="dpad-spacer"></div>
      </div>
      <div class="dpad-row">
        <button class="dpad-btn" @click="setDirection('left')">
          <van-icon name="arrow-left" size="22" />
        </button>
        <div class="dpad-center"></div>
        <button class="dpad-btn" @click="setDirection('right')">
          <van-icon name="arrow" size="22" />
        </button>
      </div>
      <div class="dpad-row">
        <div class="dpad-spacer"></div>
        <button class="dpad-btn" @click="setDirection('down')">
          <van-icon name="arrow-down" size="22" />
        </button>
        <div class="dpad-spacer"></div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.snake-wrapper {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 12px;
  position: relative;
}

.snake-header {
  width: min(90vw, 400px);
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.snake-scores {
  display: flex;
  gap: 8px;
}

.score-box {
  background: #1a1a2e;
  border-radius: 6px;
  padding: 4px 14px;
  text-align: center;
  min-width: 65px;
}

.score-label {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.6);
}

.score-value {
  font-size: 18px;
  font-weight: 700;
  color: #4caf50;
}

.snake-canvas-wrap {
  position: relative;
  border-radius: 8px;
  overflow: hidden;
  border: 2px solid #1a1a2e;
}

.snake-canvas-wrap canvas {
  display: block;
}

.snake-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #fff;
  cursor: pointer;
}

.snake-overlay p {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
}

.snake-over-title {
  font-size: 24px !important;
  margin-bottom: 8px !important;
}

.snake-over-score {
  margin-bottom: 16px !important;
  font-size: 16px !important;
  color: rgba(255, 255, 255, 0.8) !important;
}

.snake-dpad {
  margin-top: 16px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.dpad-row {
  display: flex;
  gap: 4px;
}

.dpad-btn {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  border: none;
  background: #e8e8e8;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #323233;
  transition: background 0.1s;
}

.dpad-btn:active {
  background: #c8c8c8;
}

.dpad-spacer {
  width: 56px;
  height: 56px;
}

.dpad-center {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  background: #f5f5f5;
}
</style>
