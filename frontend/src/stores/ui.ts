import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

type ThemeChoice = 'light' | 'dark' | 'system'

const KEY = 'opsyra.theme'

function stored(): ThemeChoice {
  try {
    const value = localStorage.getItem(KEY)
    return value === 'light' || value === 'dark' ? value : 'system'
  } catch {
    return 'system'
  }
}

export const useUiStore = defineStore('ui', () => {
  const choice = ref<ThemeChoice>(stored())
  const systemDark = ref(window.matchMedia?.('(prefers-color-scheme: dark)').matches ?? false)

  window.matchMedia?.('(prefers-color-scheme: dark)').addEventListener?.('change', (e) => {
    systemDark.value = e.matches
  })

  const isDark = computed(() => (choice.value === 'system' ? systemDark.value : choice.value === 'dark'))

  function toggle(): void {
    choice.value = isDark.value ? 'light' : 'dark'
    try {
      localStorage.setItem(KEY, choice.value)
    } catch {
      // Preference just won't persist.
    }
  }

  return { isDark, toggle }
})
