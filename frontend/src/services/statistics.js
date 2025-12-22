import api from './api'

export default {
  // Получить статистику по задачам
  async getTasksStatistics(filters = {}) {
    try {
      const params = new URLSearchParams()

      if (filters.project_id) params.append('project_id', filters.project_id)
      if (filters.user_id) params.append('user_id', filters.user_id)
      if (filters.date_from) params.append('date_from', filters.date_from)
      if (filters.date_to) params.append('date_to', filters.date_to)

      const response = await api.get(`/api/statistics/tasks?${params.toString()}`)
      return response.data
    } catch (error) {
      console.error('Ошибка получения статистики по задачам:', error)
      throw error
    }
  },

  // Получить статистику по проектам
  async getProjectsStatistics(filters = {}) {
    try {
      const params = new URLSearchParams()

      if (filters.date_from) params.append('date_from', filters.date_from)
      if (filters.date_to) params.append('date_to', filters.date_to)

      const response = await api.get(`/api/statistics/projects?${params.toString()}`)
      return response.data
    } catch (error) {
      console.error('Ошибка получения статистики по проектам:', error)
      throw error
    }
  },

  // Получить статистику по сотрудникам
  async getEmployeesStatistics(filters = {}) {
    try {
      const params = new URLSearchParams()

      if (filters.user_id) params.append('user_id', filters.user_id)
      if (filters.project_id) params.append('project_id', filters.project_id)
      if (filters.date_from) params.append('date_from', filters.date_from)
      if (filters.date_to) params.append('date_to', filters.date_to)

      const response = await api.get(`/api/statistics/employees?${params.toString()}`)
      return response.data
    } catch (error) {
      console.error('Ошибка получения статистики по сотрудникам:', error)
      throw error
    }
  },

  // Получить отчет о превышениях времени
  async getOverrunsStatistics(filters = {}) {
    try {
      const params = new URLSearchParams()

      if (filters.project_id) params.append('project_id', filters.project_id)
      if (filters.user_id) params.append('user_id', filters.user_id)
      if (filters.date_from) params.append('date_from', filters.date_from)
      if (filters.date_to) params.append('date_to', filters.date_to)

      const response = await api.get(`/api/statistics/overruns?${params.toString()}`)
      return response.data
    } catch (error) {
      console.error('Ошибка получения отчета о превышениях:', error)
      throw error
    }
  }
}
