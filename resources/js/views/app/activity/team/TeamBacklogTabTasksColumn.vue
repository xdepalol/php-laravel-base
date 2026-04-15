<template>
  <div class="rounded-lg border border-slate-200 bg-slate-50/50 p-3 min-h-[4rem]">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
      <span class="text-xs font-medium text-slate-600">Tareas</span>
      <Button
        v-if="canTaskCreate"
        label="Nueva tarea"
        icon="pi pi-plus"
        size="small"
        outlined
        @click="$emit('create-task')"
      />
    </div>
    <div v-if="isEmpty" class="text-xs text-slate-500 py-2">Sin tareas.</div>
    <draggable
      v-else-if="showDrag"
      v-model="draggableList"
      item-key="id"
      handle=".task-drag-handle"
      tag="div"
      class="task-card-grid"
      :disabled="!canTaskEdit"
      @end="$emit('tasks-reordered')"
    >
      <template #item="{ element: task }">
        <div
          class="task-card rounded-md border border-white bg-white px-2 py-2 shadow-sm flex items-start gap-2 text-sm h-full min-w-0"
        >
          <span
            class="task-drag-handle cursor-grab active:cursor-grabbing text-slate-400 hover:text-slate-600 shrink-0 mt-0.5"
            title="Arrastrar"
          >
            <i class="pi pi-bars text-xs" aria-hidden="true" />
          </span>
          <div class="min-w-0 flex-1">
            <p class="font-medium text-slate-800 break-words">{{ task.title }}</p>
            <Tag :value="taskStatusLabel(task)" severity="secondary" class="text-xs mt-1" />
          </div>
          <div v-if="canTaskEdit || canTaskDelete" class="flex shrink-0 gap-0.5">
            <Button
              v-if="canTaskEdit"
              v-tooltip.top="'Editar'"
              icon="pi pi-pencil"
              text
              rounded
              severity="secondary"
              size="small"
              @click="$emit('edit-task', task)"
            />
            <Button
              v-if="canTaskDelete"
              v-tooltip.top="'Eliminar'"
              icon="pi pi-trash"
              text
              rounded
              severity="danger"
              size="small"
              @click="$emit('delete-task', task)"
            />
          </div>
        </div>
      </template>
    </draggable>
    <div v-else class="task-card-grid">
      <div
        v-for="task in tasks"
        :key="task.id"
        class="task-card rounded-md border border-white bg-white px-2 py-2 shadow-sm flex items-start justify-between gap-2 text-sm h-full min-w-0"
      >
        <div class="min-w-0">
          <p class="font-medium text-slate-800 break-words">{{ task.title }}</p>
          <Tag :value="taskStatusLabel(task)" severity="secondary" class="text-xs mt-1" />
        </div>
        <div v-if="canTaskEdit || canTaskDelete" class="flex shrink-0 gap-0.5">
          <Button
            v-if="canTaskEdit"
            v-tooltip.top="'Editar'"
            icon="pi pi-pencil"
            text
            rounded
            severity="secondary"
            size="small"
            @click="$emit('edit-task', task)"
          />
          <Button
            v-if="canTaskDelete"
            v-tooltip.top="'Eliminar'"
            icon="pi pi-trash"
            text
            rounded
            severity="danger"
            size="small"
            @click="$emit('delete-task', task)"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import draggable from 'vuedraggable'

const props = defineProps({
  bi: { type: Object, required: true },
  /** Lista filtrada para vista sin drag */
  tasks: { type: Array, default: () => [] },
  showDrag: { type: Boolean, default: false },
  taskLists: { type: Object, default: () => ({}) },
  canTaskCreate: { type: Boolean, default: false },
  canTaskEdit: { type: Boolean, default: false },
  canTaskDelete: { type: Boolean, default: false },
})

defineEmits(['create-task', 'edit-task', 'delete-task', 'tasks-reordered'])

const TASK_STATUS_LABELS = {
  0: 'Por hacer',
  1: 'En progreso',
  2: 'Hecha',
  3: 'Cancelada',
}

const internalList = computed(() => props.taskLists[props.bi.id] || [])

const isEmpty = computed(() =>
  props.showDrag ? internalList.value.length === 0 : props.tasks.length === 0
)

const draggableList = computed({
  get() {
    return props.taskLists[props.bi.id] || []
  },
  set(v) {
    props.taskLists[props.bi.id] = v
  },
})

function taskStatusLabel(task) {
  const v = typeof task.status === 'object' && task.status ? task.status.value : Number(task.status ?? 0)
  return TASK_STATUS_LABELS[v] ?? String(v)
}
</script>

<style scoped>
/** Tarjetas en malla: no ocupan todo el ancho; en pantallas grandes varias columnas. Sortable sigue viendo hermanos en el mismo contenedor. */
.task-card-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.5rem;
}

@media (min-width: 640px) {
  .task-card-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (min-width: 1280px) {
  .task-card-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

.task-card {
  max-width: 22rem;
  justify-self: start;
  width: 100%;
}
</style>
