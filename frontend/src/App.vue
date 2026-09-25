<script setup lang="ts">
import { createLocale, darkTheme as naiveDark, enUS, NConfigProvider, NDialogProvider, NGlobalStyle, NMessageProvider } from 'naive-ui'
import { computed } from 'vue'
import { useUiStore } from '@/stores/ui'
import { darkTheme, lightTheme } from '@/theme'

const ui = useUiStore()
const overrides = computed(() => (ui.isDark ? darkTheme : lightTheme))

// No default "Please Input" / "Please Select" text in empty fields — the
// label says what goes there; screens add a real example where it helps.
const locale = createLocale({ Input: { placeholder: '' }, Select: { placeholder: '' } }, enUS)
</script>

<template>
  <NConfigProvider :theme="ui.isDark ? naiveDark : null" :theme-overrides="overrides" :locale="locale">
    <NGlobalStyle />
    <NMessageProvider>
      <NDialogProvider>
        <RouterView />
      </NDialogProvider>
    </NMessageProvider>
  </NConfigProvider>
</template>
