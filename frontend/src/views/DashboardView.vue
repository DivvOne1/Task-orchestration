<script setup>
import { computed, onMounted, ref } from 'vue'

import { api } from '../lib/api'

const stats = ref([
  { label: 'Users', value: '...' },
  { label: 'Projects', value: '...' },
  { label: 'Tasks', value: '...' },
  { label: 'Notifications', value: '...' },
])

const apiState = ref({
  loading: true,
  error: '',
  name: '',
  version: '',
  summary: null,
  features: [],
  status: 'checking',
})

const badgeText = computed(() => {
  if (apiState.value.loading) {
    return 'Connecting API'
  }

  if (apiState.value.error) {
    return 'API offline'
  }

  return 'API connected'
})

onMounted(async () => {
  try {
    const [{ data: meta }, { data: health }, { data: summary }] = await Promise.all([
      api.get('/meta'),
      api.get('/health'),
      api.get('/dashboard/summary'),
    ])

    apiState.value = {
      loading: false,
      error: '',
      name: meta.name,
      version: meta.version,
      summary,
      features: meta.features ?? [],
      status: health.status ?? 'ok',
    }

    stats.value = [
      { label: 'Users', value: String(summary.counts?.users ?? 0) },
      { label: 'Projects', value: String(summary.counts?.projects ?? 0) },
      { label: 'Tasks', value: String(summary.counts?.tasks ?? 0) },
      { label: 'Notifications', value: String(summary.counts?.notifications ?? 0) },
    ]
  } catch (error) {
    apiState.value = {
      loading: false,
      error: error instanceof Error ? error.message : 'Request failed',
      name: '',
      version: '',
      summary: null,
      features: [],
      status: 'error',
    }
  }
})
</script>

<template>
  <section class="page">
    <div class="page-head">
      <div>
        <p class="eyebrow">Dashboard</p>
        <h2>Keep projects, tasks and alerts in one place</h2>
      </div>
      <span class="badge">{{ badgeText }}</span>
    </div>

    <div class="stats-grid">
      <article v-for="stat in stats" :key="stat.label" class="stat-card">
        <span>{{ stat.label }}</span>
        <strong>{{ stat.value }}</strong>
      </article>
    </div>

    <div class="content-grid">
      <section class="panel">
        <div class="panel-head">
          <h3>Live backend summary</h3>
          <span>Laravel + PostgreSQL</span>
        </div>

        <div class="list" v-if="apiState.summary">
          <article class="list-item">
            <div>
              <h4>Server time</h4>
              <p>{{ apiState.summary.server_time }}</p>
            </div>
            <span class="tag">{{ apiState.summary.status }}</span>
          </article>
          <article class="list-item">
            <div>
              <h4>Database status</h4>
              <p>Health check from Laravel route</p>
            </div>
            <span class="tag">{{ apiState.summary.database }}</span>
          </article>
          <article class="list-item">
            <div>
              <h4>Source of truth</h4>
              <p>`GET /api/dashboard/summary`</p>
            </div>
            <span class="tag">live</span>
          </article>
        </div>
        <div v-else class="list">
          <article class="list-item">
            <div>
              <h4>Waiting for backend</h4>
              <p>Dashboard will populate from Laravel once the request completes.</p>
            </div>
          </article>
        </div>
      </section>

      <section class="panel accent-panel">
        <div class="panel-head">
          <h3>Backend connection</h3>
          <span>{{ apiState.status }}</span>
        </div>

        <div v-if="apiState.loading" class="feature-list">
          <span>Requesting `/api/meta` and `/api/health` from Laravel...</span>
        </div>

        <div v-else-if="apiState.error" class="feature-list">
          <span>Connection error: {{ apiState.error }}</span>
        </div>

        <div v-else class="feature-list">
          <strong>{{ apiState.name }} {{ apiState.version }}</strong>
          <span>Frontend now talks to Laravel through the shared `/api` entrypoint.</span>
          <ul class="feature-list">
            <li v-for="feature in apiState.features" :key="feature">{{ feature }}</li>
          </ul>
        </div>
      </section>
    </div>
  </section>
</template>
