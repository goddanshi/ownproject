import { defineStore } from 'pinia'
import api from '../services/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    isAuthenticated: false,
    loading: false
  }),

  actions: {
    async checkAuth() {
      this.loading = true
      try {
        const result = await api.checkAuth()
        if (result.success) {
          this.user = result.user
          this.isAuthenticated = true
        } else {
          this.user = null
          this.isAuthenticated = false
        }
      } catch (error) {
        this.user = null
        this.isAuthenticated = false
      } finally {
        this.loading = false
      }
    },

    async login(username, password) {
      const result = await api.login(username, password)
      if (result.success) {
        this.user = result.user
        this.isAuthenticated = true
      }
      return result
    },

    async register(username, email, password) {
      const result = await api.register(username, email, password)
      return result
    },

    async logout() {
      await api.logout()
      this.user = null
      this.isAuthenticated = false
    }
  }
})
