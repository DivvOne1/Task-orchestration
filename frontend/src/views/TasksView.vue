<script setup>
import { computed, onMounted, ref } from 'vue'

import { api } from '../lib/api'

const tasks = ref([])
const projects = ref([])
const loading = ref(true)
const error = ref('')
const editingId = ref(null)
const filters = ref({
  search: '',
  status: '',
  priority: '',
})
const form = ref({
  project_id: '',
  title: '',
  description: '',
  status: 'todo',
  priority: 'medium',
  assignee_id: '',
  deadline: '',
})

const filteredTasks = computed(() =>
  tasks.value.filter((task) => {
    const matchesSearch = !filters.value.search || task.title.toLowerCase().includes(filters.value.search.toLowerCase())
    const matchesStatus = !filters.value.status || task.status === filters.value.status
    const matchesPriority = !filters.value.priority || task.priority === filters.value.priority

    return matchesSearch && matchesStatus && matchesPriority
  }),
)

async function loadProjects() {
  const { data } = await api.get('/projects')
  projects.value = data
}

async function loadTasks() {
  loading.value = true
  error.value = ''

  try {
    const { data } = await api.get('/tasks')
    tasks.value = data
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load tasks.'
  } finally {
    loading.value = false
  }
}

function resetForm() {
  editingId.value = null
  form.value = {
    project_id: projects.value[0]?.id ?? '',
    title: '',
    description: '',
    status: 'todo',
    priority: 'medium',
    assignee_id: '',
    deadline: '',
  }
}

function startEdit(task) {
  editingId.value = task.id
  form.value = {
    project_id: task.project_id,
    title: task.title,
    description: task.description ?? '',
    status: task.status,
    priority: task.priority,
    assignee_id: task.assignee_id ?? '',
    deadline: task.deadline ? task.deadline.slice(0, 16) : '',
  }
}

async function submit() {
  error.value = ''

  const payload = {
    ...form.value,
    assignee_id: form.value.assignee_id || null,
    deadline: form.value.deadline || null,
  }

  try {
    if (editingId.value) {
      await api.put(`/tasks/${editingId.value}`, payload)
    } else {
      await api.post('/tasks', payload)
    }

    resetForm()
    await loadTasks()
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to save task.'
  }
}

async function removeTask(id) {
  error.value = ''

  try {
    await api.delete(`/tasks/${id}`)
    await loadTasks()
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to delete task.'
  }
}

onMounted(async () => {
  await loadProjects()
  resetForm()
  await loadTasks()
})
</script>

<template>
  <section class="page">
    <div class="page-head">
      <div>
        <p class="eyebrow">Tasks</p>
        <h2>Manage tasks</h2>
      </div>
      <button class="ghost-button" type="button" @click="resetForm">New form</button>
    </div>

    <div class="panel">
      <form class="form-grid" @submit.prevent="submit">
        <select v-model="form.project_id" class="input">
          <option disabled value="">Project</option>
          <option v-for="project in projects" :key="project.id" :value="project.id">{{ project.title }}</option>
        </select>
        <input v-model="form.title" class="input" type="text" placeholder="Task title" />
        <input v-model="form.description" class="input" type="text" placeholder="Description" />
        <select v-model="form.status" class="input">
          <option value="todo">todo</option>
          <option value="in_progress">in_progress</option>
          <option value="review">review</option>
          <option value="done">done</option>
          <option value="cancelled">cancelled</option>
        </select>
        <select v-model="form.priority" class="input">
          <option value="low">low</option>
          <option value="medium">medium</option>
          <option value="high">high</option>
          <option value="critical">critical</option>
        </select>
        <input v-model="form.deadline" class="input" type="datetime-local" />
        <p v-if="error" class="form-error">{{ error }}</p>
        <div class="action-row">
          <button class="primary-button" type="submit">
            {{ editingId ? 'Update task' : 'Create task' }}
          </button>
          <button v-if="editingId" class="ghost-button" type="button" @click="resetForm">Cancel</button>
        </div>
      </form>
    </div>

    <div class="filters">
      <input v-model="filters.search" class="input" type="text" placeholder="Search tasks by title" />
      <select v-model="filters.status" class="input">
        <option value="">Status</option>
        <option>todo</option>
        <option>in_progress</option>
        <option>review</option>
        <option>done</option>
      </select>
      <select v-model="filters.priority" class="input">
        <option value="">Priority</option>
        <option>low</option>
        <option>medium</option>
        <option>high</option>
        <option>critical</option>
      </select>
    </div>

    <div class="panel">
      <div v-if="loading" class="table-row">
        <span>Loading tasks...</span>
      </div>

      <article v-for="task in filteredTasks" :key="task.id" class="table-row">
        <div>
          <strong>{{ task.title }}</strong>
          <p>{{ task.project?.title }} · {{ task.creator?.name }}</p>
        </div>
        <span class="tag">{{ task.priority }}</span>
        <span class="tag">{{ task.status }}</span>
        <div class="action-row">
          <button class="ghost-button" type="button" @click="startEdit(task)">Edit</button>
          <button class="ghost-button" type="button" @click="removeTask(task.id)">Delete</button>
        </div>
      </article>
    </div>
  </section>
</template>
