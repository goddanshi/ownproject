<template>
  <div class="dashboard-layout">
    <Sidebar />

    <main :class="['main-content', { collapsed: sidebarCollapsed }]">
      <div class="content-wrapper">
        <slot />
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import Sidebar from '../components/Sidebar.vue'

const sidebarCollapsed = ref(localStorage.getItem('sidebar-collapsed') === 'true')

// Функция для обновления состояния
const updateSidebarState = () => {
  sidebarCollapsed.value = localStorage.getItem('sidebar-collapsed') === 'true'
}

// Слушаем изменения localStorage
onMounted(() => {
  window.addEventListener('storage', updateSidebarState)
  // Проверяем при монтировании
  updateSidebarState()

  // Периодически проверяем (на случай изменений в том же табе)
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
}

.main-content.collapsed {
  margin-left: 80px;
}

.content-wrapper {
  max-width: 1400px;
  margin: 0 auto;
}

@media (max-width: 768px) {
  .main-content {
    margin-left: 0;
  }
}
</style>
