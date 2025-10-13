import axios from 'axios'

const API_URL = 'http://81.19.136.133:8000'

const api = axios.create({
  baseURL: API_URL,
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json'
  }
})

// 🔒 автоматически добавляем токен ко всем запросам
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

export default {
  async register(username, email, password) {
    const response = await api.post('/api/register', {
      username,
      email,
      password
    })
    return response.data
  },

  async login(username, password) {
    const response = await api.post('/api/login', {
      username,
      password
    })
    // ожидаем что бек вернёт { success: true, token, user }
    return response.data
  },

  async checkAuth() {
    const response = await api.get('/api/check')
    return response.data
  },

  async logout() {
    const response = await api.post('/api/logout')
    return response.data
  }
}
