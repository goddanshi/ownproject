import api from './api'

export default {
  // Получить список задач
  async getTasks() {
    const response = await api.get('/tasks')
    return response.data
  },

  // Получить детали задачи
  async getTask(id) {
    const response = await api.get(`/tasks/${id}`)
    return response.data
  },

  // Создать задачу
  async createTask(taskData) {
    const response = await api.post('/tasks/create', {
      title: taskData.title,
      description: taskData.description,
      project_id: taskData.projectId,
      status: taskData.status,
      priority: taskData.priority,
      deadline: taskData.deadline,
      assignee_ids: taskData.assigneeIds || []
    })
    return response.data
  },

  // Обновить задачу
  async updateTask(id, taskData) {
    const response = await api.post('/tasks/update', {
      id,
      title: taskData.title,
      description: taskData.description,
      status: taskData.status,
      priority: taskData.priority,
      deadline: taskData.deadline
    })
    return response.data
  },

  // Удалить задачу
  async deleteTask(id) {
    const response = await api.delete(`/tasks/${id}`)
    return response.data
  },

  // Назначить пользователя на задачу
  async assignUser(taskId, userId) {
    const response = await api.post('/tasks/assign-user', {
      task_id: taskId,
      user_id: userId
    })
    return response.data
  },

  // Снять назначение с пользователя
  async unassignUser(taskId, userId) {
    const response = await api.post('/tasks/unassign-user', {
      task_id: taskId,
      user_id: userId
    })
    return response.data
  },

  // Начать отслеживание времени
  async startTracking(taskId, description = '') {
    const response = await api.post('/tasks/start-tracking', {
      task_id: taskId,
      description
    })
    return response.data
  },

  // Остановить отслеживание времени
  async stopTracking(taskId) {
    const response = await api.post('/tasks/stop-tracking', {
      task_id: taskId
    })
    return response.data
  }
}
