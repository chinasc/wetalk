<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { showToast } from 'vant'
import { useGameStore } from '../stores/game'
import { useChatStore } from '../stores/chat'
import { useAuthStore } from '../stores/auth'
import AccessCodeDialog from '../components/AccessCodeDialog.vue'

const router = useRouter()
const gameStore = useGameStore()
const chatStore = useChatStore()
const authStore = useAuthStore()

const activeTab = ref(0)
const showAccessDialog = ref(false)

const totalUnread = computed(() => chatStore.totalUnread)

onMounted(() => {
  gameStore.fetchGames()
  chatStore.fetchConversations()
})

function openCs() {
  showAccessDialog.value = true
}

function onAccessVerified() {
  showAccessDialog.value = false
  router.push('/cs-select')
}

function openGame(id: number) {
  router.push(`/game/${id}`)
}

function goToConversations() {
  router.push('/conversations')
}

function goToProfile() {
  router.push('/profile')
}
</script>

<template>
  <div class="home-page">
    <!-- Tab: 游戏 -->
    <div v-show="activeTab === 0" class="page">
      <van-nav-bar title="WeTalk" :border="false" class="home-nav">
        <template #right>
          <van-button
            size="small"
            type="primary"
            round
            icon="service-o"
            class="cs-btn"
            @click="openCs"
          >
            客服
          </van-button>
        </template>
      </van-nav-bar>

      <div class="page-content game-list">
        <van-loading v-if="gameStore.loading" class="page-loading" size="36px" vertical>
          加载中...
        </van-loading>
        <van-empty v-else-if="gameStore.games.length === 0" description="暂无游戏" />
        <van-grid
          v-else
          :column-num="2"
          :gutter="12"
          :border="false"
          class="game-grid"
        >
          <van-grid-item
            v-for="game in gameStore.games"
            :key="game.id"
            @click="openGame(game.id)"
          >
            <div class="game-card">
              <div class="game-card-icon">
                <img v-if="game.icon || game.cover" :src="game.icon || game.cover" :alt="game.name" />
                <van-icon v-else name="games-o" size="40" color="#1989fa" />
              </div>
              <div class="game-card-name">{{ game.name }}</div>
              <div class="game-card-desc">{{ game.description }}</div>
            </div>
          </van-grid-item>
        </van-grid>
      </div>
    </div>

    <!-- Tab: 消息 -->
    <div v-show="activeTab === 1" class="page">
      <van-nav-bar title="消息" :border="false" />
      <div class="page-content">
        <van-empty
          v-if="chatStore.sortedConversations.length === 0"
          description="暂无消息"
        />
        <van-cell-group v-else inset style="margin-top: 12px;">
          <van-cell
            v-for="conv in chatStore.sortedConversations"
            :key="conv.id"
            :title="conv.customer_service_name"
            :label="conv.last_message || '暂无消息'"
            is-link
            class="conv-cell"
            :class="{ 'has-unread': conv.unread_count > 0 }"
            @click="router.push(`/chat/${conv.id}`)"
          >
            <template #icon>
              <van-image
                round
                width="44"
                height="44"
                fit="cover"
                :src="conv.customer_service_avatar"
                class="conv-avatar"
              >
                <template #error>
                  <van-icon name="manager-o" size="24" />
                </template>
              </van-image>
            </template>
            <template #value>
              <div class="conv-meta">
                <span class="conv-time">
                  {{ new Date(conv.last_message_time).toLocaleTimeString('zh-CN', { hour: '2-digit', minute: '2-digit' }) }}
                </span>
                <van-badge v-if="conv.unread_count > 0" :content="conv.unread_count" :max="99" class="unread-badge" />
              </div>
            </template>
          </van-cell>
        </van-cell-group>
      </div>
    </div>

    <!-- Tab: 我的 -->
    <div v-show="activeTab === 2" class="page">
      <van-nav-bar title="我的" :border="false" />
      <div class="page-content">
        <div class="profile-card">
          <van-image
            round
            width="64"
            height="64"
            fit="cover"
            :src="authStore.avatar"
            class="profile-avatar"
          >
            <template #error>
              <van-icon name="manager-o" size="36" color="#969799" />
            </template>
          </van-image>
          <div class="profile-info">
            <div class="profile-name">{{ authStore.nickname || '未设置昵称' }}</div>
            <div class="profile-id">{{ authStore.user?.username }}</div>
          </div>
        </div>

        <van-cell-group inset style="margin-top: 16px;">
          <van-cell title="个人资料" icon="user-o" is-link @click="goToProfile" />
          <van-cell title="修改密码" icon="lock" is-link @click="goToProfile" />
        </van-cell-group>

        <van-cell-group inset style="margin-top: 16px;">
          <van-cell title="关于我们" icon="info-o" is-link />
        </van-cell-group>

        <div style="padding: 24px 16px;">
          <van-button block round plain type="danger" @click="authStore.logout(); router.push('/login')">
            退出登录
          </van-button>
        </div>
      </div>
    </div>

    <!-- Bottom TabBar -->
    <van-tabbar v-model="activeTab" route-placeholder>
      <van-tabbar-item icon="apps-o">游戏</van-tabbar-item>
      <van-tabbar-item icon="chat-o" :badge="totalUnread > 0 ? totalUnread : undefined">
        消息
      </van-tabbar-item>
      <van-tabbar-item icon="user-o">我的</van-tabbar-item>
    </van-tabbar>

    <!-- Access Code Dialog -->
    <AccessCodeDialog
      v-model:show="showAccessDialog"
      @verified="onAccessVerified"
    />
  </div>
</template>

<style scoped>
.home-page {
  height: 100vh;
  height: 100dvh;
  display: flex;
  flex-direction: column;
  position: relative;
}

.home-nav :deep(.van-nav-bar__title) {
  font-weight: 700;
  font-size: 18px;
}

.cs-btn {
  min-height: 32px;
}

.page-loading {
  margin-top: 60px;
}

.game-list {
  padding: 12px;
}

.game-grid {
  padding: 4px 0;
}

.game-card {
  background: #fff;
  border-radius: 12px;
  padding: 20px 12px 16px;
  text-align: center;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
  min-height: 130px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.game-card-icon {
  width: 56px;
  height: 56px;
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.game-card-icon img {
  width: 100%;
  height: 100%;
  border-radius: 12px;
  object-fit: cover;
}

.game-card-name {
  font-size: 14px;
  font-weight: 600;
  color: #323233;
  margin-bottom: 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%;
}

.game-card-desc {
  font-size: 12px;
  color: #969799;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.conv-avatar {
  margin-right: 12px;
  flex-shrink: 0;
}

.conv-meta {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 6px;
}

.conv-time {
  font-size: 11px;
  color: #969799;
}

.conv-cell.has-unread :deep(.van-cell__title span) {
  font-weight: 600;
  color: #323233;
}

.conv-cell.has-unread {
  background: #f0f5ff;
}

.conv-cell.has-unread :deep(.van-cell__label) {
  color: #666;
  font-weight: 500;
}

.unread-badge :deep(.van-badge) {
  font-size: 12px;
}

.profile-card {
  display: flex;
  align-items: center;
  padding: 24px 20px;
  background: #fff;
  margin: 12px 16px 0;
  border-radius: 12px;
}

.profile-avatar {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f2f3f5;
  overflow: hidden;
}

.profile-info {
  margin-left: 16px;
}

.profile-name {
  font-size: 18px;
  font-weight: 600;
  color: #323233;
}

.profile-id {
  font-size: 13px;
  color: #969799;
  margin-top: 4px;
}
</style>
