<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()

const form = ref({
  email: '',
  password: '',
})

const error = ref('')
const loading = ref(false)

async function submit() {
  loading.value = true
  error.value = ''

  try {
    await auth.login(form.value)
    await router.push({ name: 'dashboard' })
  } catch (err) {
    error.value = err.response?.data?.message || err.response?.data?.errors?.email?.[0] || 'Login failed.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <section class="page auth-page">
    <div class="panel form-panel">
      <p class="eyebrow">Auth</p>
      <h2>Sign in</h2>
      <form class="form-grid" @submit.prevent="submit">
        <input v-model="form.email" class="input" type="email" placeholder="Email" />
        <input v-model="form.password" class="input" type="password" placeholder="Password" />
        <p v-if="error" class="form-error">{{ error }}</p>
        <button class="primary-button" type="submit" :disabled="loading">
          {{ loading ? 'Signing in...' : 'Login' }}
        </button>
      </form>
    </div>
  </section>
</template>
