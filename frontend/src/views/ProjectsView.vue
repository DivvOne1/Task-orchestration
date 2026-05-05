<script setup>
import { onMounted, ref } from 'vue'

import { api } from '../lib/api'

const projects = ref([])
const loading = ref(true)
const error = ref('')
const editingId = ref(null)
const form = ref({
  title: '',
  description: '',
  status: 'active',
})

async function loadProjects() {
  loading.value = true
  error.value = ''

  try {
    const { data } = await api.get('/projects')
    projects.value = data
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load projects.'
  } finally {
    loading.value = false
  }
}

function resetForm() {
  editingId.value = null
  form.value = {
    title: '',
    description: '',
    status: 'active',
  }
}

function startEdit(project) {
  editingId.value = project.id
  form.value = {
    title: project.title,
    description: project.description ?? '',
    status: project.status,
  }
}

async function submit() {
  error.value = ''

  try {
    if (editingId.value) {
      await api.put(`/projects/${editingId.value}`, form.value)
    } else {
      await api.post('/projects', form.value)
    }

    resetForm()
    await loadProjects()
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to save project.'
  }
}

async function removeProject(id) {
  error.value = ''

  try {
    await api.delete(`/projects/${id}`)
    await loadProjects()
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to delete project.'
  }
}

onMounted(loadProjects)
</script>

<template>
  <section class="page">
    <div class="page-head">
      <div>
        <p class="eyebrow">Projects</p>
        <h2>Manage your projects</h2>
      </div>
      <button class="ghost-button" type="button" @click="resetForm">New form</button>
    </div>

    <div class="panel">
      <form class="form-grid" @submit.prevent="submit">
        <input v-model="form.title" class="input" type="text" placeholder="Project title" />
        <input v-model="form.description" class="input" type="text" placeholder="Description" />
        <select v-model="form.status" class="input">
          <option value="active">active</option>
          <option value="archived">archived</option>
          <option value="completed">completed</option>
        </select>
        <p v-if="error" class="form-error">{{ error }}</p>
        <div class="action-row">
          <button class="primary-button" type="submit">
            {{ editingId ? 'Update project' : 'Create project' }}
          </button>
          <button v-if="editingId" class="ghost-button" type="button" @click="resetForm">Cancel</button>
        </div>
      </form>
    </div>

    <div class="panel">
      <div class="table-head">
        <span>Title</span>
        <span>Status</span>
        <span>Owner</span>
        <span>Actions</span>
      </div>

      <div v-if="loading" class="table-row">
        <span>Loading projects...</span>
      </div>

      <article v-for="project in projects" :key="project.id" class="table-row">
        <div>
          <strong>{{ project.title }}</strong>
          <p>{{ project.description || 'No description' }}</p>
        </div>
        <span class="tag">{{ project.status }}</span>
        <span>{{ project.owner?.name }}</span>
        <div class="action-row">
          <button class="ghost-button" type="button" @click="startEdit(project)">Edit</button>
          <button class="ghost-button" type="button" @click="removeProject(project.id)">Delete</button>
        </div>
      </article>
    </div>
  </section>
</template>
