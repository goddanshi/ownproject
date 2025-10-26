import api from './api'

export default {
  // Получить список команд
  async getTeams() {
    const response = await api.get('/teams/index')
    return response.data
  },

  // Получить детали команды
  async getTeam(id) {
    const response = await api.get('/teams/view', {
      params: {id}
    })
    return response.data
  },

  // Создать команду
  async createTeam(name, description, teamleadId, memberIds = []) {
    const response = await api.post('/teams/create', {
      name,
      description,
      teamlead_id: teamleadId,
      member_ids: memberIds
    })
    return response.data
  },

  // Обновить команду
  async updateTeam(id, name, description, teamleadId = null) {
    const response = await api.post('/teams/update', {
      id,
      name,
      description,
      teamlead_id: teamleadId
    })
    return response.data
  },

  // Удалить команду
  async deleteTeam(id) {
    const response = await api.delete('/teams/delete', {
      params: {id}
    })
    return response.data
  },

  // Добавить участника
  async addMember(teamId, userId) {
    const response = await api.post('/teams/add-member', {
      team_id: teamId,
      user_id: userId
    })
    return response.data
  },

  // Удалить участника
  async removeMember(teamId, userId) {
    const response = await api.post('/teams/remove-member', {
      team_id: teamId,
      user_id: userId
    })
    return response.data
  },

  // Получить список тимлидов
  async getTeamleads() {
    const response = await api.get('/teams/get-teamleads')
    return response.data
  },
  async getEmployees() {
    const response = await api.get('/teams/get-employees')
    return response.data
  },
}
//
