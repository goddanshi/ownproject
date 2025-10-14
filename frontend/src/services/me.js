import api from './api'

export default {
  async getProfile() {
    const response = await api.get('/user/profile')
    return response.data
  },

  async updateProfile(name, surname, email) {
    const response = await api.post('/user/update-profile', {
      name,
      surname,
      email
    })
    return response.data
  },

  async changePassword(oldPassword, newPassword) {
    const response = await api.post('/user/change-password', {
      oldPassword,
      newPassword
    })

    // Если пароль изменён успешно, обновляем токен
    if (response.data.success && response.data.token) {
      localStorage.setItem('jwt_token', response.data.token)
    }

    return response.data
  }
}
