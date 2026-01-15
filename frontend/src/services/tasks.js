import api from './api'

export default {
  // Получить список задач
  async getTasks() {
    const response = await api.get('/tasks')
    return response.data
  },

  // Получить список активных задач (статусы 1, 2, 3)
  async getActiveTasks() {
    const response = await api.get('/tasks/active')
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
      start_date: taskData.start_date,
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
      start_date: taskData.start_date,
      deadline: taskData.deadline,
      estimated_time: taskData.estimated_time,
      reviewer_id: taskData.reviewer_id
    })
    return response.data
  },

  // Обновить статус задачи
  async updateTaskStatus(taskId, status) {
    const response = await api.post('/tasks/update-status', {
      task_id: taskId,
      status
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
  },

  // === Методы чата ===

  // Получить все сообщения чата задачи
  async getAllChatMessages(taskId, limit = 50) {
    const response = await api.get('/tasks/get-all-messages', {
      params: {
        task_id: taskId,
        limit
      }
    })
    return response.data
  },

  // Получить новые сообщения (long polling)
  async getChatMessages(taskId, lastMessageId = null, timeout = 30) {
    const response = await api.get('/tasks/get-messages', {
      params: {
        task_id: taskId,
        last_message_id: lastMessageId,
        timeout
      }
    })
    return response.data
  },

  // Отправить сообщение в чат
  async sendChatMessage(taskId, message) {
    const response = await api.post('/tasks/send-message', {
      task_id: taskId,
      message
    })
    return response.data
  },

  // === Методы TODO ===

  // Создать TODO
  async createTodo(taskId, title, deadline = null) {
    const response = await api.post('/tasks/create-todo', {
      task_id: taskId,
      title,
      deadline
    })
    return response.data
  },

  // Обновить TODO
  async updateTodo(todoId, data) {
    const response = await api.post('/tasks/update-todo', {
      id: todoId,
      ...data
    })
    return response.data
  },

  // Переключить статус TODO
  async toggleTodo(todoId) {
    const response = await api.post('/tasks/toggle-todo', {
      id: todoId
    })
    return response.data
  },

  // Удалить TODO
  async deleteTodo(todoId) {
    const response = await api.delete('/tasks/delete-todo', {
      params: { id: todoId }
    })
    return response.data
  }
}
