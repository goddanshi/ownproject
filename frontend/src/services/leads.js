import api from './api'

export default {
  // Получить список всех лидов
  async getLeads() {
    const response = await api.get('/leads')
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
      contact_type: leadData.contactType,
      contact_value: leadData.contactValue,
      audit_info: leadData.auditInfo,
      audit_status: leadData.auditStatus,
      proposal_info: leadData.proposalInfo,
      proposal_status: leadData.proposalStatus,
      price: leadData.price,
      status: leadData.status,
      contact_date: leadData.contactDate,
      comment: leadData.comment
    })
    return response.data
  },

  // Обновить лид
  async updateLead(id, leadData) {
    const response = await api.post(`/leads/update?id=${id}`, {
      date: leadData.date,
      website: leadData.website,
      contact_type: leadData.contactType,
      contact_value: leadData.contactValue,
      audit_info: leadData.auditInfo,
      audit_status: leadData.auditStatus,
      proposal_info: leadData.proposalInfo,
      proposal_status: leadData.proposalStatus,
      price: leadData.price,
      status: leadData.status,
      contact_date: leadData.contactDate,
      comment: leadData.comment
    })
    return response.data
  },

  // Удалить лид
  async deleteLead(id) {
    const response = await api.post(`/leads/delete?id=${id}`)
    return response.data
  }
}
