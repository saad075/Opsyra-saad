<script setup lang="ts">
import { NAlert, NButton, NForm, NFormItem, NInput } from 'naive-ui'
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { errorMessage } from '@/api/client'
import { rememberedClientCode, useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

// Arriving from the website's "Log in" (…/login?client_code=acme) fills the
// code in; otherwise the last code used on this device.
const fromLink = typeof route.query.client_code === 'string' ? route.query.client_code : ''

// From the website's "Enter the demo": the shared, public demo sign-in is
// filled in for one-click entry (the demo company resets nightly).
const isDemo = route.query.demo === '1'
const DEMO = { client_code: 'demo', email: 'admin@demo-company.example', password: 'opsyra-demo-2026' }

const form = reactive(isDemo ? { ...DEMO } : { client_code: fromLink || rememberedClientCode(), email: '', password: '' })
const error = ref('')
const busy = ref(false)

async function submit() {
  error.value = ''
  busy.value = true
  try {
    await auth.login({ ...form, client_code: form.client_code.trim().toLowerCase() })
    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : '/'
    await router.push(auth.mustChangePassword ? { name: 'change-password' } : redirect)
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <h1>{{ isDemo ? 'Enter the live demo' : 'Sign in' }}</h1>
  <p class="muted intro">
    <template v-if="isDemo">Demo Company is shared by every visitor and resets every night. The sign-in is filled in for you.</template>
    <template v-else>Use the company code your administrator gave you.</template>
  </p>

  <NAlert v-if="error" type="error" :show-icon="false" class="alert">{{ error }}</NAlert>

  <NForm @submit.prevent="submit">
    <NFormItem label="Company code" path="client_code">
      <NInput v-model:value="form.client_code" placeholder="e.g. acme" :input-props="{ autocomplete: 'organization', autocapitalize: 'off' }" />
    </NFormItem>
    <NFormItem label="Email" path="email">
      <NInput v-model:value="form.email" :input-props="{ type: 'email', autocomplete: 'username' }" />
    </NFormItem>
    <NFormItem label="Password" path="password">
      <NInput v-model:value="form.password" type="password" show-password-on="click" :input-props="{ autocomplete: 'current-password' }" />
    </NFormItem>
    <NButton type="primary" block attr-type="submit" :loading="busy" :disabled="!form.client_code || !form.email || !form.password">
      {{ isDemo ? 'Enter the demo' : 'Sign in' }}
    </NButton>
  </NForm>

  <div class="links">
    <RouterLink :to="{ name: 'forgot-password' }">Forgot your password?</RouterLink>
  </div>
</template>

<style scoped>
.intro {
  margin: 6px 0 18px;
}

.alert {
  margin-bottom: 16px;
}

.links {
  margin-top: 16px;
  text-align: center;
}

a {
  color: inherit;
}
</style>
