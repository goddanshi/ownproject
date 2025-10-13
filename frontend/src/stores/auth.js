import { defineStore } from 'pinia'
import api from '../services/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    isAuthenticated: false,
    loading: false,
    checked: false // Флаг что уже проверяли
  }),

  actions: {
    async checkAuth() {
      // Если уже проверяли - не проверяем снова
      if (this.checked) {
        return
      }

      const token = localStorage.getItem('jwt_token')

      if (!token) {
        this.user = null
        this.isAuthenticated = false
        this.checked = true
        return
      }

      this.loading = true
      try {
        const result = await api.checkAuth()
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
      const result = await api.login(username, password)
      if (result.success) {
        this.user = result.user
        this.isAuthenticated = true
        this.checked = true
      }
      return result
    },

    async register(username, email, password) {
      const result = await api.register(username, email, password)
      if (result.success) {
        this.user = result.user
        this.isAuthenticated = true
        this.checked = true
      }
      return result
    },

    async logout() {
      await api.logout()
      this.user = null
      this.isAuthenticated = false
      this.checked = false
    }
  }
})
