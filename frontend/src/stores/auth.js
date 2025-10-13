import { defineStore } from 'pinia'
import api from '../services/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    isAuthenticated: false,
    loading: false,
    token: localStorage.getItem('token') || null
  }),

  actions: {
    async checkAuth() {
      if (!this.token) {
        this.isAuthenticated = false
        this.user = null
        return
      }

      this.loading = true
      try {
        const result = await api.checkAuth()
        if (result.success) {
          this.user = result.user
          this.isAuthenticated = true
        } else {
          this.logout()
        }
      } catch (error) {
        this.logout()
      } finally {
        this.loading = false
      }
    },

    async login(username, password) {
      const result = await api.login(username, password)
      if (result.success && result.token) {
        this.token = result.token
        localStorage.setItem('token', result.token)
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
      localStorage.removeItem('token')
      this.token = null
      this.user = null
      this.isAuthenticated = false
      try {
        await api.logout()
      } catch (e) {

      }
    }
  }
})
