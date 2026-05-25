import type { WsNewMessageData, WsReadData } from '../types'

type MessageHandler = (data: WsNewMessageData) => void
type ReadHandler = (data: WsReadData) => void

// const WS_URL = 'ws://localhost:8788'
const WS_URL = `${location.protocol === 'https:' ? 'wss:' : 'ws:'}//${location.host}/ws`

class WebSocketService {
  private ws: WebSocket | null = null
  private token: string = ''
  private reconnectTimer: ReturnType<typeof setTimeout> | null = null
  private pingTimer: ReturnType<typeof setInterval> | null = null
  private messageHandlers: MessageHandler[] = []
  private readHandlers: ReadHandler[] = []
  private statusHandlers: ((connected: boolean) => void)[] = []
  private onAuthSuccess: (() => void) | null = null

  connect(token: string, onAuth?: () => void): void {
    this.token = token
    this.onAuthSuccess = onAuth || null
    this.cleanup()
    this.doConnect()
  }

  private doConnect(): void {
    if (this.reconnectTimer) {
      clearTimeout(this.reconnectTimer)
      this.reconnectTimer = null
    }

    try {
      this.ws = new WebSocket(WS_URL)
    } catch {
      this.scheduleReconnect()
      return
    }

    this.ws.onopen = () => {
      this.notifyStatus(true)
      this.send('auth', { token: this.token })
      this.pingTimer = setInterval(() => {
        this.send('ping', {})
      }, 30000)
    }

    this.ws.onmessage = (event: MessageEvent) => {
      try {
        const msg = JSON.parse(event.data as string)
        this.handleMessage(msg)
      } catch {
        // ignore
      }
    }

    this.ws.onclose = () => {
      this.notifyStatus(false)
      this.cleanupTimers()
      this.scheduleReconnect()
    }

    this.ws.onerror = () => {
      this.notifyStatus(false)
    }
  }

  private handleMessage(msg: { event: string; data: unknown }): void {
    switch (msg.event) {
      case 'auth.success':
        if (this.onAuthSuccess) {
          this.onAuthSuccess()
        }
        break
      case 'message.new':
        for (const handler of this.messageHandlers) {
          handler(msg.data as WsNewMessageData)
        }
        break
      case 'message.read':
        for (const handler of this.readHandlers) {
          handler(msg.data as WsReadData)
        }
        break
    }
  }

  send(event: string, data: unknown): void {
    if (this.ws && this.ws.readyState === WebSocket.OPEN) {
      this.ws.send(JSON.stringify({ event, data }))
    }
  }

  sendChatMessage(conversationId: number, type: string, content: string): void {
    this.send('chat.send', {
      conversation_id: conversationId,
      type,
      content,
    })
  }

  sendRead(conversationId: number): void {
    this.send('chat.read', { conversation_id: conversationId })
  }

  onMessage(handler: MessageHandler): () => void {
    this.messageHandlers.push(handler)
    return () => {
      const idx = this.messageHandlers.indexOf(handler)
      if (idx > -1) this.messageHandlers.splice(idx, 1)
    }
  }

  onRead(handler: ReadHandler): () => void {
    this.readHandlers.push(handler)
    return () => {
      const idx = this.readHandlers.indexOf(handler)
      if (idx > -1) this.readHandlers.splice(idx, 1)
    }
  }

  onStatus(handler: (connected: boolean) => void): () => void {
    this.statusHandlers.push(handler)
    return () => {
      const idx = this.statusHandlers.indexOf(handler)
      if (idx > -1) this.statusHandlers.splice(idx, 1)
    }
  }

  private notifyStatus(connected: boolean): void {
    for (const handler of this.statusHandlers) {
      handler(connected)
    }
  }

  private cleanupTimers(): void {
    if (this.pingTimer) {
      clearInterval(this.pingTimer)
      this.pingTimer = null
    }
  }

  private scheduleReconnect(): void {
    this.reconnectTimer = setTimeout(() => {
      this.doConnect()
    }, 3000)
  }

  private cleanup(): void {
    this.cleanupTimers()
    if (this.reconnectTimer) {
      clearTimeout(this.reconnectTimer)
      this.reconnectTimer = null
    }
    if (this.ws) {
      this.ws.onopen = null
      this.ws.onmessage = null
      this.ws.onclose = null
      this.ws.onerror = null
      this.ws.close()
      this.ws = null
    }
  }

  disconnect(): void {
    this.onAuthSuccess = null
    this.cleanup()
    this.messageHandlers = []
    this.readHandlers = []
    this.statusHandlers = []
  }
}

export const wsService = new WebSocketService()
