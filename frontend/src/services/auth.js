import api from './api'

export default {
  async register(username, email, password, name, surname) {
    const response = await api.post('/api/register', {
      username,
      email,
      password,
      name,
      surname
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
  }
}


