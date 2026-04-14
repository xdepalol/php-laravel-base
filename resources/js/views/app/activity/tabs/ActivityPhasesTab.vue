<template>
  <Card>
    <template #title>
      <div class="flex flex-wrap items-center justify-between gap-3 w-full">
        <span>Fases</span>
        <div v-if="canList" class="flex flex-wrap gap-2">
          <Button
            v-if="canCreate"
            label="Importar CSV / Excel"
            icon="pi pi-file-import"
            severity="secondary"
            outlined
            size="small"
            :disabled="isLoading"
            @click="importDialog.open = true"
          />
          <Button
            v-if="canCreate"
            label="Nueva fase"
            icon="pi pi-plus"
            size="small"
            :disabled="isLoading"
            @click="goCreate"
          />
        </div>
      </div>
    </template>
    <template #content>
      <p v-if="canList" class="text-sm text-slate-600 mb-4">
        Fases y sprints de la actividad (calendario compartido). La importación masiva usa el mismo
        enfoque que el backlog: CSV o pegado desde Excel; el servidor interpreta cabeceras o columnas
        fijas (título, sprint, inicio, fin).
      </p>
      <p v-else class="text-sm text-amber-800 mb-4">
        No tienes permiso para listar fases de esta actividad.
      </p>

      <DataTable
        v-if="canList"
        :value="phases"
        :loading="isLoading"
        data-key="id"
        striped-rows
        class="text-sm"
      >
        <template #empty>
          <span class="text-slate-500">No hay fases definidas.</span>
        </template>
        <Column field="title" header="Título">
          <template #body="{ data }">
            <router-link
              v-if="canView"
              :to="phaseShowLink(data.id)"
              class="text-blue-700 hover:underline font-medium"
            >
              {{ data.title || `Fase #${data.id}` }}
            </router-link>
            <span v-else class="font-medium text-slate-800">{{ data.title || `Fase #${data.id}` }}</span>
          </template>
        </Column>
        <Column header="Sprint" class="w-28">
          <template #body="{ data }">
            <Tag v-if="data.is_sprint" value="Sí" severity="info" />
            <span v-else class="text-slate-500">No</span>
          </template>
        </Column>
        <Column header="Inicio" class="whitespace-nowrap w-32">
          <template #body="{ data }">
            <UtcFormatted :value="data.start_date" variant="date" />
          </template>
        </Column>
        <Column header="Fin" class="whitespace-nowrap w-32">
          <template #body="{ data }">
            <UtcFormatted :value="data.end_date" variant="date" />
          </template>
        </Column>
        <Column
          header="Tareas / roles"
          class="w-36 hidden md:table-cell"
          header-class="hidden md:table-cell"
        >
          <template #body="{ data }">
            <span class="text-slate-600">
              {{ (data.phase_tasks?.length ?? 0) }} / {{ (data.phase_student_roles?.length ?? 0) }}
            </span>
          </template>
        </Column>
        <Column v-if="canDelete" class="w-14 text-right">
          <template #header>
            <span class="sr-only">Eliminar</span>
          </template>
          <template #body="{ data }">
            <Button
              v-tooltip.top="'Eliminar fase'"
              icon="pi pi-trash"
              rounded
              text
              severity="danger"
              size="small"
              :aria-label="`Eliminar ${data.title || 'fase'}`"
              @click="confirmDelete(data)"
            />
          </template>
        </Column>
      </DataTable>

      <Dialog
        v-model:visible="importDialog.open"
        modal
        header="Importar fases (CSV / Excel)"
        class="w-full max-w-2xl"
        :closable="!importDialog.running"
        @hide="resetImportDialog"
      >
        <p class="text-sm text-slate-600 mb-3">
          Pega datos copiados desde Excel o un CSV. Se detecta el separador (tabulador, coma o punto y
          coma). Si la primera fila parece cabecera, se enlazan columnas por nombre.
        </p>
        <p class="text-xs text-slate-600 mb-2 font-medium">Columnas reconocidas</p>
        <ul class="text-xs text-slate-500 mb-3 list-disc pl-5 space-y-0.5">
          <li>
            <strong>title / título / nombre</strong> — obligatorio por fila.
          </li>
          <li>
            <strong>is_sprint / sprint</strong> — opcional (1, sí, true, sprint…).
          </li>
          <li>
            <strong>start_date / inicio</strong> y <strong>end_date / fin</strong> — opcional (YYYY-MM-DD o
            DD/MM/AAAA).
          </li>
        </ul>
        <p class="text-xs text-slate-500 mb-2">
          Sin cabecera: orden fijo
          <code class="bg-slate-100 px-1 rounded">título, sprint, inicio, fin</code>
          (las tres últimas columnas son opcionales).
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
            @click="csvFileInput?.click()"
          />
        </div>
        <Textarea
          v-model="importDialog.text"
          class="w-full font-mono text-sm"
          rows="12"
          :disabled="importDialog.running"
          placeholder="Ej. con cabecera:&#10;title&#9;is_sprint&#9;start_date&#9;end_date&#10;Sprint 1&#9;1&#9;2025-09-01&#9;2025-09-14"
        />
        <template #footer>
          <Button
            label="Cancelar"
            severity="secondary"
            text
            :disabled="importDialog.running"
            @click="importDialog.open = false"
          />
          <Button
            label="Importar"
            icon="pi pi-check"
            :loading="importDialog.running"
            :disabled="!importDialog.text?.trim()"
            @click="runImport"
          />
        </template>
      </Dialog>
    </template>
  </Card>
</template>

<script setup>
import { computed, inject, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAbility } from '@casl/vue'
import useActivityPhases from '@/composables/activityPhases'
import { useToast } from '@/composables/useToast'

const { can } = useAbility()
const canList = computed(() => can('phase-list'))
const canCreate = computed(() => can('phase-create'))
const canDelete = computed(() => can('phase-delete'))
const canView = computed(() => can('phase-view'))

const route = useRoute()
const router = useRouter()
const activityId = inject('activityId')
const toast = useToast()
const swal = inject('$swal', null)

const {
  phases,
  isLoading,
  getPhases,
  deletePhase,
  importPhasesCsv,
} = useActivityPhases()

const tabQuery = computed(() => {
  const raw = route.query.fromSubjectGroup
  if (raw == null || raw === '') return {}
  return { fromSubjectGroup: String(raw) }
})

function phaseShowLink(phaseId) {
  return {
    name: 'app.activity.phase.show',
    params: { activityId: activityId?.value, phaseId: String(phaseId) },
    query: { ...tabQuery.value },
  }
}

function goCreate() {
  router.push({
    name: 'app.activity.phase.create',
    params: { activityId: activityId?.value },
    query: { ...tabQuery.value },
  })
}

const importDialog = reactive({
  open: false,
  text: '',
  running: false,
})
const csvFileInput = ref(null)

function resetImportDialog() {
  if (importDialog.running) return
  importDialog.text = ''
}

function onCsvFileSelected(e) {
  const file = e.target.files?.[0]
  if (!file || !file.text) return
  file.text().then((t) => {
    importDialog.text = t
  })
  e.target.value = ''
}

async function runImport() {
  const aid = activityId?.value
  if (!aid || !importDialog.text?.trim()) {
    toast.error('CSV vacío', 'Pega filas copiadas desde Excel o un CSV.')
    return
  }
  importDialog.running = true
  try {
    const data = await importPhasesCsv(aid, importDialog.text)
    await getPhases(aid)
    const parts = [`${data.created ?? 0} creada(s)`]
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
  } catch {
    /* toast en composable */
  } finally {
    importDialog.running = false
  }
}

async function confirmDelete(row) {
  if (!row?.id) return
  const aid = activityId?.value
  if (!aid) return
  const title = row.title || `Fase #${row.id}`

  const run = async () => {
    try {
      await deletePhase(aid, row.id)
    } catch {
      /* toast en composable */
    }
  }

  if (!swal) {
    if (typeof window !== 'undefined' && window.confirm(`¿Eliminar «${title}»?`)) run()
    return
  }

  swal({
    icon: 'warning',
    title: '¿Eliminar fase?',
    text: `Se eliminará «${title}» y sus datos asociados.`,
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
    if (!id || !canList.value) return
    try {
      await getPhases(id)
    } catch {
      /* toast en composable */
    }
  },
  { immediate: true }
)
</script>
