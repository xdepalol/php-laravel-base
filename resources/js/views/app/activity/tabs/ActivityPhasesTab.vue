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
        <Column
          v-if="showStudentPhaseSelfRoleColumn"
          header="Mi rol (fase)"
          class="min-w-[12rem] hidden lg:table-cell"
          header-class="hidden lg:table-cell"
        >
          <template #body="{ data }">
            <template v-if="data.teams_may_assign_phase_roles && activityRoleOptions.length">
              <Select
                :model-value="myPhaseRoleId(data)"
                :options="activityRoleOptions"
                option-label="name"
                option-value="id"
                placeholder="Sin rol"
                show-clear
                class="w-full text-sm"
                :disabled="savingPhaseId === data.id"
                @update:model-value="(v) => onMyPhaseRoleChange(data, v)"
              />
            </template>
            <span v-else class="text-slate-400 text-xs">—</span>
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

      <div
        v-if="showStudentPhaseSelfRoleColumn && phaseRoleWarningMessages.length"
        class="mt-4 rounded-lg border border-amber-200 bg-amber-50/80 px-3 py-2 text-sm text-amber-950"
      >
        <p class="font-medium mb-1">Avisos sobre roles (no bloquean el guardado)</p>
        <ul class="list-disc pl-5 space-y-0.5">
          <li v-for="(w, i) in phaseRoleWarningMessages" :key="i">{{ w }}</li>
        </ul>
      </div>
    </template>
  </Card>
</template>

<script setup>
import { computed, inject, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAbility } from '@casl/vue'
import useActivityPhases from '@/composables/activityPhases'
import useActivityTeams from '@/composables/activityTeams'
import { useToast } from '@/composables/useToast'
import { authStore } from '@/store/auth'
import { activityRoleAssignmentWarnings } from '@/utils/activityRoleWarnings'

const { can } = useAbility()
const canList = computed(() => can('phase-list'))
const canCreate = computed(() => can('phase-create'))
const canDelete = computed(() => can('phase-delete'))
const canView = computed(() => can('phase-view'))

const route = useRoute()
const router = useRouter()
const activityId = inject('activityId')
/** En espacio de equipo, enlaces de fase van al detalle por equipo. */
const teamId = inject('teamId', null)
const activityRef = inject('activity', null)
const toast = useToast()
const swal = inject('$swal', null)
const auth = authStore()

const {
  phases,
  isLoading,
  getPhases,
  deletePhase,
  importPhasesCsv,
} = useActivityPhases()

const { getTeamMemberRoles, patchMyPhaseStudentRole } = useActivityTeams()

const activityRoleOptions = ref([])
const savingPhaseId = ref(null)

const showStudentPhaseSelfRoleColumn = computed(() => {
  const tid = teamId?.value
  const act = activityRef?.value
  if (!tid || !act?.activity_role_type_id) return false
  if (can('phase-edit')) return false
  return true
})

const phaseRoleWarningMessages = computed(() => {
  if (!showStudentPhaseSelfRoleColumn.value || !activityRoleOptions.value.length) return []
  const tid = Number(teamId?.value)
  const msgs = []
  for (const phase of phases.value || []) {
    const rows = (phase.phase_student_roles || []).filter((r) => Number(r.team_id) === tid)
    const assigns = rows.map((r) => ({ activity_role_id: r.activity_role_id }))
    msgs.push(...activityRoleAssignmentWarnings(assigns, activityRoleOptions.value))
  }
  return [...new Set(msgs)]
})

const tabQuery = computed(() => {
  const raw = route.query.fromSubjectGroup
  if (raw == null || raw === '') return {}
  return { fromSubjectGroup: String(raw) }
})

function phaseShowLink(phaseId) {
  const tid = teamId?.value
  if (tid) {
    return {
      name: 'app.activity.team.phase.show',
      params: {
        activityId: String(activityId?.value),
        teamId: String(tid),
        phaseId: String(phaseId),
      },
      query: { ...tabQuery.value },
    }
  }
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

async function loadPhaseRoleOptions() {
  const aid = activityId?.value
  if (!aid || !showStudentPhaseSelfRoleColumn.value) {
    activityRoleOptions.value = []
    return
  }
  try {
    activityRoleOptions.value = await getTeamMemberRoles(aid)
  } catch {
    activityRoleOptions.value = []
  }
}

function myPhaseRoleId(phase) {
  const uid = auth.user?.id
  const tid = Number(teamId?.value)
  if (!uid || !tid) return null
  const row = (phase.phase_student_roles || []).find(
    (r) => Number(r.student_id) === Number(uid) && Number(r.team_id) === tid
  )
  return row?.activity_role_id ?? null
}

async function onMyPhaseRoleChange(phase, activity_role_id) {
  const aid = activityId?.value
  const tid = teamId?.value
  if (!aid || !tid || !phase?.id) return
  savingPhaseId.value = phase.id
  try {
    await patchMyPhaseStudentRole(aid, phase.id, tid, activity_role_id ?? null)
    await getPhases(aid)
  } catch {
    /* toast en composable */
  } finally {
    savingPhaseId.value = null
  }
}

watch(
  () => [
    activityId?.value,
    teamId?.value,
    activityRef?.value?.id,
    activityRef?.value?.activity_role_type_id,
    canList.value,
  ],
  async ([id]) => {
    if (!id || !canList.value) return
    try {
      await getPhases(id)
      await loadPhaseRoleOptions()
    } catch {
      /* toast en composable */
    }
  },
  { immediate: true }
)
</script>
