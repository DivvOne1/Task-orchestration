import { defineStore } from 'pinia'

import { api } from '../lib/api'

const TOKEN_KEY = 'taskflow_token'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem(TOKEN_KEY) || '',
    user: null,
    initialized: false,
  }),
  getters: {
    isAuthenticated: (state) => Boolean(state.token && state.user),
  },
  actions: {
    setToken(token) {
      this.token = token

      if (token) {
        localStorage.setItem(TOKEN_KEY, token)
        api.defaults.headers.common.Authorization = `Bearer ${token}`
      } else {
        localStorage.removeItem(TOKEN_KEY)
        delete api.defaults.headers.common.Authorization
      }
    },
    async restore() {
      if (this.token) {
        api.defaults.headers.common.Authorization = `Bearer ${this.token}`

        try {
          await this.fetchMe()
        } catch {
          this.setToken('')
          this.user = null
        }
      }

      this.initialized = true
    },
    async fetchMe() {
      const { data } = await api.get('/me')
      this.user = data
      return data
    },
    async register(payload) {
      const { data } = await api.post('/register', payload)
      this.setToken(data.token)
      this.user = data.user
      return data
    },
    async login(payload) {
      const { data } = await api.post('/login', payload)
      this.setToken(data.token)
      this.user = data.user
      return data
    },
    async logout() {
      try {
        if (this.token) {
          await api.post('/logout')
        }
      } finally {
        this.setToken('')
        this.user = null
      }
    },
  },
})
