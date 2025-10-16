import api from './api'

export default {
  async getPermissions() {
    const response = await api.get('/settings/permissions')
    return response.data
  },

  async getRolePermissions(role) {
    const response = await api.get('/settings/role-permissions', {
      params: { role }
    })
    return response.data
  },

  async updateRolePermissions(role, permissions) {
    const response = await api.post('/settings/update-role-permissions', {
      role,
      permissions
    })
    return response.data
  }
}
