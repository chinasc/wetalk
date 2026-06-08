type EventHandler = (data: Record<string, unknown>) => void

class WebSocketService {
  private ws: WebSocket | null = null
  private url = `${location.protocol === 'https:' ? 'wss:' : 'ws:'}//${location.host}/ws`
  private token = ''
  private handlers: Map<string, Set<EventHandler>> = new Map()
  private reconnectTimer: ReturnType<typeof setTimeout> | null = null
  private pingTimer: ReturnType<typeof setInterval> | null = null
  private reconnectAttempts = 0
  private shouldReconnect = false
  private connecting = false

  connect(token: string): void {
    this.token = token
    this.shouldReconnect = true
    this.reconnectAttempts = 0
    this.connecting = false

    // Clear any pending reconnect timer to prevent ghost connections
    if (this.reconnectTimer) {
      clearTimeout(this.reconnectTimer)
      this.reconnectTimer = null
    }

    this.doConnect()
  }

  private doConnect(): void {
    this.stopPing()

    if (this.ws) {
      // Prevent onclose from triggering reconnect for the old connection
      this.connecting = true
      this.ws.close()
      this.ws = null
    }

    this.connecting = false
    this.ws = new WebSocket(this.url)

    this.ws.onopen = () => {
      this.reconnectAttempts = 0
      this.send('auth', { token: this.token })
      this.startPing()
    }

    this.ws.onmessage = (event: MessageEvent) => {
      try {
        const msg = JSON.parse(event.data as string) as { event: string; data: Record<string, unknown> }
        if (msg.event === 'kicked') {
          this.stopPing()
          this.shouldReconnect = false
          if (this.ws) {
            this.ws.close()
            this.ws = null
          }
          const kickedHandlers = this.handlers.get('kicked')
          if (kickedHandlers) {
            kickedHandlers.forEach((handler) => handler(msg.data))
          }
          return
        }
        const handlers = this.handlers.get(msg.event)
        if (handlers) {
          handlers.forEach((handler) => handler(msg.data))
        }
        const wildcardHandlers = this.handlers.get('*')
        if (wildcardHandlers) {
          wildcardHandlers.forEach((handler) => handler(msg.data))
        }
      } catch {
        // Ignore malformed messages
      }
    }

    this.ws.onclose = () => {
      this.stopPing()
      // Don't reconnect if we're in the middle of a deliberate reconnect
      if (this.connecting) return
      if (this.shouldReconnect) {
        this.scheduleReconnect()
      }
    }

    this.ws.onerror = () => {
      // onclose will fire after this
    }
  }

  private scheduleReconnect(): void {
    const maxAttempts = 20
    if (this.reconnectAttempts >= maxAttempts) {
      return
    }
    const delay = Math.min(1000 * Math.pow(2, this.reconnectAttempts), 30000)
    this.reconnectAttempts++
    this.reconnectTimer = setTimeout(() => {
      this.doConnect()
    }, delay)
  }

  private startPing(): void {
    this.stopPing()
    this.pingTimer = setInterval(() => {
      this.send('ping', {})
    }, 15000)
  }

  private stopPing(): void {
    if (this.pingTimer) {
      clearInterval(this.pingTimer)
      this.pingTimer = null
    }
  }

  send(event: string, data: Record<string, unknown>): void {
    if (this.ws && this.ws.readyState === WebSocket.OPEN) {
      this.ws.send(JSON.stringify({ event, data }))
    }
  }

  on(event: string, handler: EventHandler): void {
    if (!this.handlers.has(event)) {
      this.handlers.set(event, new Set())
    }
    this.handlers.get(event)!.add(handler)
  }

  off(event: string, handler: EventHandler): void {
    const handlers = this.handlers.get(event)
    if (handlers) {
      handlers.delete(handler)
    }
  }

  disconnect(): void {
    this.shouldReconnect = false
    this.connecting = false
    if (this.reconnectTimer) {
      clearTimeout(this.reconnectTimer)
      this.reconnectTimer = null
    }
    this.stopPing()
    if (this.ws) {
      this.ws.close()
      this.ws = null
    }
    // Don't clear handlers — preserve listeners (e.g. kicked) for re-login
  }

  get isConnected(): boolean {
    return this.ws?.readyState === WebSocket.OPEN
  }
}

export const wsService = new WebSocketService()
export default wsService
