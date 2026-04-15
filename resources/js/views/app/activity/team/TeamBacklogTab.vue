<template>
  <Card>
    <template #title>
      <div class="flex flex-wrap items-center justify-between gap-3">
        <span>Backlog</span>
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
        Ítems del backlog compartido (solo lectura) y los de este equipo (editables). La importación masiva
        (CSV / pegado desde Excel) solo afecta a ítems del equipo y la procesa el servidor con League CSV.
      </p>

      <DataTable
        :value="sortedFilteredItems"
        :loading="isLoading"
        data-key="id"
        striped-rows
        class="text-sm"
      >
        <template #empty>
          <span class="text-slate-500">No hay ítems que mostrar.</span>
        </template>
        <Column field="position" header="Pos." class="w-16 text-center" />
        <Column field="title" header="Título" />
        <Column header="Ámbito" class="w-36">
          <template #body="{ data }">
            {{ scopeLabel(data) }}
          </template>
        </Column>
        <Column header="Prioridad" class="w-28">
          <template #body="{ data }">
            {{ backlogPriorityLabel(data.priority) }}
          </template>
        </Column>
        <Column header="Estado" class="w-36">
          <template #body="{ data }">
            <Tag :value="backlogStatusLabel(data)" severity="secondary" />
          </template>
        </Column>
        <Column field="points" header="Pts" class="w-20">
          <template #body="{ data }">
            {{ data.points ?? '—' }}
          </template>
        </Column>
        <Column class="w-28 text-end">
          <template #body="{ data }">
            <template v-if="isTeamRow(data)">
              <Button
                v-tooltip.top="'Editar'"
                icon="pi pi-pencil"
                text
                rounded
                severity="secondary"
                :disabled="!canEditTeamBacklog"
                @click="openEditDialog(data)"
              />
              <Button
                v-tooltip.top="'Eliminar'"
                icon="pi pi-trash"
                text
                rounded
                severity="danger"
                :disabled="!canEditTeamBacklog"
                @click="confirmDelete(data)"
              />
            </template>
          </template>
        </Column>
      </DataTable>

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
        <p class="text-xs text-slate-500 mb-2">
          Sin cabecera: se asume orden fijo
          <code class="bg-slate-100 px-1 rounded">posición, título, descripción, prioridad, puntos, estado</code>
          (las columnas finales son opcionales). Los .xlsx no se suben tal cual: exporta a CSV o copia y pega las
          celdas (Excel suele usar tabuladores).
        </p>
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
            @click="csvFileInput?.value?.click()"
          />
        </div>
        <Textarea
          v-model="importDialog.text"
          class="w-full font-mono text-sm"
          rows="12"
          :disabled="importDialog.running"
          placeholder="Ej. con cabecera (Excel):&#10;position&#9;title&#9;description&#10;1&#9;Historia de usuario&#9;Como…&#10;2&#9;Otra&#9;&#10;&#10;Ej. sin cabecera:&#10;1,Mi ítem,Descripción opcional,2,5,0"
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
            <Textarea v-model="itemForm.description" class="w-full" rows="4" />
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
          <Button
            label="Guardar"
            icon="pi pi-check"
            :loading="itemDialog.saving"
            @click="submitItemDialog"
          />
        </template>
      </Dialog>
    </template>
  </Card>
</template>

<script setup>
import { computed, inject, reactive, ref, useTemplateRef, watch } from 'vue'
import { useToast } from '@/composables/useToast'
import useActivityBacklogItems from '@/composables/activityBacklogItems'

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
} = useActivityBacklogItems()

const canEditTeamBacklog = computed(() => !!teamId?.value && !!activityId?.value)

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

const teamItemsOnly = computed(() => {
  const tid = Number(teamId?.value)
  return (backlogItems.value || []).filter((b) => Number(b.team_id ?? b.team?.id) === tid)
})

const BACKLOG_STATUS_LABELS = {
  0: 'Backlog',
  1: 'En curso',
  2: 'Hecho',
  3: 'Cancelado',
}

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

function scopeLabel(row) {
  const bid = row?.team_id ?? row?.team?.id
  if (bid == null) return 'Compartido'
  return 'Este equipo'
}

function isTeamRow(row) {
  return Number(row?.team_id ?? row?.team?.id) === Number(teamId?.value)
}

function backlogPriorityLabel(p) {
  const v = typeof p === 'object' && p !== null ? p.value : p
  if (v == null) return '—'
  return `P${v}`
}

function backlogStatusLabel(row) {
  const s = row?.status
  const v = typeof s === 'object' && s !== null ? s.value : s
  if (v == null) return '—'
  return BACKLOG_STATUS_LABELS[v] ?? String(v)
}

function rowToForm(row) {
  return {
    id: row.id,
    title: row.title ?? '',
    description: row.description ?? '',
    priority: typeof row.priority === 'object' && row.priority !== null ? row.priority.value : row.priority ?? 2,
    status: typeof row.status === 'object' && row.status !== null ? row.status.value : row.status ?? 0,
    points: row.points ?? null,
    position: row.position ?? 0,
  }
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
  itemForm.value = rowToForm(row)
  itemDialog.open = true
}

function closeItemDialog() {
  itemDialog.saving = false
  clearErrors()
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
    /* toasts en composable */
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
      /* toast en composable */
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

watch(
  () => activityId?.value,
  async (id) => {
    if (!id) return
    try {
      await getBacklogItems(id)
    } catch {
      /* toast en composable */
    }
  },
  { immediate: true }
)
</script>
