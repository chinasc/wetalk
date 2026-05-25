export interface User {
  id: number
  username: string
  nickname: string
  avatar: string
  phone: string
  created_at: string
}

export interface LoginParams {
  username: string
  password: string
  verify_code?: string
}

export interface LoginResult {
  token: string
  user: User
  is_new_user: boolean
}

export interface Game {
  id: number
  name: string
  icon: string
  cover: string
  description: string
  url: string
  sort_order: number
  status: number
}

export interface CustomerService {
  id: number
  nickname: string
  avatar: string
  online: boolean
}

export interface Conversation {
  id: number
  customer_service_id: number
  customer_service_name: string
  customer_service_avatar: string
  last_message: string
  last_message_time: string
  unread_count: number
  created_at: string
  updated_at: string
}

export interface ChatMessage {
  id: number
  conversation_id: number
  sender_id: number
  sender_type: 'user' | 'customer_service'
  type: 'text' | 'image' | 'album'
  content: string
  is_read: boolean
  created_at: string
}

export interface Album {
  id: number
  title: string
  cover: string
  image_count: number
  created_at: string
}

export interface AlbumDetail {
  id: number
  title: string
  images: AlbumImage[]
  created_at: string
}

export interface AlbumImage {
  id: number
  url: string
  remark: string
  sort_order: number
}

export interface WsEvent {
  event: string
  data: Record<string, unknown>
}
