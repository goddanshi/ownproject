import api from './api'

export default {
  // Получить все доступные права (только для админа)
  async getPermissions() {
    const response = await api.get('/settings/permissions')
    return response.data
  },

  // Получить права для роли
  async getRolePermissions(role) {
    const response = await api.get('/settings/role-permissions', {
      params: { role }
    })
    return response.data
  },

  // Обновить права роли (только для админа)
  async updateRolePermissions(role, permissions) {
    const response = await api.post('/settings/update-role-permissions', {
      role,
      permissions
    })
    return response.data
  }
}
