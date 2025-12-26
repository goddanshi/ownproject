import axios from 'axios'

// Всегда используем production сервер аналитики
const ANALYTICS_API_URL = 'http://185.104.113.132:9999'

const analyticsApi = axios.create({
  baseURL: ANALYTICS_API_URL,
  headers: {
    'Content-Type': 'application/json'
  }
})

export default {
  // Получить все проекты
  getAllProjects() {
    return analyticsApi.get('/projects/')
  },

  // Получить проекты по команде
  getProjectsByTeam(teamName) {
    return analyticsApi.get(`/projects/by_team/${teamName}`)
  },

  // Получить конкретный проект
  getProject(projectId) {
    return analyticsApi.get(`/projects/${projectId}`)
  },

  // Создать или обновить проект
  updateProject(projectId, data) {
    return analyticsApi.put(`/projects/${projectId}`, data)
  },

  // Удалить проект
  deleteProject(projectId) {
    return analyticsApi.delete(`/projects/${projectId}`)
  },

  // Скачать Excel
  downloadExcel() {
    return analyticsApi.get('/projects/as_xlsx', {
      responseType: 'blob'
    })
  }
}
