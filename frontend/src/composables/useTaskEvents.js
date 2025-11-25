import { ref } from 'vue'

// Глобальное хранилище событий задач
const taskEventListeners = new Map()

export function useTaskEvents() {
  // Подписка на события задачи
  const onTaskEvent = (taskId, callback) => {
    if (!taskEventListeners.has(taskId)) {
      taskEventListeners.set(taskId, new Set())
    }
    taskEventListeners.get(taskId).add(callback)

    // Возвращаем функцию отписки
    return () => {
      const listeners = taskEventListeners.get(taskId)
      if (listeners) {
        listeners.delete(callback)
        if (listeners.size === 0) {
          taskEventListeners.delete(taskId)
        }
      }
    }
  }

  // Отправка события задачи
  const emitTaskEvent = (taskId, eventType, data = {}) => {
    const listeners = taskEventListeners.get(taskId)
    if (listeners) {
      listeners.forEach(callback => {
        callback({ type: eventType, taskId, data })
      })
    }

    // Также отправляем глобальное событие для всех задач
    const globalListeners = taskEventListeners.get('*')
    if (globalListeners) {
      globalListeners.forEach(callback => {
        callback({ type: eventType, taskId, data })
      })
    }
  }

  return {
    onTaskEvent,
    emitTaskEvent
  }
}
