<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content">
      <div v-if="loading" class="loading">
        <div class="spinner"></div>
        <p>Загрузка...</p>
      </div>

      <div v-else-if="lead" class="modal-layout">
        <div class="modal-header">
          <h2>Детали лида</h2>
          <div class="header-actions">
            <button class="btn-icon" @click="editLead" title="Редактировать">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
              </svg>
            </button>
            <button class="btn-icon danger" @click="deleteLead" title="Удалить">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
              </svg>
            </button>
            <button class="close-btn" @click="$emit('close')">&times;</button>
          </div>
        </div>

        <div class="modal-body">
          <div class="details-grid">
            <!-- Основная информация -->
            <div class="section">
              <h3>Основная информация</h3>
              <div class="info-grid">
                <div class="info-item">
                  <span class="label">Дата заявки:</span>
                  <span>{{ formatDate(lead.date) }}</span>
                </div>
                <div class="info-item">
                  <span class="label">Статус:</span>
                  <span class="status-badge">{{ lead.status_label }}</span>
                </div>
                <div class="info-item" v-if="lead.website">
                  <span class="label">Сайт:</span>
                  <a :href="`https://${lead.website}`" target="_blank" class="website-link">
                    {{ lead.website }}
                  </a>
                </div>
                <div class="info-item">
                  <span class="label">Тип связи:</span>
                  <span class="contact-type-badge">{{ lead.contact_type_label }}</span>
                </div>
                <div class="info-item">
                  <span class="label">Контакт:</span>
                  <span>{{ lead.contact_value }}</span>
                </div>
                <div class="info-item" v-if="lead.contact_date">
                  <span class="label">Дата связи:</span>
                  <span :class="['contact-date', getContactDateClass(lead)]">
                    {{ formatDate(lead.contact_date) }}
                  </span>
                </div>
                <div class="info-item" v-if="lead.price">
                  <span class="label">Цена:</span>
                  <span class="price">{{ formatPrice(lead.price) }}</span>
                </div>
                <div class="info-item">
                  <span class="label">Создал:</span>
                  <span>{{ lead.creator.name }} {{ lead.creator.surname }}</span>
                </div>
              </div>
            </div>

            <!-- Аудит -->
            <div class="section">
              <h3>Аудит</h3>
              <div class="status-row">
                <span :class="['status-badge', lead.audit_status === 'ready' ? 'ready' : 'not-ready']">
                  {{ lead.audit_status_label }}
                </span>
              </div>
              <p v-if="lead.audit_info" class="section-text">{{ lead.audit_info }}</p>
              <p v-else class="no-data">Информация отсутствует</p>
            </div>

            <!-- Коммерческое предложение -->
            <div class="section">
              <h3>Коммерческое предложение</h3>
              <div class="status-row">
                <span :class="['status-badge', lead.proposal_status === 'ready' ? 'ready' : 'not-ready']">
                  {{ lead.proposal_status_label }}
                </span>
              </div>
              <p v-if="lead.proposal_info" class="section-text">{{ lead.proposal_info }}</p>
              <p v-else class="no-data">Информация отсутствует</p>
            </div>

            <!-- Комментарий -->
            <div class="section" v-if="lead.comment">
              <h3>Комментарий</h3>
              <p class="section-text">{{ lead.comment }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, inject } from 'vue'
import leadsApi from '../../services/leads'

const props = defineProps({
  leadId: Number
})

const emit = defineEmits(['close', 'edit', 'delete'])

const lead = ref(null)
const loading = ref(true)
const $confirm = inject('$confirm', null)

const loadLead = async () => {
  try {
    loading.value = true
    const response = await leadsApi.getLead(props.leadId)
    if (response.success) {
      lead.value = response.lead
    }
  } catch (error) {
    console.error('Ошибка загрузки лида:', error)
  } finally {
    loading.value = false
  }
}

const editLead = () => {
  emit('edit', lead.value)
}

const deleteLead = async () => {
  try {
    if ($confirm) {
      await $confirm({
        title: 'Удаление лида',
        message: 'Вы уверены, что хотите удалить этот лид? Это действие нельзя отменить.',
        confirmText: 'Удалить',
        cancelText: 'Отмена',
        type: 'danger'
      })
    } else {
      if (!confirm('Вы уверены, что хотите удалить этот лид?')) {
        return
      }
    }

    const response = await leadsApi.deleteLead(lead.value.id)
    if (response.success) {
      emit('delete')
      emit('close')
    }
  } catch (rejected) {
    if (rejected === false) {
      return
    }
    console.error('Ошибка удаления лида:', rejected)
  }
}

const formatDate = (timestamp) => {
  if (!timestamp) return ''
  const date = new Date(timestamp * 1000)
  return date.toLocaleDateString('ru-RU')
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0
  }).format(price)
}

const getContactDateClass = (lead) => {
  if (lead.contact_date_today) return 'today'
  if (lead.contact_date_expired) return 'expired'
  return 'future'
}

onMounted(() => {
  loadLead()
})
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 900px;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.modal-layout {
  display: flex;
  flex-direction: column;
  height: 100%;
  min-height: 0;
  overflow: hidden;
}

.loading {
  text-align: center;
  padding: 4rem 2rem;
}

.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #2d3748;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  background: white;
  border-bottom: 1px solid #e5e7eb;
  flex-shrink: 0;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.5rem;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-icon {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 6px;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
}

.btn-icon svg {
  width: 20px;
  height: 20px;
  stroke: currentColor;
}

.btn-icon:hover {
  background: #f3f4f6;
}

.btn-icon.danger {
  color: #dc2626;
}

.btn-icon.danger:hover {
  background: #fee2e2;
}

.close-btn {
  background: none;
  border: none;
  font-size: 2rem;
  cursor: pointer;
  color: #666;
  padding: 0;
  line-height: 1;
}

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
  flex: 1;
  min-height: 0;
}

.details-grid {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.section {
  background: #f9fafb;
  padding: 1.5rem;
  border-radius: 8px;
}

.section h3 {
  margin: 0 0 1rem 0;
  font-size: 1.1rem;
  color: #1f2937;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.label {
  font-size: 0.875rem;
  color: #6b7280;
  font-weight: 500;
}

.status-row {
  margin-bottom: 1rem;
}

.status-badge {
  display: inline-block;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
}

.status-badge.ready {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.not-ready {
  background: #fee2e2;
  color: #991b1b;
}

.contact-type-badge {
  display: inline-block;
  background: #dbeafe;
  color: #1e40af;
  padding: 0.25rem 0.75rem;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 600;
}

.website-link {
  color: #2563eb;
  font-weight: 500;
  transition: color 0.2s;
}

.website-link:hover {
  color: #1d4ed8;
}

.contact-date {
  font-weight: 600;
  padding: 0.25rem 0.75rem;
  border-radius: 6px;
  display: inline-block;
}

.contact-date.today {
  background: #d1fae5;
  color: #065f46;
}

.contact-date.expired {
  background: #fee2e2;
  color: #991b1b;
}

.contact-date.future {
  background: #e0e7ff;
  color: #4338ca;
}

.price {
  font-size: 1.25rem;
  font-weight: 700;
  color: #059669;
}

.section-text {
  margin: 0;
  line-height: 1.6;
  color: #374151;
  white-space: pre-wrap;
}

.no-data {
  margin: 0;
  color: #9ca3af;
  font-style: italic;
}

@media (max-width: 768px) {
  .info-grid {
    grid-template-columns: 1fr;
  }
}
</style>
