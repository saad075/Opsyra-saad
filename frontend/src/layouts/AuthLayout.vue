<script setup lang="ts">
import { NButton, NCard } from 'naive-ui'
import BrandMark from '@/components/BrandMark.vue'
import { useUiStore } from '@/stores/ui'

const ui = useUiStore()
</script>

<template>
  <div class="auth-shell" :class="{ dark: ui.isDark }">
    <div class="auth-column">
      <BrandMark class="auth-brand" :size="32" />
      <NCard class="auth-card" :bordered="true">
        <RouterView />
      </NCard>
      <NButton text size="small" class="theme-toggle" @click="ui.toggle()">
        {{ ui.isDark ? 'Light mode' : 'Dark mode' }}
      </NButton>
    </div>
  </div>
</template>

<style scoped>
.auth-shell {
  /* 100vh, not 100%: Naive UI's config provider wrapper has no height. */
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 32px 16px;
  background: linear-gradient(160deg, #e3f1ee 0%, #f6f7f9 45%);
}

.auth-shell.dark {
  background: linear-gradient(160deg, #17332f 0%, #11161c 45%);
  --brand-accent: #5cc4b4;
}

.auth-column {
  width: 100%;
  max-width: 420px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
}

.auth-card {
  width: 100%;
}

.theme-toggle {
  opacity: 0.7;
}
</style>
