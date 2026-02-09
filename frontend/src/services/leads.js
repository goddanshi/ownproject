import api from './api'

export default {
  // Получить список всех лидов
  async getLeads(filters = {}) {
    const params = {}
    if (filters.managerId) params.manager_id = filters.managerId
    if (filters.dateFrom) params.date_from = filters.dateFrom
    if (filters.dateTo) params.date_to = filters.dateTo

    const response = await api.get('/leads', { params })
    return response.data
  },

  // Получить один лид
  async getLead(id) {
    const response = await api.get(`/leads/${id}`)
    return response.data
  },

  // Создать лид
  async createLead(leadData) {
    const response = await api.post('/leads/create', {
      date: leadData.date,
      website: leadData.website,
      channel: leadData.channel,
      contact_type: leadData.contactType,
      contact_value: leadData.contactValue,
      audit_info: leadData.auditInfo,
      audit_status: leadData.auditStatus,
      proposal_info: leadData.proposalInfo,
      proposal_status: leadData.proposalStatus,
      price: leadData.price,
      status: leadData.status,
      contact_date: leadData.contactDate,
      comment: leadData.comment,
      manager_id: leadData.managerId
    })
    return response.data
  },

  // Обновить лид
  async updateLead(id, leadData) {
    const response = await api.post(`/leads/update?id=${id}`, {
      date: leadData.date,
      website: leadData.website,
      channel: leadData.channel,
      contact_type: leadData.contactType,
      contact_value: leadData.contactValue,
      audit_info: leadData.auditInfo,
      audit_status: leadData.auditStatus,
      proposal_info: leadData.proposalInfo,
      proposal_status: leadData.proposalStatus,
      price: leadData.price,
      status: leadData.status,
      contact_date: leadData.contactDate,
      comment: leadData.comment,
      manager_id: leadData.managerId
    })
    return response.data
  },

  // Удалить лид
  async deleteLead(id) {
    const response = await api.post(`/leads/delete?id=${id}`)
    return response.data
  },

  // Получить список менеджеров
  async getManagers() {
    const response = await api.get('/leads/get-managers')
    return response.data
  },

  // Получить список каналов (для автодополнения)
  async getChannels() {
    const response = await api.get('/leads/get-channels')
    return response.data
  },

  // Экспорт в Excel
  exportToExcel(filters = {}) {
    const params = new URLSearchParams()
    if (filters.managerId) params.append('manager_id', filters.managerId)
    if (filters.dateFrom) params.append('date_from', filters.dateFrom)
    if (filters.dateTo) params.append('date_to', filters.dateTo)

    const API_URL = import.meta.env.DEV
      ? 'http://localhost:8000'
      : 'http://91.218.245.170:8000'

    const token = localStorage.getItem('jwt_token')
    const url = `${API_URL}/leads/export?${params.toString()}`

    // Создаем временную ссылку для скачивания
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `leads_${new Date().getTime()}.xlsx`)

    // Добавляем токен в fetch запрос
    fetch(url, {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    })
    .then(response => response.blob())
    .then(blob => {
      const url = window.URL.createObjectURL(blob)
      link.href = url
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(url)
    })
  }
}
