import { defineStore } from 'pinia'
import authApi from '@/services/auth'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    isAuthenticated: false,
    loading: false,
    checked: false
  }),

  actions: {
    async checkAuth() {
      if (this.checked) return

      const token = localStorage.getItem('jwt_token')
      if (!token) {
        this.user = null
        this.isAuthenticated = false
        this.checked = true
        return
      }

      this.loading = true
      try {
        const result = await authApi.checkAuth()
        if (result.success) {
          this.user = result.user
          this.isAuthenticated = true
        } else {
          this.user = null
          this.isAuthenticated = false
          localStorage.removeItem('jwt_token')
        }
      } catch (error) {
        this.user = null
        this.isAuthenticated = false
        localStorage.removeItem('jwt_token')
      } finally {
        this.loading = false
        this.checked = true
      }
    },

    async login(username, password) {
      const result = await authApi.login(username, password)
      if (result.success) {
        this.user = result.user
        this.isAuthenticated = true
        this.checked = true
      }
      return result
    },

    async register(username, email, password) {
      const result = await authApi.register(username, email, password)
      if (result.success) {
        this.user = result.user
        this.isAuthenticated = true
        this.checked = true
      }
      return result
    },

    async logout() {
      await authApi.logout()
      this.user = null
      this.isAuthenticated = false
      this.checked = false
    }
  }
})
