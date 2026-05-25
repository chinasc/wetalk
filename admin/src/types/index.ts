export interface AdminInfo {
  id: number
  username: string
  nickname: string
  avatar: string
  role: string
}

export interface LoginParams {
  username: string
  password: string
}

export interface LoginResult {
  code: number
  msg: string
  data: {
    token: string
    user: {
      id: number
      username: string
      nickname: string
      avatar: string
      role: string
    }
  }
}

export interface User {
  id: number
  username: string
  nickname: string
  avatar: string
  status: number
  is_cs: boolean
  created_at: string
}

export interface CreateUserParams {
  username: string
  nickname: string
}

export interface CreateUserResult {
  verify_code: string
}

export interface CsItem {
  id: number
  user_id: number
  username: string
  nickname: string
  avatar: string
  is_online: boolean
}

export interface Album {
  id: number
  name: string
  cover: string
  sort_order: number
  image_count: number
  created_at: string
}

export interface AlbumImage {
  id: number
  album_id: number
  url: string
  remark: string
  sort_order: number
  created_at: string
}

export interface CreateAlbumParams {
  name: string
  cover?: File
  sort_order: number
}

export interface UploadImageParams {
  image: File
  remark: string
  sort_order: number
}

export interface Conversation {
  id: number
  customer_service_id: number
  user_id: number
  cs_name: string
  cs_avatar: string
  user_name: string
  user_avatar: string
  last_message: string
  last_time: string
}

export interface ChatMessage {
  id: number
  conversation_id: number
  sender_id: number
  sender_type: string
  sender_nickname: string
  content: string
  message_type: string
  type: string
  created_at: string
}

export interface SystemSettings {
  chat_access_code: string
  upload_max_size: number
  [key: string]: string | number
}

export interface Game {
  id: number
  name: string
  cover: string
  description: string
  url: string
  sort_order: number
  status: number
  created_at: string
}

export interface CreateGameParams {
  name: string
  cover?: File
  description: string
  url: string
  sort_order: number
  status: number
}

export interface PaginatedResult<T> {
  list: T[]
  total: number
  page: number
  page_size: number
}
