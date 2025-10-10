import axios from 'axios'

const API_URL = 'http://81.19.136.133:8000'

const api = axios.create({
  baseURL: API_URL,
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json'
  }
})

export default {
  async login(username, password) {
    const response = await api.post('/api/login', {
      username,
      password
    })
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
