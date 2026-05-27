const SOUND_KEY = 'cs_sound_enabled'

function createDingUrl(): string {
  const sampleRate = 44100
  const duration = 0.25
  const numSamples = Math.floor(sampleRate * duration)
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
  view.setUint32(24, sampleRate, true)
  view.setUint32(28, sampleRate * 2, true)
  view.setUint16(32, 2, true)
  view.setUint16(34, 16, true)
  writeStr(36, 'data')
  view.setUint32(40, numSamples * 2, true)

  for (let i = 0; i < numSamples; i++) {
    const t = i / sampleRate
    const envelope = Math.exp(-t * 14)
    const sample = (Math.sin(2 * Math.PI * 880 * t) * 0.5 + Math.sin(2 * Math.PI * 1320 * t) * 0.3) * envelope
    view.setInt16(44 + i * 2, Math.max(-32768, Math.min(32767, sample * 32767)), true)
  }

  return URL.createObjectURL(new Blob([buffer], { type: 'audio/wav' }))
}

const audio = new Audio(createDingUrl())
audio.volume = 1.0
audio.preload = 'auto'

let unlocked = false

function unlock() {
  if (unlocked) return
  unlocked = true
  audio.play().then(() => {
    audio.pause()
    audio.currentTime = 0
  }).catch(() => {})
  document.removeEventListener('click', unlock)
  document.removeEventListener('touchstart', unlock)
  document.removeEventListener('touchend', unlock)
}

document.addEventListener('click', unlock)
document.addEventListener('touchstart', unlock)
document.addEventListener('touchend', unlock)

export function isSoundEnabled(): boolean {
  return localStorage.getItem(SOUND_KEY) !== 'false'
}

export function setSoundEnabled(enabled: boolean): void {
  localStorage.setItem(SOUND_KEY, String(enabled))
}

export function playNotification(): void {
  if (!isSoundEnabled()) return
  if (!unlocked) return
  try {
    audio.pause()
    audio.currentTime = 0
    audio.play().catch(() => {})
  } catch {
    // silently fail
  }
}
