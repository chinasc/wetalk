import { defineAsyncComponent, type Component } from 'vue'

export const gameRegistry: Record<string, Component> = {
  '2048': defineAsyncComponent(() => import('./Game2048.vue')),
  'snake': defineAsyncComponent(() => import('./GameSnake.vue')),
  'memory': defineAsyncComponent(() => import('./GameMemory.vue')),
  'minesweeper': defineAsyncComponent(() => import('./GameMinesweeper.vue')),
}
