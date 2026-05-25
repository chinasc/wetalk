<template>
  <router-view />
</template>

<script setup lang="ts">
// Pure router-view shell. Each view manages its own layout.
</script>

<style>
/* Global reset */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html,
body {
  height: 100%;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue',
    Arial, 'PingFang SC', 'Hiragino Sans GB', 'Microsoft YaHei', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  background: #f5f5f5;
  -webkit-tap-highlight-color: transparent;
}

#app {
  height: 100%;
}

/* Prevent iOS bounce scroll on non-scrollable pages */
body {
  overscroll-behavior: none;
}
</style>
