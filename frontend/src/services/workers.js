import api from './api'

export default {
  // Получить список работников
  async getWorkers() {
    const response = await api.get('/workers/index')
    return response.data
  },

  // Получить детали работника
  async getWorker(id) {
    const response = await api.get('/workers/view', {
      params: { id }
    })
    return response.data
  },

  // Создать работника
  async createWorker(username, email, password, role, name = '', surname = '') {
    const response = await api.post('/workers/create', {
      username,
      email,
      password,
      role,
      name,
      surname
    })
    return response.data
  },

  // Обновить работника
  async updateWorker(id, username, email, role, name = '', surname = '') {
    const response = await api.post('/workers/update', {
      id,
      username,
      email,
      role,
      name,
      surname
    })
    return response.data
  },

  // Удалить работника
  async deleteWorker(id) {
    const response = await api.delete('/workers/delete', {
      params: { id }
    })
    return response.data
  },

  // Изменить пароль работника
  async changeWorkerPassword(id, newPassword) {
    const response = await api.post('/workers/change-password', {
      id,
      new_password: newPassword
    })
    return response.data
  },

  // Обновить дату регистрации работника
  async updateCreatedAt(id, createdAt) {
    const response = await api.post('/workers/update-created-at', {
      id,
      created_at: createdAt
    })
    return response.data
  }
}
