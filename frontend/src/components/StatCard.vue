<script setup lang="ts">
import { NCard } from 'naive-ui'

defineProps<{
  label: string
  value: string | number
  hint?: string
  /** Draws attention when something needs doing. */
  tone?: 'default' | 'warning'
  to?: { name: string }
}>()
</script>

<template>
  <component :is="to ? 'RouterLink' : 'div'" :to="to" class="stat-link">
    <NCard size="small" class="stat" :class="tone" hoverable>
      <p class="label">{{ label }}</p>
      <p class="value">{{ value }}</p>
      <p v-if="hint" class="hint">{{ hint }}</p>
    </NCard>
  </component>
</template>

<style scoped>
.stat-link {
  color: inherit;
  text-decoration: none;
  display: block;
  height: 100%;
}

.stat {
  height: 100%;
}

.stat.warning {
  border-left: 4px solid #8a5a00;
}

.label {
  margin: 0;
  font-size: 0.85rem;
  font-weight: 600;
  opacity: 0.7;
}

.value {
  margin: 6px 0 0;
  font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif;
  font-size: 1.9rem;
  font-weight: 800;
  line-height: 1.1;
}

.hint {
  margin: 4px 0 0;
  font-size: 0.8rem;
  opacity: 0.65;
}
</style>
