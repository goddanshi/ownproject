<template>
  <DashboardLayout>
    <div class="page">
      <h1>📝 Заявки</h1>
      <p>Здесь будет список заявок</p>
    </div>
  </DashboardLayout>
</template>

<script setup>
import DashboardLayout from '../layouts/DashboardLayout.vue'
</script>

<style scoped>
.page {
  background: white;
  padding: 2rem;
  border-radius: 15px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

h1 {
  color: #333;
  margin-bottom: 1rem;
}
</style>
