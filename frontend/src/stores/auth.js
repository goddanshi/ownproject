import { defineStore } from 'pinia'
import authApi from '@/services/auth'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    isAuthenticated: false,
    loading: false,
    checked: false,
    permissionsLoading: false
  }),

  persist: {
    storage: localStorage,
    paths: ['isAuthenticated', 'checked'],
    // Исключаем user из persist, чтобы permissions не сохранялись в localStorage
    // Базовые данные пользователя будем получать из checkAuth
  },

  getters: {
    can: (state) => (permission) => {
      if (!state.user || !state.user.permissions) return false
      return state.user.permissions.includes(permission)
    },

    isAdmin: (state) => state.user?.role === 1,
    isTeamlead: (state) => state.user?.role === 2,
    isEmployee: (state) => state.user?.role === 3,
  },

  actions: {
    async checkAuth() {
      const token = localStorage.getItem('jwt_token')
      if (!token) {
        this.user = null
        this.isAuthenticated = false
        this.checked = true
        return
      }

      // Если уже checked и есть user, всегда загружаем свежие права
      if (this.checked && this.user) {
        await this.loadUserPermissions()
        return
      }

      this.loading = true
      try {
        const result = await authApi.checkAuth()
        if (result.success) {
          this.user = result.user
          this.isAuthenticated = true

          try {
            await this.loadUserPermissions()
          } catch (error) {
            console.warn('Failed to load permissions, continuing without them')
            this.user.permissions = []
          }
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

    // Новый метод для периодической проверки прав
    async refreshPermissions() {
      if (!this.isAuthenticated || !this.user) return

      // Не запускаем если уже идет загрузка
      if (this.permissionsLoading) return

      this.permissionsLoading = true
      try {
        await this.loadUserPermissions()
      } catch (error) {
        console.error('Failed to refresh permissions:', error)
      } finally {
        this.permissionsLoading = false
      }
    },

    async loadUserPermissions() {
      if (!this.user) return

      try {
        const { default: settingsApi } = await import('@/services/settings')
        const result = await settingsApi.getRolePermissions(this.user.role)

        if (result.success) {
          this.user.permissions = result.permissions || []
        } else {
          this.user.permissions = []
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

        try {
          await this.loadUserPermissions()
        } catch (error) {
          this.user.permissions = []
        }
      }
      return result
    },

    async register(username, email, password, name, surname) {
      const result = await authApi.register(username, email, password, name, surname)
      if (result.success) {
        this.user = result.user
        this.isAuthenticated = true
        this.checked = true

        try {
          await this.loadUserPermissions()
        } catch (error) {
          this.user.permissions = []
        }
      }
      return result
    },

    async logout() {
      await authApi.logout()
      this.user = null
      this.isAuthenticated = false
      this.checked = false
    },

    updateUser(userData) {
      if (this.user) {
        this.user = { ...this.user, ...userData }
      }
    }
  }
})
