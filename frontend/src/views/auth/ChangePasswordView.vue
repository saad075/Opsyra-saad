<script setup lang="ts">
import { NAlert, NButton, NForm, NFormItem, NInput, useMessage } from 'naive-ui'
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { errorMessage } from '@/api/client'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const message = useMessage()

const form = reactive({ current_password: '', password: '', password_confirmation: '' })
const error = ref('')
const busy = ref(false)

async function submit() {
  error.value = ''
  busy.value = true
  try {
    await auth.changePassword(form)
    message.success('Password changed. Other devices have been signed out.')
    await router.push({ name: 'dashboard' })
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    busy.value = false
  }
}

async function signOut() {
  await auth.logout()
  await router.push({ name: 'login' })
}
</script>

<template>
  <h1>{{ auth.mustChangePassword ? 'Choose your password' : 'Change password' }}</h1>
  <p class="muted intro">
    <template v-if="auth.mustChangePassword">You signed in with a temporary password. Choose your own to continue.</template>
    At least 10 characters, with letters and numbers.
  </p>

  <NAlert v-if="error" type="error" :show-icon="false" class="alert">{{ error }}</NAlert>

  <NForm @submit.prevent="submit">
    <NFormItem :label="auth.mustChangePassword ? 'Temporary password' : 'Current password'">
      <NInput v-model:value="form.current_password" type="password" show-password-on="click" :input-props="{ autocomplete: 'current-password' }" />
    </NFormItem>
    <NFormItem label="New password">
      <NInput v-model:value="form.password" type="password" show-password-on="click" :input-props="{ autocomplete: 'new-password' }" />
    </NFormItem>
    <NFormItem label="Confirm new password">
      <NInput v-model:value="form.password_confirmation" type="password" :input-props="{ autocomplete: 'new-password' }" />
    </NFormItem>
    <NButton type="primary" block attr-type="submit" :loading="busy" :disabled="!form.current_password || !form.password || !form.password_confirmation">
      Save password
    </NButton>
  </NForm>

  <div class="links">
    <RouterLink v-if="!auth.mustChangePassword" :to="{ name: 'dashboard' }">Cancel</RouterLink>
    <a v-else href="#" @click.prevent="signOut">Sign out</a>
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
