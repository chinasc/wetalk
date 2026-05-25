import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { Game } from '../types'
import { gameApi } from '../api'

export const useGameStore = defineStore('game', () => {
  const games = ref<Game[]>([])
  const loading = ref(false)

  async function fetchGames() {
    loading.value = true
    try {
      const res = await gameApi.getGames()
      games.value = res.data
    } finally {
      loading.value = false
    }
  }

  return {
    games,
    loading,
    fetchGames,
  }
})
