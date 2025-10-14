<template>
  <div class="dashboard-layout">
    <Sidebar />

    <main :class="['main-content', { collapsed: sidebarCollapsed }]">
      <!-- Хедер внутри лейаута -->
      <div class="dashboard-header">
        <div class="header-left">
          <slot name="header-left">
            <h1>Панель управления</h1>
          </slot>
        </div>

        <div class="header-right">
          <div class="date-info">{{ currentDate }}</div>

          <RouterLink to="/profile" class="user-profile">
            <div class="user-info">
              <span class="user-name">{{ authStore.user?.username }}</span>
              <span class="user-email">{{ authStore.user?.email }}</span>
            </div>
            <div class="user-avatar">
              {{ authStore.user?.username?.[0]?.toUpperCase() || 'U' }}
            </div>
          </RouterLink>
        </div>
      </div>

      <!-- Контент страницы -->
      <div class="content-wrapper">
        <slot />
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import Sidebar from '../components/Sidebar.vue'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()

const sidebarCollapsed = ref(localStorage.getItem('sidebar-collapsed') === 'true')

const currentDate = computed(() => {
  const date = new Date()
  return date.toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
})

// Функция для обновления состояния
const updateSidebarState = () => {
  sidebarCollapsed.value = localStorage.getItem('sidebar-collapsed') === 'true'
}

// Слушаем изменения localStorage
onMounted(() => {
  window.addEventListener('storage', updateSidebarState)
  updateSidebarState()

  const interval = setInterval(updateSidebarState, 100)

  onUnmounted(() => {
    window.removeEventListener('storage', updateSidebarState)
    clearInterval(interval)
  })
})
</script>

<style scoped>
.dashboard-layout {
  display: flex;
  min-height: 100vh;
  background: #f5f5f7;
}

.main-content {
  margin-left: 280px;
  flex: 1;
  transition: margin-left 0.3s ease;
  padding: 2rem;
  display: flex;
  flex-direction: column;
}

.main-content.collapsed {
  margin-left: 80px;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #e0e0e0;
}

.header-left h1 {
  margin: 0;
  font-size: 1.75rem;
  font-weight: 600;
  color: #1a1a1a;
  letter-spacing: -0.5px;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.date-info {
  font-size: 0.9rem;
  color: #666;
  padding: 0.5rem 1rem;
  background: #fafafa;
  border-radius: 6px;
  border: 1px solid #e0e0e0;
}

.user-profile {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 1rem;
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  text-decoration: none;
  transition: all 0.2s ease;
}

.user-profile:hover {
  border-color: #2d3748;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.user-info {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.125rem;
}

.user-name {
  font-size: 0.9rem;
  font-weight: 600;
  color: #1a1a1a;
}

.user-email {
  font-size: 0.75rem;
  color: #666;
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  font-weight: 600;
  flex-shrink: 0;
}

.content-wrapper {
  flex: 1;
}

@media (max-width: 768px) {
  .main-content {
    margin-left: 0;
  }

  .dashboard-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .header-right {
    width: 100%;
    justify-content: space-between;
  }

  .user-info {
    align-items: flex-start;
  }
}
</style>
