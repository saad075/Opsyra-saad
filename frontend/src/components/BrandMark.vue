<script setup lang="ts">
defineProps<{ size?: number }>()
</script>

<template>
  <span class="brand">
    <svg :width="size ?? 28" :height="size ?? 28" viewBox="0 0 32 32" aria-hidden="true">
      <rect width="32" height="32" rx="8" fill="currentColor" />
      <circle cx="16" cy="16" r="8" fill="none" stroke="#fff" stroke-width="3.5" />
    </svg>
    <span class="name">Opsyra</span>
  </span>
</template>

<style scoped>
.brand {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  color: var(--brand-accent, #0e6e62);
}

.name {
  font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif;
  font-weight: 700;
  font-size: 1.25rem;
  color: var(--n-text-color, inherit);
  letter-spacing: -0.01em;
}
</style>
