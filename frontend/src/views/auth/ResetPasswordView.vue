<script setup lang="ts">
import { NAlert, NButton, NForm, NFormItem, NInput } from 'naive-ui'
import { computed, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { api, errorMessage } from '@/api/client'

// Arrives from the emailed link: ?token=…&client_code=…&email=…
const route = useRoute()
const params = computed(() => ({
  token: String(route.query.token ?? ''),
  client_code: String(route.query.client_code ?? ''),
  email: String(route.query.email ?? ''),
}))

// The welcome email for a new workspace uses the same link with &welcome=1.
const isWelcome = computed(() => route.query.welcome === '1')

const form = reactive({ password: '', password_confirmation: '' })
const done = ref(false)
const error = ref('')
const busy = ref(false)

async function submit() {
  error.value = ''
  busy.value = true
  try {
    await api.post('/auth/reset-password', { ...params.value, ...form })
    done.value = true
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <h1>{{ isWelcome ? 'Welcome to Opsyra' : 'Set a new password' }}</h1>

  <NAlert v-if="!params.token" type="warning" :show-icon="false" class="alert">
    This page needs the link from your reset email. Request a new one if it has expired.
  </NAlert>

  <template v-else-if="done">
    <NAlert type="success" :show-icon="false" class="alert">
      {{ isWelcome ? 'Your password is set. Sign in to start the setup wizard.' : 'Your password has been reset.' }}
    </NAlert>
    <RouterLink :to="{ name: 'login', query: { client_code: params.client_code } }">Sign in</RouterLink>
  </template>

  <template v-else>
    <p class="muted intro">
      <template v-if="isWelcome">Choose a password for {{ params.email }} to open your {{ params.client_code }} workspace.</template>
      <template v-else>For {{ params.email }}.</template>
      At least 10 characters, with letters and numbers.
    </p>
    <NAlert v-if="error" type="error" :show-icon="false" class="alert">{{ error }}</NAlert>
    <NForm @submit.prevent="submit">
      <NFormItem label="New password">
        <NInput v-model:value="form.password" type="password" show-password-on="click" :input-props="{ autocomplete: 'new-password' }" />
      </NFormItem>
      <NFormItem label="Confirm new password">
        <NInput v-model:value="form.password_confirmation" type="password" :input-props="{ autocomplete: 'new-password' }" />
      </NFormItem>
      <NButton type="primary" block attr-type="submit" :loading="busy" :disabled="!form.password || !form.password_confirmation">
        {{ isWelcome ? 'Set password' : 'Reset password' }}
      </NButton>
    </NForm>
  </template>

  <div class="links">
    <RouterLink :to="{ name: 'forgot-password' }">Request a new link</RouterLink>
  </div>
</template>

<style scoped>
.intro {
  margin: 6px 0 18px;
}

.alert {
  margin: 12px 0 16px;
}

.links {
  margin-top: 16px;
  text-align: center;
}

a {
  color: inherit;
}
</style>
