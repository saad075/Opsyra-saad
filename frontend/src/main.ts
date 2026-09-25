import { createPinia } from 'pinia'
import { createApp } from 'vue'
import App from './App.vue'
import { installAuthHandlers } from './api/client'
import { router } from './router'
import { useAuthStore } from './stores/auth'
import './styles.css'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

installAuthHandlers({
  onUnauthenticated: () => {
    useAuthStore(pinia).forget()
    if (router.currentRoute.value.name !== 'login') router.push({ name: 'login' })
  },
  onPasswordChangeRequired: () => {
    router.push({ name: 'change-password' })
  },
})

app.mount('#app')
