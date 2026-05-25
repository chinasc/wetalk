import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import LayoutView from '@/views/LayoutView.vue'

const router = createRouter({
  history: createWebHistory('/admin/'),
  routes: [
    {
      path: '/login',
      name: 'Login',
      component: () => import('@/views/LoginView.vue'),
      meta: { requiresAuth: false },
    },
    {
      path: '/',
      component: LayoutView,
      meta: { requiresAuth: true },
      redirect: '/users',
      children: [
        {
          path: 'users',
          name: 'UserManage',
          component: () => import('@/views/UserManage.vue'),
          meta: { title: '用户管理' },
        },
        {
          path: 'cs',
          name: 'CsManage',
          component: () => import('@/views/CsManage.vue'),
          meta: { title: '客服管理' },
        },
        {
          path: 'albums',
          name: 'AlbumManage',
          component: () => import('@/views/AlbumManage.vue'),
          meta: { title: '相册管理' },
        },
        {
          path: 'chats',
          name: 'ChatRecords',
          component: () => import('@/views/ChatRecords.vue'),
          meta: { title: '聊天记录' },
        },
        {
          path: 'games',
          name: 'GameManage',
          component: () => import('@/views/GameManage.vue'),
          meta: { title: '游戏管理' },
        },
        {
          path: 'quick-replies',
          name: 'QuickReplyManage',
          component: () => import('@/views/QuickReplyManage.vue'),
          meta: { title: '常用语管理' },
        },
        {
          path: 'settings',
          name: 'Settings',
          component: () => import('@/views/Settings.vue'),
          meta: { title: '系统设置' },
        },
      ],
    },
  ],
})

router.beforeEach(async (to, _from, next) => {
  const auth = useAuthStore()
  const isLoggedIn = !!auth.token

  if (to.meta.requiresAuth !== false && !isLoggedIn) {
    next('/login')
  } else if (to.path === '/login' && isLoggedIn) {
    next('/users')
  } else {
    next()
  }
})

export default router
