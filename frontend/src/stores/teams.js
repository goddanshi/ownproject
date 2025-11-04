import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../services/api'

export const useTeamsStore = defineStore('teams', () => {
  const teams = ref([])
  const loading = ref(false)
  const error = ref(null)

  const loadTeams = async () => {
    try {
      loading.value = true
      error.value = null
      const response = await api.get('/teams')
      if (response.data.success) {
        teams.value = response.data.teams
      }
    } catch (err) {
      console.error('Ошибка загрузки команд:', err)
      error.value = err.message
    } finally {
      loading.value = false
    }
  }

  return {
    teams,
    loading,
    error,
    loadTeams
  }
})
