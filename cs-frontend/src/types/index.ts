// ============ Auth ============

export interface LoginForm {
  username: string
  password: string
  verify_code?: string
}

export interface UserInfo {
  id: number
  username: string
  nickname: string
  avatar: string
  role: string
}

// ============ Chat ============

export interface Conversation {
  id: number
  user_id: number
  user_nickname: string
  user_avatar: string
  last_message: string
  last_message_type: MessageType
  last_message_time: string
  unread_count: number
}

export type MessageType = 'text' | 'image' | 'album'

export interface ChatMessage {
  id: number
  conversation_id: number
  sender_id: number
  sender_type: 'user' | 'cs'
  type: MessageType
  content: string
  created_at: string
}

// ============ Album ============

export interface Album {
  id: number
  title: string
  cover: string
  description: string
  photo_count: number
}

export interface AlbumDetail {
  id: number
  title: string
  cover: string
  description: string
  photos: AlbumPhoto[]
}

export interface AlbumPhoto {
  id: number
  url: string
  caption: string
}

// ============ WebSocket ============

export interface WsMessage {
  event: string
  data: unknown
}

export interface WsNewMessageData {
  id: number
  conversation_id: number
  sender_id: number
  sender_type: 'user' | 'cs'
  type: MessageType
  content: string
  created_at: string
}

export interface WsReadData {
  conversation_id: number
  reader_id: number
}

// ============ API Response ============

export interface ApiResponse<T = unknown> {
  code: number
  message: string
  data: T
}
