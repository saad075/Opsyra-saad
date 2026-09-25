<script setup lang="ts">
import { NAlert, NButton, NForm, NFormItem, NInput } from 'naive-ui'
import { reactive, ref } from 'vue'
import { api, errorMessage } from '@/api/client'
import { rememberedClientCode } from '@/stores/auth'

const form = reactive({ client_code: rememberedClientCode(), email: '' })
const sent = ref('')
const error = ref('')
const busy = ref(false)

async function submit() {
  error.value = ''
  busy.value = true
  try {
    const { data } = await api.post('/auth/forgot-password', { ...form, client_code: form.client_code.trim().toLowerCase() })
    sent.value = data.message
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <h1>Forgot password</h1>

  <template v-if="sent">
    <NAlert type="success" :show-icon="false" class="alert">{{ sent }}</NAlert>
    <p class="muted">The link works once and expires in an hour.</p>
  </template>

  <template v-else>
    <p class="muted intro">We'll email you a link to set a new password.</p>
    <NAlert v-if="error" type="error" :show-icon="false" class="alert">{{ error }}</NAlert>
    <NForm @submit.prevent="submit">
      <NFormItem label="Company code">
        <NInput v-model:value="form.client_code" :input-props="{ autocapitalize: 'off' }" />
      </NFormItem>
      <NFormItem label="Email">
        <NInput v-model:value="form.email" :input-props="{ type: 'email', autocomplete: 'username' }" />
      </NFormItem>
      <NButton type="primary" block attr-type="submit" :loading="busy" :disabled="!form.client_code || !form.email">Send reset link</NButton>
    </NForm>
  </template>

  <div class="links">
    <RouterLink :to="{ name: 'login' }">Back to sign in</RouterLink>
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
