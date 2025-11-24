import { createApp, h } from 'vue'
import ConfirmDialog from '../components/ConfirmDialog.vue'

let confirmInstance = null
let resolvePromise = null
let rejectPromise = null

function createConfirmInstance() {
  const container = document.createElement('div')
  document.body.appendChild(container)

  const app = createApp({
    setup() {
      const handleConfirm = () => {
        if (resolvePromise) {
          resolvePromise(true)
          resolvePromise = null
          rejectPromise = null
        }
      }

      const handleCancel = () => {
        if (rejectPromise) {
          rejectPromise(false)
          resolvePromise = null
          rejectPromise = null
        }
      }

      return () => h(ConfirmDialog, {
        ref: 'confirmDialog',
        onConfirm: handleConfirm,
        onCancel: handleCancel
      })
    }
  })

  const instance = app.mount(container)
  return instance.$refs.confirmDialog
}

export function $confirm(options = {}) {
  return new Promise((resolve, reject) => {
    if (!confirmInstance) {
      confirmInstance = createConfirmInstance()
    }

    resolvePromise = resolve
    rejectPromise = reject

    // Настройки по умолчанию
    const defaultOptions = {
      title: 'Подтверждение',
      message: 'Вы уверены?',
      confirmText: 'Подтвердить',
      cancelText: 'Отмена',
      type: 'danger'
    }

    // Если передана строка, используем её как message
    const finalOptions = typeof options === 'string'
      ? { ...defaultOptions, message: options }
      : { ...defaultOptions, ...options }

    // Устанавливаем параметры и открываем диалог
    Object.assign(confirmInstance.$props, finalOptions)
    confirmInstance.open()
  })
}

export default {
  install(app) {
    app.config.globalProperties.$confirm = $confirm
    app.provide('$confirm', $confirm)
  }
}
