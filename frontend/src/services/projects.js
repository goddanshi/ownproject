import api from './api'

export default {
  // Получить список проектов
  async getProjects() {
    const response = await api.get('/projects')
    return response.data
  },

  // Получить детали проекта
  async getProject(id) {
    const response = await api.get(`/projects/${id}`)
    return response.data
  },

  // Создать проект
  async createProject(data) {
    const response = await api.post('/projects/create', data)
    return response.data
  },

  // Обновить проект
  async updateProject(id, data) {
    const response = await api.post(`/projects/update?id=${id}`, data)
    return response.data
  },

  // Удалить проект
  async deleteProject(id) {
    const response = await api.delete(`/projects/${id}`)
    return response.data
  },

  // Изменить порядок проектов
  async reorderProjects(orderedIds) {
    const response = await api.post('/projects/reorder', {
      ordered_ids: orderedIds
    })
    return response.data
  }
}
