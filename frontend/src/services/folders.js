import api from './api'

const foldersApi = {
  // Получить все папки
  async getFolders() {
    try {
      const response = await api.get('/folders')
      return response.data
    } catch (error) {
      console.error('Ошибка получения папок:', error)
      throw error
    }
  },

  // Получить дерево папок
  async getFoldersTree(teamId = null) {
    try {
      const params = teamId ? { teamId } : {}
      const response = await api.get('/folders/tree', { params })
      return response.data
    } catch (error) {
      console.error('Ошибка получения дерева папок:', error)
      throw error
    }
  },

  // Получить одну папку
  async getFolder(id) {
    try {
      const response = await api.get(`/folders/${id}`)
      return response.data
    } catch (error) {
      console.error('Ошибка получения папки:', error)
      throw error
    }
  },

  // Создать папку
  async createFolder(data) {
    try {
      const response = await api.post('/folders/create', data)
      return response.data
    } catch (error) {
      console.error('Ошибка создания папки:', error)
      throw error
    }
  },

  // Обновить папку
  async updateFolder(id, data) {
    try {
      const response = await api.post(`/folders/update?id=${id}`, data)
      return response.data
    } catch (error) {
      console.error('Ошибка обновления папки:', error)
      throw error
    }
  },

  // Удалить папку
  async deleteFolder(id) {
    try {
      const response = await api.delete(`/folders/${id}`)
      return response.data
    } catch (error) {
      console.error('Ошибка удаления папки:', error)
      throw error
    }
  }
}

export default foldersApi
