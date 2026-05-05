<script setup>
import { useRouter } from 'vue-router'

import { useAuthStore } from './stores/auth'

const auth = useAuthStore()
const router = useRouter()

async function handleLogout() {
  await auth.logout()
  await router.push({ name: 'login' })
}
</script>

<template>
  <div class="app-shell">
    <aside class="sidebar">
      <div>
        <p class="eyebrow">TaskFlow</p>
        <h1>Task orchestration for small teams</h1>
        <p class="sidebar-copy">
          Laravel API, Vue dashboard, RabbitMQ notifications and Go background processing in one workspace.
        </p>
      </div>

      <nav class="sidebar-nav">
        <RouterLink to="/">Dashboard</RouterLink>
        <RouterLink to="/projects">Projects</RouterLink>
        <RouterLink to="/tasks">Tasks</RouterLink>
        <RouterLink to="/notifications">Notifications</RouterLink>
        <RouterLink v-if="!auth.isAuthenticated" to="/login">Login</RouterLink>
        <RouterLink v-if="!auth.isAuthenticated" to="/register">Register</RouterLink>
        <button v-if="auth.isAuthenticated" class="nav-button" type="button" @click="handleLogout">
          Logout
        </button>
      </nav>
    </aside>

    <main class="content-panel">
      <RouterView />
    </main>
  </div>
</template>
