import axios from 'axios'

const API_URL = import.meta.env.DEV
  ? 'http://localhost:8000'
  : 'http://185.213.240.236:8000'

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
  console.log('🔵 API Request:', {
    method: config.method,
    url: config.url,
    baseURL: config.baseURL,
    fullURL: `${config.baseURL}${config.url}`,
    data: config.data,
    headers: config.headers
  })
  return config
}, (error) => {
  console.error('🔴 API Request Error:', error)
  return Promise.reject(error)
})

// Interceptor для обработки ошибок авторизации
api.interceptors.response.use(
  (response) => {
    console.log('🟢 API Response:', {
      status: response.status,
      url: response.config.url,
      data: response.data
    })
    return response
  },
  async (error) => {
    console.error('🔴 API Response Error:', {
      message: error.message,
      status: error.response?.status,
      statusText: error.response?.statusText,
      url: error.config?.url,
      data: error.response?.data,
      headers: error.response?.headers
    })

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

export default api
