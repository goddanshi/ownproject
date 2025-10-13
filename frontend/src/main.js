import './assets/main.css'
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import { useAuthStore } from './stores/Auth'

const app = createApp(App)
app.use(createPinia())
app.use(router)

const auth = useAuthStore()

// 🔁 Проверяем токен при загрузке
auth.checkAuth().finally(() => {
  app.mount('#app')
})
