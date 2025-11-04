<template>
  <div class="tree-node" :style="{ paddingLeft: `${level * 20}px` }">
    <!-- Узел папки или проекта -->
    <div class="node-content" :class="{ 'is-folder': node.type === 'folder', 'is-project': node.type === 'project' }">
      <!-- Кнопка раскрытия (только для папок с детьми) -->
      <button
        v-if="node.type === 'folder' && node.children && node.children.length > 0"
        class="expand-btn"
        @click.stop="toggleExpand"
      >
        {{ isExpanded ? '▼' : '▶' }}
      </button>
      <span v-else class="expand-placeholder"></span>

      <!-- Иконка -->
      <span class="node-icon" @click.stop="node.type === 'folder' ? toggleExpand() : null">
        {{ node.type === 'folder' ? '📁' : '📋' }}
      </span>

      <!-- Название -->
      <router-link
        v-if="node.type === 'project'"
        :to="`/projects/${node.id}`"
        class="node-name"
        @click="handleProjectClick"
      >
        {{ node.name }}
      </router-link>
      <span v-else class="node-name" @click.stop="toggleExpand">
        {{ node.name }}
      </span>

      <!-- Счетчик задач для проектов -->
      <span v-if="node.type === 'project' && node.tasks_count !== undefined" class="tasks-badge">
        {{ node.tasks_count }}
      </span>

      <!-- Действия -->
      <div v-if="!collapsed" class="node-actions">
        <template v-if="node.type === 'folder'">
          <button class="action-btn" @click.stop="$emit('add-subfolder', node.id)" title="Добавить подпапку">
            📁+
          </button>
          <button class="action-btn" @click.stop="$emit('add-project-to-folder', node.id)" title="Добавить проект">
            📋+
          </button>
          <button class="action-btn" @click.stop="$emit('edit-folder', node)" title="Редактировать">
            ✏️
          </button>
          <button class="action-btn danger" @click.stop="$emit('delete-folder', node.id)" title="Удалить">
            🗑️
          </button>
        </template>
        <template v-else-if="node.type === 'project'">
          <button class="action-btn" @click.stop="$emit('edit-project', node)" title="Редактировать">
            ✏️
          </button>
          <button class="action-btn danger" @click.stop="$emit('delete-project', node.id)" title="Удалить">
            🗑️
          </button>
        </template>
      </div>
    </div>

    <!-- Дочерние элементы -->
    <transition name="expand">
      <div v-if="isExpanded && node.children && node.children.length > 0" class="node-children">
        <TreeNode
          v-for="child in node.children"
          :key="child.type + '-' + child.id"
          :node="child"
          :level="level + 1"
          :collapsed="collapsed"
          @toggle="$emit('toggle', $event)"
          @edit-folder="$emit('edit-folder', $event)"
          @delete-folder="$emit('delete-folder', $event)"
          @edit-project="$emit('edit-project', $event)"
          @delete-project="$emit('delete-project', $event)"
          @add-subfolder="$emit('add-subfolder', $event)"
          @add-project-to-folder="$emit('add-project-to-folder', $event)"
        />
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  node: {
    type: Object,
    required: true
  },
  level: {
    type: Number,
    default: 0
  },
  collapsed: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits([
  'toggle',
  'edit-folder',
  'delete-folder',
  'edit-project',
  'delete-project',
  'add-subfolder',
  'add-project-to-folder'
])

const isExpanded = ref(false)

const toggleExpand = () => {
  isExpanded.value = !isExpanded.value
  emit('toggle', props.node.id)
}

const handleProjectClick = (event) => {
  console.log('Клик на проект:', props.node.name, 'ID:', props.node.id)
  event.stopPropagation()
}
</script>

<style scoped>
.tree-node {
  width: 100%;
}

.node-content {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem;
  border-radius: 6px;
  transition: background 0.2s ease;
  position: relative;
}

.node-content:hover {
  background: #f5f5f7;
}

.node-content:hover .node-actions {
  opacity: 1;
  pointer-events: auto;
}

.expand-btn {
  width: 20px;
  height: 20px;
  padding: 0;
  border: none;
  background: transparent;
  cursor: pointer;
  font-size: 0.7rem;
  color: #666;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.expand-btn:hover {
  color: #2d3748;
}

.expand-placeholder {
  width: 20px;
  flex-shrink: 0;
}

.node-icon {
  font-size: 1.2rem;
  flex-shrink: 0;
}

.is-folder .node-icon {
  cursor: pointer;
}

.node-name {
  flex: 1;
  font-size: 0.9rem;
  color: #2d3748;
  text-decoration: none;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-weight: 500;
}

.node-name:hover {
  color: #667eea;
}

.is-folder .node-name {
  font-weight: 600;
  cursor: pointer;
}

.tasks-badge {
  background: #e0e0e0;
  color: #666;
  padding: 0.15rem 0.5rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  flex-shrink: 0;
}

.node-actions {
  display: flex;
  gap: 0.25rem;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.2s ease;
  flex-shrink: 0;
}

.action-btn {
  width: 24px;
  height: 24px;
  padding: 0;
  border: none;
  background: transparent;
  cursor: pointer;
  font-size: 0.9rem;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s ease;
}

.action-btn:hover {
  background: #e0e0e0;
}

.action-btn.danger:hover {
  background: #fee;
}

.node-children {
  margin-left: 0;
}

/* Анимация раскрытия */
.expand-enter-active,
.expand-leave-active {
  transition: all 0.3s ease;
  overflow: hidden;
}

.expand-enter-from,
.expand-leave-to {
  max-height: 0;
  opacity: 0;
}

.expand-enter-to,
.expand-leave-from {
  max-height: 1000px;
  opacity: 1;
}
</style>
