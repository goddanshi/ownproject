import axios from 'axios'

const API_URL = import.meta.env.DEV
  ? 'http://localhost:8000'
  : 'http://81.19.136.133:8000'

const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json'
  }
})

// Interceptor для добавления токена в каждый запрос
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('jwt_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Interceptor для обработки ошибок авторизации
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config

    // Если 401 и это не refresh запрос
    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true

      try {
        // Пытаемся обновить токен
        const response = await api.post('/api/refresh')
        if (response.data.success) {
          localStorage.setItem('jwt_token', response.data.token)
          originalRequest.headers.Authorization = `Bearer ${response.data.token}`
          return api(originalRequest)
        }
      } catch (refreshError) {
        // Если refresh не сработал - разлогиниваем
        localStorage.removeItem('jwt_token')
        window.location.href = '/login'
        return Promise.reject(refreshError)
      }
    }

    return Promise.reject(error)
  }
)

export default {
  async register(username, email, password) {
    const response = await api.post('/api/register', {
      username,
      email,
      password
    })

    if (response.data.success && response.data.token) {
      localStorage.setItem('jwt_token', response.data.token)
    }

    return response.data
  },

  async login(username, password) {
    const response = await api.post('/api/login', {
      username,
      password
    })

    if (response.data.success && response.data.token) {
      localStorage.setItem('jwt_token', response.data.token)
    }

    return response.data
  },

  async checkAuth() {
    const token = localStorage.getItem('jwt_token')
    if (!token) {
      return { success: false, message: 'No token' }
    }

    const response = await api.get('/api/check')
    return response.data
  },

  async logout() {
    const response = await api.post('/api/logout')
    localStorage.removeItem('jwt_token')
    return response.data
  },

  async getProfile() {
    const response = await api.get('/user/profile')
    return response.data
  },

  async updateProfile(name, surname, email) {
    const response = await api.post('/user/update-profile', {
      name,
      surname,
      email
    })
    return response.data
  },

  async changePassword(oldPassword, newPassword) {
    const response = await api.post('/user/change-password', {
      oldPassword,
      newPassword
    })
    return response.data
  }
}
