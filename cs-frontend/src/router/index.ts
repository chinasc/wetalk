import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'
import LoginView from '../views/LoginView.vue'
import ConversationsView from '../views/ConversationsView.vue'
import ChatView from '../views/ChatView.vue'
import ProfileView from '../views/ProfileView.vue'
import AlbumsView from '../views/AlbumsView.vue'
import AlbumSendView from '../views/AlbumSendView.vue'
import AlbumDetailView from '../views/AlbumDetailView.vue'

const routes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { requiresAuth: false },
  },
  {
    path: '/',
    name: 'conversations',
    component: ConversationsView,
    meta: { requiresAuth: true },
  },
  {
    path: '/chat/:id',
    name: 'chat',
    component: ChatView,
    meta: { requiresAuth: true },
  },
  {
    path: '/profile',
    name: 'profile',
    component: ProfileView,
    meta: { requiresAuth: true },
  },
  {
    path: '/albums',
    name: 'albums',
    component: AlbumsView,
    meta: { requiresAuth: true },
  },
  {
    path: '/album-send',
    name: 'album-send',
    component: AlbumSendView,
    meta: { requiresAuth: true },
  },
  {
    path: '/album/:id',
    name: 'album-detail',
    component: AlbumDetailView,
    meta: { requiresAuth: true },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, _from, next) => {
  const token = localStorage.getItem('cs_token')
  const userStr = localStorage.getItem('cs_user')

  if (to.meta.requiresAuth !== false) {
    if (!token || !userStr) {
      return next({ path: '/login', query: { redirect: to.fullPath } })
    }
    try {
      const user = JSON.parse(userStr)
      if (user.role !== 'customer_service') {
        localStorage.removeItem('cs_token')
        localStorage.removeItem('cs_user')
        return next('/login')
      }
    } catch {
      localStorage.removeItem('cs_token')
      localStorage.removeItem('cs_user')
      return next('/login')
    }
  }

  if (to.path === '/login' && token && userStr) {
    try {
      const user = JSON.parse(userStr)
      if (user.role === 'customer_service') {
        return next('/')
      }
    } catch {
      // fall through to login
    }
  }

  next()
})

export default router
