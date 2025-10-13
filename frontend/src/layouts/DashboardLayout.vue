<template>
  <div class="dashboard-layout">
    <Sidebar />

    <main :class="['main-content', { collapsed: isCollapsed }]">
      <div class="content-wrapper">
        <slot />
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, provide } from 'vue'
import Sidebar from '../components/Sidebar.vue'


const isCollapsed = ref(false)

provide('sidebarCollapsed', isCollapsed)
</script>

<style scoped>
.dashboard-layout {
  display: flex;
  min-height: 100vh;
  background: #f5f7fa;
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
