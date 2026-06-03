const SOUND_KEY = 'cs_sound_enabled'

// ---- Sound synthesis parameters ----
const FREQ1 = 880
const FREQ2 = 1320
const DURATION = 0.4
const SAMPLE_RATE = 44100

function generateWavSamples(): Float32Array {
  const len = Math.floor(SAMPLE_RATE * DURATION)
  const samples = new Float32Array(len)
  for (let i = 0; i < len; i++) {
    const t = i / SAMPLE_RATE
    const env = Math.exp(-t * 10)
    samples[i] = (Math.sin(2 * Math.PI * FREQ1 * t) * 0.5 + Math.sin(2 * Math.PI * FREQ2 * t) * 0.5) * env
  }
  return samples
}

function createWavBlob(): Blob {
  const samples = generateWavSamples()
  const numSamples = samples.length
  const buffer = new ArrayBuffer(44 + numSamples * 2)
  const view = new DataView(buffer)

  function writeStr(offset: number, str: string) {
    for (let i = 0; i < str.length; i++) view.setUint8(offset + i, str.charCodeAt(i))
  }

  writeStr(0, 'RIFF')
  view.setUint32(4, 36 + numSamples * 2, true)
  writeStr(8, 'WAVE')
  writeStr(12, 'fmt ')
  view.setUint32(16, 16, true)
  view.setUint16(20, 1, true)
  view.setUint16(22, 1, true)
  view.setUint32(24, SAMPLE_RATE, true)
  view.setUint32(28, SAMPLE_RATE * 2, true)
  view.setUint16(32, 2, true)
  view.setUint16(34, 16, true)
  writeStr(36, 'data')
  view.setUint32(40, numSamples * 2, true)

  for (let i = 0; i < numSamples; i++) {
    view.setInt16(44 + i * 2, Math.max(-32768, Math.min(32767, samples[i] * 32767)), true)
  }

  return new Blob([buffer], { type: 'audio/wav' })
}

// ---- Web Audio API (primary) ----
let audioCtx: AudioContext | null = null
let audioBuffer: AudioBuffer | null = null

function getAudioContext(): AudioContext | null {
  if (audioCtx) return audioCtx
  try {
    const AC = window.AudioContext || (window as any).webkitAudioContext
    if (!AC) return null
    audioCtx = new AC()
    return audioCtx
  } catch {
    return null
  }
}

async function ensureAudioBuffer(): Promise<AudioBuffer | null> {
  if (audioBuffer) return audioBuffer
  const ctx = getAudioContext()
  if (!ctx) return null
  try {
    const samples = generateWavSamples()
    const buf = ctx.createBuffer(1, samples.length, SAMPLE_RATE)
    buf.getChannelData(0).set(samples)
    audioBuffer = buf
    return buf
  } catch {
    return null
  }
}

async function playWithWebAudio(): Promise<boolean> {
  const ctx = getAudioContext()
  if (!ctx) return false
  try {
    if (ctx.state === 'suspended') {
      await ctx.resume()
    }
    const buf = await ensureAudioBuffer()
    if (!buf) return false
    const source = ctx.createBufferSource()
    source.buffer = buf
    const gain = ctx.createGain()
    gain.gain.value = 1.0
    source.connect(gain)
    gain.connect(ctx.destination)
    source.start(0)
    return true
  } catch {
    return false
  }
}

// ---- HTML5 Audio (fallback) ----
let htmlAudio: HTMLAudioElement | null = null

function getHtmlAudio(): HTMLAudioElement {
  if (htmlAudio) return htmlAudio
  const blob = createWavBlob()
  const url = URL.createObjectURL(blob)
  htmlAudio = new Audio()
  htmlAudio.src = url
  htmlAudio.volume = 1.0
  htmlAudio.preload = 'auto'
  return htmlAudio
}

async function playWithHtmlAudio(): Promise<boolean> {
  try {
    const audio = getHtmlAudio()
    audio.pause()
    audio.currentTime = 0
    await audio.play()
    return true
  } catch {
    return false
  }
}

// ---- Unlock on user gesture ----
let unlocked = false

function unlock() {
  if (unlocked) return
  unlocked = true
  // Resume AudioContext synchronously in gesture handler (critical for iOS)
  const ctx = getAudioContext()
  if (ctx && ctx.state === 'suspended') {
    ctx.resume().catch(() => {})
  }
  // Preload and unlock HTML5 Audio
  try {
    const audio = getHtmlAudio()
    audio.play().then(() => {
      audio.pause()
      audio.currentTime = 0
    }).catch(() => {})
  } catch { /* ignore */ }
  // Pre-generate audio buffer
  ensureAudioBuffer()
  // Remove all listeners
  const events = ['click', 'touchstart', 'touchend', 'keydown', 'pointerdown']
  events.forEach(e => document.removeEventListener(e, unlock))
}

;['click', 'touchstart', 'touchend', 'keydown', 'pointerdown'].forEach(e => {
  document.addEventListener(e, unlock, { passive: true })
})

// ---- Public API ----

export function isSoundEnabled(): boolean {
  return localStorage.getItem(SOUND_KEY) !== 'false'
}

export function setSoundEnabled(enabled: boolean): void {
  localStorage.setItem(SOUND_KEY, String(enabled))
}

export async function playNotification(): Promise<void> {
  if (!isSoundEnabled()) return
  // Always try Web Audio first, fall back to HTML5 Audio
  const ok = await playWithWebAudio()
  if (!ok) {
    await playWithHtmlAudio()
  }
}
