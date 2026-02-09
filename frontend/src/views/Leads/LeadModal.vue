<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content">
      <div class="modal-header">
        <h2>{{ lead ? 'Редактировать лид' : 'Новый лид' }}</h2>
        <button class="close-btn" @click="$emit('close')">&times;</button>
      </div>

      <div class="modal-body">
        <form @submit.prevent="handleSubmit">
          <div class="form-grid">
            <!-- Дата заявки -->
            <div class="form-group">
              <label>Дата заявки *</label>
              <input
                type="date"
                v-model="formData.dateInput"
                required
                class="form-control"
              />
            </div>

            <!-- Сайт -->
            <div class="form-group">
              <label>Сайт</label>
              <input
                type="text"
                v-model="formData.website"
                placeholder="example.com"
                class="form-control"
              />
            </div>

            <!-- Канал/Источник -->
            <div class="form-group">
              <label>Канал/Источник</label>
              <input
                type="text"
                v-model="formData.channel"
                list="channels-list"
                placeholder="Выберите или введите канал"
                class="form-control"
              />
              <datalist id="channels-list">
                <option v-for="channel in channels" :key="channel" :value="channel"></option>
              </datalist>
            </div>

            <!-- Менеджер -->
            <div class="form-group">
              <label>Ответственный менеджер</label>
              <select v-model.number="formData.managerId" class="form-control">
                <option :value="null">Не выбран</option>
                <option v-for="manager in managers" :key="manager.id" :value="manager.id">
                  {{ manager.name }} {{ manager.surname }}
                </option>
              </select>
            </div>

            <!-- Тип контакта -->
            <div class="form-group">
              <label>Тип связи *</label>
              <select v-model="formData.contactType" required class="form-control">
                <option value="">Выберите тип</option>
                <option value="phone">Телефон</option>
                <option value="whatsapp">WhatsApp</option>
                <option value="vk">ВКонтакте</option>
                <option value="telegram">Telegram</option>
              </select>
            </div>

            <!-- Контакт -->
            <div class="form-group">
              <label>Контакт *</label>
              <input
                type="text"
                v-model="formData.contactValue"
                required
                placeholder="+7 (900) 123-45-67"
                class="form-control"
              />
            </div>

            <!-- Цена -->
            <div class="form-group">
              <label>Цена</label>
              <input
                type="number"
                v-model="formData.price"
                placeholder="0"
                step="0.01"
                class="form-control"
              />
            </div>

            <!-- Статус -->
            <div class="form-group">
              <label>Статус *</label>
              <select v-model.number="formData.status" required class="form-control">
                <option :value="1">Новый</option>
                <option :value="2">Ждем ответа</option>
                <option :value="3">Работаем</option>
                <option :value="4">Слился</option>
                <option :value="5">Холодные</option>
              </select>
            </div>

            <!-- Дата связи -->
            <div class="form-group">
              <label>Дата связи</label>
              <input
                type="date"
                v-model="formData.contactDateInput"
                class="form-control"
              />
            </div>
          </div>

          <!-- Аудит -->
          <div class="form-section">
            <h3>Аудит</h3>
            <div class="form-grid">
              <div class="form-group full-width">
                <label>Информация</label>
                <textarea
                  v-model="formData.auditInfo"
                  rows="3"
                  placeholder="Описание аудита..."
                  class="form-control"
                ></textarea>
              </div>
              <div class="form-group">
                <label>Статус аудита</label>
                <select v-model="formData.auditStatus" class="form-control">
                  <option value="not_ready">Не готов</option>
                  <option value="ready">Готов</option>
                </select>
              </div>
            </div>
          </div>

          <!-- КП -->
          <div class="form-section">
            <h3>Коммерческое предложение</h3>
            <div class="form-grid">
              <div class="form-group full-width">
                <label>Информация</label>
                <textarea
                  v-model="formData.proposalInfo"
                  rows="3"
                  placeholder="Описание КП..."
                  class="form-control"
                ></textarea>
              </div>
              <div class="form-group">
                <label>Статус КП</label>
                <select v-model="formData.proposalStatus" class="form-control">
                  <option value="not_ready">Не готов</option>
                  <option value="ready">Готов</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Комментарий -->
          <div class="form-group">
            <label>Комментарий</label>
            <textarea
              v-model="formData.comment"
              rows="3"
              placeholder="Дополнительная информация..."
              class="form-control"
            ></textarea>
          </div>

          <div class="modal-actions">
            <button type="button" @click="$emit('close')" class="btn-secondary">
              Отмена
            </button>
            <button type="submit" class="btn-primary" :disabled="saving">
              {{ saving ? 'Сохранение...' : 'Сохранить' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import leadsApi from '../../services/leads'

const props = defineProps({
  lead: Object
})

const emit = defineEmits(['close', 'saved'])

const saving = ref(false)
const managers = ref([])
const channels = ref([])

const formData = ref({
  dateInput: '',
  website: '',
  channel: '',
  contactType: '',
  contactValue: '',
  auditInfo: '',
  auditStatus: 'not_ready',
  proposalInfo: '',
  proposalStatus: 'not_ready',
  price: null,
  status: 1,
  contactDateInput: '',
  comment: '',
  managerId: null
})

const timestampToDateInput = (timestamp) => {
  if (!timestamp) return ''
  const date = new Date(timestamp * 1000)
  return date.toISOString().split('T')[0]
}

const dateInputToTimestamp = (dateInput) => {
  if (!dateInput) return null
  return Math.floor(new Date(dateInput).getTime() / 1000)
}

const handleSubmit = async () => {
  try {
    saving.value = true

    const leadData = {
      date: dateInputToTimestamp(formData.value.dateInput),
      website: formData.value.website || null,
      channel: formData.value.channel || null,
      contactType: formData.value.contactType,
      contactValue: formData.value.contactValue,
      auditInfo: formData.value.auditInfo || null,
      auditStatus: formData.value.auditStatus,
      proposalInfo: formData.value.proposalInfo || null,
      proposalStatus: formData.value.proposalStatus,
      price: formData.value.price || null,
      status: formData.value.status,
      contactDate: dateInputToTimestamp(formData.value.contactDateInput),
      comment: formData.value.comment || null,
      managerId: formData.value.managerId || null
    }

    let response
    if (props.lead) {
      response = await leadsApi.updateLead(props.lead.id, leadData)
    } else {
      response = await leadsApi.createLead(leadData)
    }

    if (response.success) {
      emit('saved')
    } else {
      alert('Ошибка: ' + response.message)
    }
  } catch (error) {
    console.error('Ошибка сохранения лида:', error)
    alert('Произошла ошибка при сохранении')
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  // Загрузить менеджеров и каналы
  try {
    const [managersResp, channelsResp] = await Promise.all([
      leadsApi.getManagers(),
      leadsApi.getChannels()
    ])

    if (managersResp.success) {
      managers.value = managersResp.managers
    }

    if (channelsResp.success) {
      channels.value = channelsResp.channels
    }
  } catch (error) {
    console.error('Ошибка загрузки данных:', error)
  }

  if (props.lead) {
    formData.value = {
      dateInput: timestampToDateInput(props.lead.date),
      website: props.lead.website || '',
      channel: props.lead.channel || '',
      contactType: props.lead.contact_type,
      contactValue: props.lead.contact_value,
      auditInfo: props.lead.audit_info || '',
      auditStatus: props.lead.audit_status,
      proposalInfo: props.lead.proposal_info || '',
      proposalStatus: props.lead.proposal_status,
      price: props.lead.price,
      status: props.lead.status,
      contactDateInput: timestampToDateInput(props.lead.contact_date),
      comment: props.lead.comment || '',
      managerId: props.lead.manager_id
    }
  } else {
    // Для нового лида устанавливаем текущую дату
    formData.value.dateInput = timestampToDateInput(Math.floor(Date.now() / 1000))
  }
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
  max-width: 800px;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.5rem;
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
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.form-section {
  margin-bottom: 1.5rem;
}

.form-section h3 {
  margin: 0 0 1rem 0;
  font-size: 1.1rem;
  color: #1f2937;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group.full-width {
  grid-column: 1 / -1;
}

.form-group label {
  font-weight: 500;
  color: #374151;
  font-size: 0.875rem;
}

.form-control {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.2s;
}

.form-control:focus {
  outline: none;
  border-color: #2d3748;
}

textarea.form-control {
  resize: vertical;
  font-family: inherit;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.btn-primary,
.btn-secondary {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-primary {
  background: #2d3748;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #1a202c;
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-secondary {
  background: white;
  color: #666;
  border: 1px solid #d1d5db;
}

.btn-secondary:hover {
  background: #f9fafb;
}

@media (max-width: 768px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
}
</style>
