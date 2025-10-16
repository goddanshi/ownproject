import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

export function usePermissions() {
  const authStore = useAuthStore()

  const userPermissions = computed(() => {
    return authStore.user?.permissions || []
  })

  const can = (permission) => {
    return userPermissions.value.includes(permission)
  }

  const canAny = (permissions) => {
    return permissions.some(permission => can(permission))
  }

  const canAll = (permissions) => {
    return permissions.every(permission => can(permission))
  }

  return {
    can,
    canAny,
    canAll,
    userPermissions
  }
}
