import api from './api'

export default {
  async getProfiles() {
    const response = await api.get('/user/profiles')
    return response.data
  },

}
