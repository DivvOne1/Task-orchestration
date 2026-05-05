<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const error = ref('')
const loading = ref(false)

async function submit() {
  loading.value = true
  error.value = ''

  try {
    await auth.register(form.value)
    await router.push({ name: 'dashboard' })
  } catch (err) {
    error.value =
      err.response?.data?.message ||
      err.response?.data?.errors?.email?.[0] ||
      err.response?.data?.errors?.password?.[0] ||
      'Registration failed.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <section class="page auth-page">
    <div class="panel form-panel">
      <p class="eyebrow">Auth</p>
      <h2>Create account</h2>
      <form class="form-grid" @submit.prevent="submit">
        <input v-model="form.name" class="input" type="text" placeholder="Name" />
        <input v-model="form.email" class="input" type="email" placeholder="Email" />
        <input v-model="form.password" class="input" type="password" placeholder="Password" />
        <input
          v-model="form.password_confirmation"
          class="input"
          type="password"
          placeholder="Confirm password"
        />
        <p v-if="error" class="form-error">{{ error }}</p>
        <button class="primary-button" type="submit" :disabled="loading">
          {{ loading ? 'Creating account...' : 'Register' }}
        </button>
      </form>
    </div>
  </section>
</template>
