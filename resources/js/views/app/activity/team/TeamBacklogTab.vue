<template>
  <Card>
    <template #title>
      <div class="flex flex-wrap items-center justify-between gap-3">
        <span>Backlog y tareas</span>
        <div class="flex flex-wrap gap-2">
          <Button
            label="Importar CSV / Excel"
            icon="pi pi-file-import"
            severity="secondary"
            outlined
            size="small"
            :disabled="isLoading || !canEditTeamBacklog"
            @click="openImportDialog"
          />
          <Button
            label="Nuevo ítem"
            icon="pi pi-plus"
            size="small"
            :disabled="isLoading || !canEditTeamBacklog"
            @click="openCreateDialog"
          />
        </div>
      </div>
    </template>
    <template #content>
      <p class="text-sm text-slate-600 mb-4">
        Ítems compartidos y del equipo, con sus tareas. Reordená ítems del equipo o tareas cuando no haya
        filtros activos (los filtros ocultan filas pero el reordenar necesita ver el conjunto completo).
      </p>

      <div class="flex flex-wrap items-end gap-3 mb-4 p-3 rounded-lg border border-slate-200 bg-slate-50/80">
        <div class="min-w-[10rem] flex-1">
          <label class="text-xs font-medium text-slate-600 block mb-1">Buscar ítem</label>
          <InputText v-model="backlogNameQuery" class="w-full" placeholder="Título…" />
        </div>
        <div class="w-40">
          <label class="text-xs font-medium text-slate-600 block mb-1">Prioridad ítem</label>
          <Select
            v-model="backlogPriorityFilter"
            :options="priorityOptions"
            option-label="label"
            option-value="value"
            placeholder="Todas"
            show-clear
            class="w-full"
          />
        </div>
        <div class="w-44">
          <label class="text-xs font-medium text-slate-600 block mb-1">Estado ítem</label>
          <Select
            v-model="backlogStatusFilter"
            :options="statusOptions"
            option-label="label"
            option-value="value"
            placeholder="Todos"
            show-clear
            class="w-full"
          />
        </div>
        <div class="min-w-[10rem] flex-1">
          <label class="text-xs font-medium text-slate-600 block mb-1">Buscar tarea</label>
          <InputText v-model="taskNameQuery" class="w-full" placeholder="Título de tarea…" />
        </div>
        <div class="w-44">
          <label class="text-xs font-medium text-slate-600 block mb-1">Estado tarea</label>
          <Select
            v-model="taskStatusFilter"
            :options="taskStatusFilterOptions"
            option-label="label"
            option-value="value"
            placeholder="Todos"
            show-clear
            class="w-full"
          />
        </div>
        <div class="flex items-center gap-2 pb-0.5">
          <Checkbox v-model="showTasks" input-id="show-tasks" binary />
          <label for="show-tasks" class="text-sm text-slate-700 cursor-pointer">Mostrar tareas</label>
        </div>
      </div>

      <p
        v-if="backlogFiltersActive || taskFiltersActive"
        class="text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-md px-2 py-1.5 mb-3"
      >
        <template v-if="backlogFiltersActive">Filtros de backlog activos: el reordenado de ítems del equipo está desactivado.</template>
        <template v-if="backlogFiltersActive && taskFiltersActive"> </template>
        <template v-if="taskFiltersActive">Filtros de tarea activos: el reordenado de tareas está desactivado.</template>
      </p>

      <div v-if="isLoading" class="flex justify-center py-12 text-slate-500">
        <i class="pi pi-spin pi-spinner text-2xl" aria-hidden="true" />
      </div>

      <div v-else class="space-y-8">
        <!-- Vista filtrada: lista mixta sin drag de backlog -->
        <template v-if="backlogFiltersActive">
          <div v-if="!displayedBacklogRows.length" class="text-sm text-slate-500">Ningún ítem coincide.</div>
          <div v-else class="space-y-6">
            <div
              v-for="bi in displayedBacklogRows"
              :key="bi.id"
              class="grid gap-4 md:grid-cols-[minmax(14rem,22rem)_1fr] items-start"
            >
              <BacklogItemCard
                :row="bi"
                :scope-label="scopeLabel(bi)"
                :is-team-row="isTeamRow(bi)"
                :can-edit-team-backlog="canEditTeamBacklog"
                @edit="openEditDialog(bi)"
                @delete="confirmDelete(bi)"
              />
              <TasksColumn
                v-if="showTasks"
                :bi="bi"
                :tasks="filteredTasksForBi(bi.id)"
                :show-drag="false"
                :task-lists="taskLists"
                :can-task-create="can('task-create')"
                :can-task-edit="can('task-edit')"
                :can-task-delete="can('task-delete')"
                @create-task="openTaskDialog(bi, null)"
                @edit-task="(t) => openTaskDialog(bi, t)"
                @delete-task="confirmDeleteTask"
              />
            </div>
          </div>
        </template>

        <template v-else>
          <div v-if="sharedRows.length" class="space-y-4">
            <h3 class="text-sm font-semibold text-slate-800 border-b border-slate-200 pb-1">Backlog compartido</h3>
            <div class="space-y-6">
              <div
                v-for="bi in sharedRows"
                :key="bi.id"
                class="grid gap-4 md:grid-cols-[minmax(14rem,22rem)_1fr] items-start"
              >
                <BacklogItemCard
                  :row="bi"
                  scope-label="Compartido"
                  :is-team-row="false"
                  :can-edit-team-backlog="false"
                />
                <TasksColumn
                  v-if="showTasks"
                  :bi="bi"
                  :tasks="filteredTasksForBi(bi.id)"
                  :show-drag="!taskFiltersActive && can('task-edit')"
                  :task-lists="taskLists"
                  :can-task-create="can('task-create')"
                  :can-task-edit="can('task-edit')"
                  :can-task-delete="can('task-delete')"
                  @create-task="openTaskDialog(bi, null)"
                  @edit-task="(t) => openTaskDialog(bi, t)"
                  @delete-task="confirmDeleteTask"
                  @tasks-reordered="onTaskDragEnd(bi.id)"
                />
              </div>
            </div>
          </div>

          <div class="space-y-4">
            <h3 class="text-sm font-semibold text-slate-800 border-b border-slate-200 pb-1">
              Este equipo
              <span v-if="can('backlogitem-edit')" class="text-xs font-normal text-slate-500 ml-2">
                (arrastrá con el asa ⋮⋮)
              </span>
            </h3>
            <draggable
              v-model="teamDraggableList"
              item-key="id"
              handle=".bi-drag-handle"
              tag="div"
              class="space-y-6"
              :disabled="!can('backlogitem-edit') || teamDraggableList.length === 0"
              @end="onTeamBacklogDragEnd"
            >
              <template #item="{ element: bi }">
                <div
                  class="grid gap-4 md:grid-cols-[minmax(14rem,22rem)_1fr] items-start border-b border-slate-100 pb-6 last:border-0 last:pb-0"
                >
                  <BacklogItemCard
                    :row="bi"
                    scope-label="Este equipo"
                    :is-team-row="true"
                    :can-edit-team-backlog="canEditTeamBacklog"
                    drag-handle-class="bi-drag-handle"
                    @edit="openEditDialog(bi)"
                    @delete="confirmDelete(bi)"
                  />
                  <TasksColumn
                    v-if="showTasks"
                    :bi="bi"
                    :tasks="filteredTasksForBi(bi.id)"
                    :show-drag="!taskFiltersActive && can('task-edit')"
                    :task-lists="taskLists"
                    :can-task-create="can('task-create')"
                    :can-task-edit="can('task-edit')"
                    :can-task-delete="can('task-delete')"
                    @create-task="openTaskDialog(bi, null)"
                    @edit-task="(t) => openTaskDialog(bi, t)"
                    @delete-task="confirmDeleteTask"
                    @tasks-reordered="onTaskDragEnd(bi.id)"
                  />
                </div>
              </template>
            </draggable>
            <p v-if="!teamDraggableList.length" class="text-sm text-slate-500">No hay ítems propios del equipo.</p>
          </div>
        </template>
      </div>

      <Dialog
        v-model:visible="importDialog.open"
        modal
        header="Importar backlog (CSV / Excel)"
        class="w-full max-w-2xl"
        :closable="!importDialog.running"
        @hide="resetImportDialog"
      >
        <p class="text-sm text-slate-600 mb-3">
          Pega datos copiados desde Excel, LibreOffice o un archivo CSV. El servidor detecta separador
          (tabulador, coma o punto y coma) y, si la primera fila parece cabecera, enlaza columnas por
          nombre (español o inglés).
        </p>
        <p class="text-xs text-slate-600 mb-2 font-medium">Columnas reconocidas</p>
        <ul class="text-xs text-slate-500 mb-3 list-disc pl-5 space-y-0.5">
          <li><strong>position / posición / pos / orden</strong> — si hay valor, actualiza el ítem del equipo
            en esa posición o lo crea; filas sin posición se añaden al final.</li>
          <li><strong>title / título / titulo</strong> — obligatorio por fila.</li>
          <li><strong>description / descripción</strong>, <strong>priority / prioridad</strong> (1–4 o LOW, MEDIUM…),
            <strong>points / puntos</strong>, <strong>status / estado</strong> (0–3 o backlog, en curso, hecho…).</li>
        </ul>
        <div class="flex flex-wrap items-center gap-2 mb-2">
          <input
            ref="csvFileInput"
            type="file"
            accept=".csv,text/csv,text/plain,application/vnd.ms-excel"
            class="sr-only"
            :disabled="importDialog.running"
            @change="onCsvFileSelected"
          />
          <Button
            type="button"
            label="Elegir archivo .csv"
            icon="pi pi-folder-open"
            severity="secondary"
            outlined
            size="small"
            :disabled="importDialog.running"
            @click="csvFileInput?.click()"
          />
        </div>
        <Textarea
          v-model="importDialog.text"
          class="w-full font-mono text-sm"
          rows="12"
          :disabled="importDialog.running"
          placeholder="Ej. con cabecera (Excel):&#10;position&#9;title&#9;description&#10;1&#9;Historia de usuario&#9;Como…"
        />
        <template #footer>
          <Button label="Cancelar" severity="secondary" text :disabled="importDialog.running" @click="importDialog.open = false" />
          <Button
            label="Importar"
            icon="pi pi-check"
            :loading="importDialog.running"
            :disabled="!importDialog.text?.trim()"
            @click="runImport"
          />
        </template>
      </Dialog>

      <Dialog
        v-model:visible="itemDialog.open"
        modal
        :header="itemDialog.mode === 'create' ? 'Nuevo ítem de backlog' : 'Editar ítem'"
        class="w-full max-w-lg"
        @hide="closeItemDialog"
      >
        <div class="flex flex-col gap-4 pt-1">
          <div>
            <label class="text-sm font-medium text-slate-700 block mb-1">Título</label>
            <InputText v-model="itemForm.title" class="w-full" maxlength="150" />
            <small v-if="hasError('title')" class="text-red-600">{{ getError('title') }}</small>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700 block mb-1">Descripción</label>
            <Editor v-model="itemForm.description" editor-style="min-height: 200px" class="w-full" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-sm font-medium text-slate-700 block mb-1">Prioridad</label>
              <Select
                v-model="itemForm.priority"
                :options="priorityOptions"
                option-label="label"
                option-value="value"
                class="w-full"
              />
            </div>
            <div>
              <label class="text-sm font-medium text-slate-700 block mb-1">Estado</label>
              <Select
                v-model="itemForm.status"
                :options="statusOptions"
                option-label="label"
                option-value="value"
                class="w-full"
              />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-sm font-medium text-slate-700 block mb-1">Puntos</label>
              <InputNumber v-model="itemForm.points" class="w-full" :min="0" show-buttons />
            </div>
            <div>
              <label class="text-sm font-medium text-slate-700 block mb-1">Posición</label>
              <InputNumber v-model="itemForm.position" class="w-full" :min="0" show-buttons />
            </div>
          </div>
        </div>
        <template #footer>
          <Button label="Cancelar" severity="secondary" text @click="itemDialog.open = false" />
          <Button label="Guardar" icon="pi pi-check" :loading="itemDialog.saving" @click="submitItemDialog" />
        </template>
      </Dialog>

      <Dialog
        v-model:visible="taskDialog.open"
        modal
        :header="taskDialog.mode === 'create' ? 'Nueva tarea' : 'Editar tarea'"
        class="w-full max-w-2xl"
        @hide="closeTaskDialog"
      >
        <div v-if="taskDialog.backlogItem" class="flex flex-col gap-4 pt-1">
          <p class="text-xs text-slate-500">
            Ítem:
            <span class="font-medium text-slate-700">{{ taskDialog.backlogItem.title }}</span>
          </p>
          <div>
            <label class="text-sm font-medium text-slate-700 block mb-1">Título</label>
            <InputText v-model="taskForm.title" class="w-full" maxlength="150" />
            <small v-if="taskHasError('title')" class="text-red-600">{{ taskGetError('title') }}</small>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700 block mb-1">Descripción</label>
            <Editor v-model="taskForm.description" editor-style="min-height: 220px" class="w-full" />
          </div>
          <div class="w-52">
            <label class="text-sm font-medium text-slate-700 block mb-1">Estado</label>
            <Select
              v-model="taskForm.status"
              :options="taskFormStatusOptions"
              option-label="label"
              option-value="value"
              class="w-full"
            />
          </div>
        </div>
        <template #footer>
          <Button label="Cancelar" severity="secondary" text @click="taskDialog.open = false" />
          <Button label="Guardar" icon="pi pi-check" :loading="taskDialog.saving" @click="submitTaskDialog" />
        </template>
      </Dialog>
    </template>
  </Card>
</template>

<script setup>
import { computed, inject, onMounted, reactive, ref, useTemplateRef, watch } from 'vue'
import draggable from 'vuedraggable'
import { useAbility } from '@casl/vue'
import { useToast } from '@/composables/useToast'
import useAuth from '@/composables/auth'
import useActivityBacklogItems from '@/composables/activityBacklogItems'
import useActivityTasks from '@/composables/activityTasks'
import BacklogItemCard from './TeamBacklogTabBacklogCard.vue'
import TasksColumn from './TeamBacklogTabTasksColumn.vue'

const { can } = useAbility()
const { getAbilities } = useAuth()
const activityId = inject('activityId')
const teamId = inject('teamId')
const swal = inject('$swal', null)
const toast = useToast()

const {
  backlogItems,
  isLoading,
  getBacklogItems,
  createBacklogItem,
  updateBacklogItem,
  deleteBacklogItem,
  hasError,
  getError,
  clearErrors,
  importBacklogCsv,
  reorderBacklogItems,
} = useActivityBacklogItems()

const {
  createTask,
  updateTask,
  deleteTask,
  hasError: taskHasError,
  getError: taskGetError,
  clearErrors: clearTaskErrors,
  reorderTasks,
} = useActivityTasks()

const canEditTeamBacklog = computed(() => !!teamId?.value && !!activityId?.value)

const backlogNameQuery = ref('')
const backlogPriorityFilter = ref(null)
const backlogStatusFilter = ref(null)
const taskNameQuery = ref('')
const taskStatusFilter = ref(null)
const showTasks = ref(true)

const taskStatusFilterOptions = [
  { value: null, label: 'Todos' },
  { value: 0, label: 'Por hacer' },
  { value: 1, label: 'En progreso' },
  { value: 2, label: 'Hecha' },
  { value: 3, label: 'Cancelada' },
]

const taskFormStatusOptions = taskStatusFilterOptions.filter((o) => o.value !== null)

const priorityOptions = [
  { value: 1, label: 'Baja (P1)' },
  { value: 2, label: 'Media (P2)' },
  { value: 3, label: 'Alta (P3)' },
  { value: 4, label: 'Crítica (P4)' },
]

const statusOptions = [
  { value: 0, label: 'Backlog' },
  { value: 1, label: 'En curso' },
  { value: 2, label: 'Hecho' },
  { value: 3, label: 'Cancelado' },
]

const filteredItems = computed(() => {
  const tid = teamId?.value
  const list = backlogItems.value || []
  return list.filter((b) => {
    const bid = b.team_id ?? b.team?.id
    return bid == null || Number(bid) === Number(tid)
  })
})

const sortedFilteredItems = computed(() => {
  const list = [...filteredItems.value]
  list.sort((a, b) => {
    const pa = Number(a.position) || 0
    const pb = Number(b.position) || 0
    if (pa !== pb) return pa - pb
    return (a.id ?? 0) - (b.id ?? 0)
  })
  return list
})

function priorityValue(row) {
  const p = row?.priority
  return typeof p === 'object' && p !== null ? p.value : p
}

function statusValue(row) {
  const s = row?.status
  return typeof s === 'object' && s !== null ? s.value : s
}

const backlogFiltersActive = computed(
  () =>
    backlogNameQuery.value.trim() !== '' ||
    backlogPriorityFilter.value != null ||
    backlogStatusFilter.value != null
)

const taskFiltersActive = computed(
  () => taskNameQuery.value.trim() !== '' || taskStatusFilter.value != null
)

const displayedBacklogRows = computed(() => {
  return sortedFilteredItems.value.filter((b) => {
    if (backlogNameQuery.value.trim()) {
      const q = backlogNameQuery.value.trim().toLowerCase()
      if (!(b.title || '').toLowerCase().includes(q)) return false
    }
    if (backlogPriorityFilter.value != null) {
      if (Number(priorityValue(b)) !== Number(backlogPriorityFilter.value)) return false
    }
    if (backlogStatusFilter.value != null) {
      if (Number(statusValue(b)) !== Number(backlogStatusFilter.value)) return false
    }
    return true
  })
})

const sharedRows = computed(() =>
  sortedFilteredItems.value.filter((b) => (b.team_id ?? b.team?.id) == null)
)

const teamDraggableList = ref([])

const teamItemsOrdered = computed(() => {
  const tid = Number(teamId?.value)
  return sortedFilteredItems.value.filter((b) => Number(b.team_id ?? b.team?.id) === tid)
})

watch(
  [teamItemsOrdered, backlogFiltersActive],
  () => {
    if (backlogFiltersActive.value) return
    teamDraggableList.value = teamItemsOrdered.value.map((b) => ({ ...b }))
  },
  { deep: true, immediate: true }
)

async function onTeamBacklogDragEnd() {
  const aid = activityId?.value
  const tid = teamId?.value
  if (!aid || !tid || !can('backlogitem-edit')) return
  const ids = teamDraggableList.value.map((b) => b.id)
  try {
    await reorderBacklogItems(aid, { team_id: Number(tid), ids })
    await getBacklogItems(aid)
  } catch {
    toast.error('Error', 'No se pudo guardar el orden del backlog.')
    try {
      await getBacklogItems(aid)
    } catch {
      /* */
    }
  }
}

/** Tareas por id de ítem (para drag); se sincroniza con la API. */
const taskLists = reactive({})

function normTask(t) {
  if (!t?.id) return null
  return {
    id: t.id,
    backlog_item_id: t.backlog_item_id,
    title: t.title ?? '',
    description: t.description ?? '',
    status: typeof t.status === 'object' && t.status ? t.status.value : Number(t.status ?? 0),
    position: Number(t.position) || 0,
  }
}

function syncTaskListsFromBacklog() {
  const list = backlogItems.value || []
  for (const bi of list) {
    const bid = bi.id
    const raw = bi.tasks
    const arr = Array.isArray(raw) ? raw.map(normTask).filter(Boolean) : []
    arr.sort((a, b) => (a.position || 0) - (b.position || 0) || a.id - b.id)
    taskLists[bid] = arr
  }
}

watch(
  () => backlogItems.value,
  () => syncTaskListsFromBacklog(),
  { deep: true, immediate: true }
)

function taskStatusNum(t) {
  return typeof t.status === 'object' && t.status ? t.status.value : Number(t.status ?? 0)
}

function filteredTasksForBi(backlogItemId) {
  const list = taskLists[backlogItemId] || []
  return list.filter((t) => {
    if (taskNameQuery.value.trim()) {
      const q = taskNameQuery.value.trim().toLowerCase()
      if (!(t.title || '').toLowerCase().includes(q)) return false
    }
    if (taskStatusFilter.value != null) {
      if (Number(taskStatusNum(t)) !== Number(taskStatusFilter.value)) return false
    }
    return true
  })
}

async function onTaskDragEnd(backlogItemId) {
  const aid = activityId?.value
  const tid = teamId?.value
  if (!aid || !tid || taskFiltersActive.value) return
  const ids = (taskLists[backlogItemId] || []).map((t) => t.id)
  if (!ids.length) return
  try {
    await reorderTasks(aid, {
      team_id: Number(tid),
      backlog_item_id: backlogItemId,
      ids,
    })
    await getBacklogItems(aid)
  } catch {
    toast.error('Error', 'No se pudo guardar el orden de las tareas.')
  }
}

function scopeLabel(row) {
  const bid = row?.team_id ?? row?.team?.id
  if (bid == null) return 'Compartido'
  return 'Este equipo'
}

function isTeamRow(row) {
  return Number(row?.team_id ?? row?.team?.id) === Number(teamId?.value)
}

const teamItemsOnly = computed(() => {
  const tid = Number(teamId?.value)
  return (backlogItems.value || []).filter((b) => Number(b.team_id ?? b.team?.id) === tid)
})

const csvFileInput = useTemplateRef('csvFileInput')

const importDialog = reactive({
  open: false,
  text: '',
  running: false,
})

function onCsvFileSelected(event) {
  const input = event.target
  const file = input?.files?.[0]
  if (!file) return
  const reader = new FileReader()
  reader.onload = () => {
    importDialog.text = typeof reader.result === 'string' ? reader.result : ''
  }
  reader.readAsText(file, 'UTF-8')
  input.value = ''
}

const itemDialog = reactive({
  open: false,
  mode: 'create',
  saving: false,
})

const itemForm = ref({
  id: null,
  title: '',
  description: '',
  priority: 2,
  status: 0,
  points: null,
  position: 0,
})

const taskDialog = reactive({
  open: false,
  mode: 'create',
  saving: false,
  backlogItem: null,
  taskId: null,
})

const taskForm = ref({
  title: '',
  description: '',
  status: 0,
})

function openImportDialog() {
  importDialog.text = ''
  importDialog.open = true
}

function resetImportDialog() {
  importDialog.text = ''
  importDialog.running = false
}

function openCreateDialog() {
  clearErrors()
  itemDialog.mode = 'create'
  itemForm.value = {
    id: null,
    title: '',
    description: '',
    priority: 2,
    status: 0,
    points: null,
    position: nextSuggestedPosition(),
  }
  itemDialog.open = true
}

function nextSuggestedPosition() {
  const nums = teamItemsOnly.value.map((b) => Number(b.position) || 0)
  return nums.length ? Math.max(...nums) + 1 : 1
}

function openEditDialog(row) {
  if (!isTeamRow(row)) return
  clearErrors()
  itemDialog.mode = 'edit'
  itemForm.value = {
    id: row.id,
    title: row.title ?? '',
    description: row.description ?? '',
    priority: priorityValue(row) ?? 2,
    status: statusValue(row) ?? 0,
    points: row.points ?? null,
    position: row.position ?? 0,
  }
  itemDialog.open = true
}

function closeItemDialog() {
  itemDialog.saving = false
  clearErrors()
}

function buildApiPayload(form) {
  return {
    team_id: teamId.value,
    title: form.title.trim(),
    description: form.description?.trim() || null,
    priority: form.priority,
    points: form.points ?? null,
    status: form.status,
    position: form.position ?? 0,
  }
}

async function submitItemDialog() {
  const aid = activityId?.value
  if (!aid) return
  itemDialog.saving = true
  clearErrors()
  try {
    const payload = buildApiPayload(itemForm.value)
    if (itemDialog.mode === 'create') {
      await createBacklogItem(aid, payload)
    } else if (itemForm.value.id) {
      await updateBacklogItem(aid, itemForm.value.id, payload)
    }
    await getBacklogItems(aid)
    itemDialog.open = false
  } catch {
    /* composable */
  } finally {
    itemDialog.saving = false
  }
}

function confirmDelete(row) {
  if (!isTeamRow(row)) return
  const run = async () => {
    const aid = activityId?.value
    if (!aid) return
    try {
      await deleteBacklogItem(aid, row.id)
      await getBacklogItems(aid)
    } catch {
      /* toast */
    }
  }
  if (!swal) {
    if (typeof window !== 'undefined' && window.confirm('¿Eliminar este ítem de backlog?')) run()
    return
  }
  swal({
    icon: 'warning',
    title: '¿Eliminar ítem?',
    text: `Se eliminará «${row.title || 'sin título'}».`,
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#ef4444',
  }).then((result) => {
    if (result.isConfirmed) run()
  })
}

async function runImport() {
  const aid = activityId?.value
  const tid = teamId?.value
  if (!aid || !tid) return
  if (!importDialog.text?.trim()) {
    toast.error('CSV vacío', 'Pega filas copiadas desde Excel o un CSV.')
    return
  }
  importDialog.running = true
  try {
    const data = await importBacklogCsv(aid, {
      csv: importDialog.text,
      team_id: Number(tid),
    })
    await getBacklogItems(aid)
    const parts = [`${data.created ?? 0} creado(s)`, `${data.updated ?? 0} actualizado(s)`]
    if (data.skipped) parts.push(`${data.skipped} fila(s) omitida(s)`)
    let detail = parts.join(', ')
    if (Array.isArray(data.errors) && data.errors.length) {
      const preview = data.errors
        .slice(0, 5)
        .map((e) => `Fila ${e.row}: ${e.message}`)
        .join(' · ')
      detail += `. ${preview}${data.errors.length > 5 ? '…' : ''}`
    }
    toast.success('Importación CSV', detail)
    importDialog.open = false
    importDialog.text = ''
  } catch (e) {
    const body = e?.response?.data
    let msg = body?.message || 'No se pudo importar el CSV.'
    if (body?.errors && typeof body.errors === 'object') {
      const first = Object.values(body.errors).flat()[0]
      if (first) msg = Array.isArray(first) ? first[0] : first
    }
    toast.error('Error', typeof msg === 'string' ? msg : 'Error al importar')
  } finally {
    importDialog.running = false
  }
}

function openTaskDialog(bi, task) {
  clearTaskErrors()
  taskDialog.backlogItem = bi
  if (task) {
    taskDialog.mode = 'edit'
    taskDialog.taskId = task.id
    taskForm.value = {
      title: task.title ?? '',
      description: task.description ?? '',
      status: taskStatusNum(task),
    }
  } else {
    taskDialog.mode = 'create'
    taskDialog.taskId = null
    taskForm.value = {
      title: '',
      description: '',
      status: 0,
    }
  }
  taskDialog.open = true
}

function closeTaskDialog() {
  taskDialog.saving = false
  taskDialog.backlogItem = null
  taskDialog.taskId = null
  clearTaskErrors()
}

async function submitTaskDialog() {
  const aid = activityId?.value
  const bi = taskDialog.backlogItem
  if (!aid || !bi?.id) return
  taskDialog.saving = true
  clearTaskErrors()
  try {
    if (taskDialog.mode === 'create') {
      const nextPos =
        (taskLists[bi.id] || []).reduce((m, t) => Math.max(m, Number(t.position) || 0), -1) + 1
      await createTask(aid, {
        backlog_item_id: bi.id,
        title: taskForm.value.title.trim(),
        description: taskForm.value.description || null,
        status: taskForm.value.status ?? 0,
        position: nextPos,
      })
    } else if (taskDialog.taskId) {
      await updateTask(aid, taskDialog.taskId, {
        backlog_item_id: bi.id,
        title: taskForm.value.title.trim(),
        description: taskForm.value.description || null,
        status: taskForm.value.status ?? 0,
        position:
          (taskLists[bi.id] || []).find((t) => t.id === taskDialog.taskId)?.position ?? 0,
      })
    }
    await getBacklogItems(aid)
    taskDialog.open = false
  } catch {
    /* composable */
  } finally {
    taskDialog.saving = false
  }
}

function confirmDeleteTask(task) {
  const run = async () => {
    const aid = activityId?.value
    if (!aid || !task?.id) return
    try {
      await deleteTask(aid, task.id)
      await getBacklogItems(aid)
    } catch {
      /* */
    }
  }
  if (!swal) {
    if (typeof window !== 'undefined' && window.confirm('¿Eliminar esta tarea?')) run()
    return
  }
  swal({
    icon: 'warning',
    title: '¿Eliminar tarea?',
    text: `Se eliminará «${task.title || 'sin título'}».`,
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#ef4444',
  }).then((result) => {
    if (result.isConfirmed) run()
  })
}

watch(
  () => activityId?.value,
  async (id) => {
    if (!id) return
    try {
      await getBacklogItems(id)
    } catch {
      /* */
    }
  },
  { immediate: true }
)

/** Permisos recientes (p. ej. tras migración) sin cerrar sesión. */
onMounted(() => {
  getAbilities().catch(() => {})
})
</script>
