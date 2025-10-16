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

          // Загружаем права пользователя
          await this.loadUserPermissions()
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

    async loadUserPermissions() {
      try {
        const settingsApi = (await import('@/services/settings')).default
        const result = await settingsApi.getRolePermissions(this.user.role)

        if (result.success) {
          this.user.permissions = result.permissions
        }
      } catch (error) {
        console.error('Failed to load permissions:', error)
        this.user.permissions = []
      }
    },

    async login(username, password) {
      const result = await authApi.login(username, password)
      if (result.success) {
        this.user = result.user
        this.isAuthenticated = true
        this.checked = true
        await this.loadUserPermissions()
      }
      return result
    },

    async register(username, email, password) {
      const result = await authApi.register(username, email, password)
      if (result.success) {
        this.user = result.user
        this.isAuthenticated = true
        this.checked = true
        await this.loadUserPermissions()
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
