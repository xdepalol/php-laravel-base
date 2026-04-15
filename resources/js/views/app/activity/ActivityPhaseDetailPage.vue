<template>
  <Card>
    <template #title>
      <div class="flex flex-wrap items-center justify-between gap-3 w-full">
        <div class="min-w-0">
          <span>{{ row.title || 'Fase' }}</span>
          <p v-if="contextTeamId && teamName" class="text-xs font-normal text-slate-500 mt-1 truncate">
            Equipo: {{ teamName }}
          </p>
        </div>
        <div class="flex flex-wrap gap-2">
          <Button
            v-if="canEdit"
            label="Editar"
            icon="pi pi-pencil"
            size="small"
            outlined
            @click="goEdit"
          />
          <Button
            v-if="canDelete"
            label="Eliminar"
            icon="pi pi-trash"
            size="small"
            severity="danger"
            outlined
            @click="confirmDelete"
          />
        </div>
      </div>
    </template>
    <template #content>
      <div v-if="loading" class="flex justify-center py-12 text-blue-600">
        <i class="pi pi-spin pi-spinner text-2xl" aria-hidden="true" />
      </div>
      <div v-else-if="loadError" class="text-sm text-red-600">No se pudo cargar la fase.</div>
      <div v-else class="space-y-6 text-sm">
        <dl class="grid gap-4 sm:grid-cols-2">
          <div>
            <dt class="text-slate-500 font-medium">Sprint</dt>
            <dd class="mt-1">
              <Tag v-if="row.is_sprint" value="Sí" severity="info" />
              <span v-else class="text-slate-600">No</span>
            </dd>
          </div>
          <div>
            <dt class="text-slate-500 font-medium">Actividad</dt>
            <dd class="mt-1 text-slate-800">{{ activityTitle }}</dd>
          </div>
          <div>
            <dt class="text-slate-500 font-medium">Inicio</dt>
            <dd class="mt-1">
              <UtcFormatted :value="row.start_date" variant="date" />
            </dd>
          </div>
          <div>
            <dt class="text-slate-500 font-medium">Fin</dt>
            <dd class="mt-1">
              <UtcFormatted :value="row.end_date" variant="date" />
            </dd>
          </div>
          <div class="sm:col-span-2">
            <dt class="text-slate-500 font-medium">
              Los estudiantes del equipo pueden elegir su rol en esta fase
            </dt>
            <dd class="mt-1">
              <Tag v-if="row.teams_may_assign_phase_roles" value="Sí" severity="success" />
              <span v-else class="text-slate-600">No</span>
            </dd>
          </div>
        </dl>

        <div
          v-if="sprintWorkflowVisible"
          class="rounded-lg border border-indigo-200 bg-indigo-50/40 p-4 space-y-3"
        >
          <h3 class="text-sm font-semibold text-slate-800">Sprint del equipo</h3>
          <p class="text-xs text-slate-600">
            Estado:
            <strong>{{ sprintStatusLabel(sprintStatusValue) }}</strong>
          </p>

          <template v-if="sprintStatusValue === 3">
            <p class="text-xs text-slate-600">
              En retrospectiva: rellena los tres campos; son obligatorios para finalizar el sprint.
            </p>
            <div class="space-y-2">
              <div>
                <label class="text-xs font-medium text-slate-600 block mb-1">Qué fue bien</label>
                <Textarea v-model="retroDraft.well" rows="2" class="w-full" auto-resize />
              </div>
              <div>
                <label class="text-xs font-medium text-slate-600 block mb-1">Qué mejorar</label>
                <Textarea v-model="retroDraft.bad" rows="2" class="w-full" auto-resize />
              </div>
              <div>
                <label class="text-xs font-medium text-slate-600 block mb-1">Acciones / mejoras</label>
                <Textarea v-model="retroDraft.improve" rows="2" class="w-full" auto-resize />
              </div>
            </div>
            <Button
              label="Guardar retrospectiva"
              icon="pi pi-save"
              size="small"
              outlined
              :loading="sprintSaving && sprintSaveMode === 'retro'"
              :disabled="sprintSaving"
              @click="saveRetroDraft"
            />
          </template>

          <template v-if="canEdit">
            <div class="pt-1 border-t border-indigo-100">
              <label class="text-xs font-medium text-slate-600 block mb-1">Feedback del profesorado</label>
              <Textarea v-model="feedbackDraft" rows="3" class="w-full" auto-resize />
              <Button
                label="Guardar feedback"
                icon="pi pi-save"
                size="small"
                class="mt-2"
                outlined
                :loading="sprintSaving && sprintSaveMode === 'feedback'"
                :disabled="sprintSaving"
                @click="saveFeedbackDraft"
              />
            </div>
          </template>

          <div class="pt-1">
            <Button
              v-if="canAdvanceSprintDetail"
              :label="sprintAdvanceButtonLabel(sprintStatusValue)"
              icon="pi pi-forward"
              size="small"
              :loading="sprintSaving && sprintSaveMode === 'advance'"
              :disabled="sprintSaving || !canClickAdvanceSprintDetail"
              @click="advanceSprintFromDetail"
            />
          </div>
        </div>

        <template v-if="phaseTeamsForDisplay.length">
          <div
            v-for="pt in phaseTeamsForDisplay"
            :key="pt.id ?? `${pt.phase_id}-${pt.team_id}`"
            class="space-y-3"
          >
            <p
              v-if="!contextTeamId && phaseTeamsForDisplay.length > 1"
              class="text-sm font-semibold text-slate-800"
            >
              {{ pt.team?.name || `Equipo #${pt.team_id}` }}
            </p>

            <div
              v-if="
                (pt.retro_well || pt.retro_bad || pt.retro_improvement) &&
                !(sprintWorkflowVisible && sprintStatusValue === 3)
              "
              class="space-y-3"
            >
              <h3 class="text-sm font-semibold text-slate-800">Retrospectiva</h3>
              <div v-if="pt.retro_well" class="rounded-lg border border-slate-200 p-3">
                <p class="text-xs font-medium text-slate-500 mb-1">Qué fue bien</p>
                <p class="text-slate-700 whitespace-pre-wrap">{{ pt.retro_well }}</p>
              </div>
              <div v-if="pt.retro_bad" class="rounded-lg border border-slate-200 p-3">
                <p class="text-xs font-medium text-slate-500 mb-1">Qué mejorar</p>
                <p class="text-slate-700 whitespace-pre-wrap">{{ pt.retro_bad }}</p>
              </div>
              <div v-if="pt.retro_improvement" class="rounded-lg border border-slate-200 p-3">
                <p class="text-xs font-medium text-slate-500 mb-1">Acciones</p>
                <p class="text-slate-700 whitespace-pre-wrap">{{ pt.retro_improvement }}</p>
              </div>
            </div>

            <div
              v-if="pt.teacher_feedback"
              class="rounded-lg border border-amber-200 bg-amber-50/80 p-3"
            >
              <p class="text-xs font-medium text-amber-900 mb-1">Feedback del profesorado</p>
              <p class="text-slate-800 whitespace-pre-wrap">{{ pt.teacher_feedback }}</p>
            </div>
          </div>
        </template>

        <div class="pt-2 border-t border-slate-100">
          <h3 class="text-sm font-semibold text-slate-800 mb-2">
            Tareas en fase ({{ row.phase_tasks?.length ?? 0 }})
          </h3>
          <ul v-if="row.phase_tasks?.length" class="list-disc pl-5 text-slate-600 space-y-1">
            <li v-for="pt in row.phase_tasks" :key="pt.id">
              {{ pt.task?.title || `Tarea #${pt.task_id}` }}
            </li>
          </ul>
          <p v-else class="text-slate-500">Ninguna.</p>
        </div>

        <div v-if="contextTeamId" class="pt-2 border-t border-slate-100 space-y-3">
          <h3 class="text-sm font-semibold text-slate-800 mb-2">Roles por estudiante en esta fase (este equipo)</h3>

          <template v-if="canManagePhaseStudentRoles && activityHasRoleTypes">
            <p class="text-xs text-slate-500 mb-3">
              Pulsa el lápiz para asignar o cambiar el rol de la actividad de cada miembro en esta fase. Puedes
              dejar «Sin rol» y guardar para quitar la asignación.
            </p>
            <div v-if="assignmentsPanelLoading" class="flex justify-center py-8 text-slate-500">
              <i class="pi pi-spin pi-spinner text-xl" aria-hidden="true" />
            </div>
            <DataTable
              v-else
              :value="assignmentRows"
              data-key="rowKey"
              size="small"
              striped-rows
              class="text-sm"
            >
              <template #empty>
                <span class="text-slate-500">No hay miembros en este equipo.</span>
              </template>
              <Column header="Estudiante" class="min-w-[10rem]">
                <template #body="{ data }">
                  <span class="font-medium text-slate-800">{{ data.studentLabel }}</span>
                </template>
              </Column>
              <Column header="Rol" class="min-w-[8rem]">
                <template #body="{ data }">
                  {{ data.roleDisplayName }}
                </template>
              </Column>
              <Column class="w-16">
                <template #header>
                  <span class="sr-only">Cambiar rol</span>
                </template>
                <template #body="{ data }">
                  <Button
                    v-tooltip.top="'Cambiar rol'"
                    icon="pi pi-pencil"
                    rounded
                    text
                    severity="secondary"
                    size="small"
                    :aria-label="`Cambiar rol en fase de ${data.studentLabel}`"
                    @click="openPhaseRoleDialog(data)"
                  />
                </template>
              </Column>
            </DataTable>
          </template>

          <template v-else>
            <p v-if="!phaseStudentRolesForTeam.length" class="text-slate-500">Ninguno.</p>
            <ul v-else class="list-disc pl-5 text-slate-600 space-y-1">
              <li v-for="psr in phaseStudentRolesForTeam" :key="psr.id">
                {{ studentLabel(psr) }}
                <template v-if="psr.activity_role?.name"> — {{ psr.activity_role.name }}</template>
              </li>
            </ul>
          </template>
        </div>

        <Button label="Volver a la lista" icon="pi pi-arrow-left" severity="secondary" text @click="goList" />
      </div>
    </template>
  </Card>

  <Dialog
    v-model:visible="phaseRoleDialogVisible"
    modal
    header="Rol en la fase"
    :style="{ width: '420px' }"
    @hide="phaseRoleEditRow = null"
  >
    <div v-if="phaseRoleEditRow" class="flex flex-col gap-4">
      <p class="text-sm text-slate-700">
        <span class="font-medium">{{ phaseRoleEditRow.studentLabel }}</span>
      </p>
      <div>
        <label for="phase-member-role-select" class="text-sm font-medium text-slate-700 block mb-1">Rol</label>
        <Select
          id="phase-member-role-select"
          v-model="phaseRoleEditSelectedId"
          :options="assignmentRoleOptions"
          option-label="name"
          option-value="id"
          placeholder="Sin rol (asignar después)"
          show-clear
          class="w-full"
        />
      </div>
    </div>
    <template #footer>
      <Button label="Cancelar" severity="secondary" text @click="phaseRoleDialogVisible = false" />
      <Button
        label="Guardar"
        icon="pi pi-check"
        :loading="phaseRoleSaveLoading"
        :disabled="
          phaseRoleEditRow &&
          samePhaseRole(phaseRoleEditSelectedId, phaseRoleEditRow.savedRoleId)
        "
        @click="savePhaseRoleFromDialog"
      />
    </template>
  </Dialog>
</template>

<script setup>
import { computed, inject, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAbility } from '@casl/vue'
import useActivityPhases from '@/composables/activityPhases'
import useActivityTeams from '@/composables/activityTeams'
import usePhaseStudentRoles from '@/composables/phaseStudentRoles'
import { useToast } from '@/composables/useToast'
import { formatStudentDisplayName } from '@/utils/studentDisplayName'
import {
  nextSprintStatusValue,
  retroCompleteForFinish,
  sprintAdvanceButtonLabel,
  sprintStatusLabel,
} from '@/utils/phaseTeamSprint'

const route = useRoute()
const router = useRouter()
const { can } = useAbility()
const toast = useToast()
const activityRef = inject('activity')
const activityId = inject('activityId')
const teamRef = inject('team', null)

const canEdit = computed(() => can('phase-edit'))
const canDelete = computed(() => can('phase-delete'))

const canManagePhaseStudentRoles = computed(
  () =>
    can('phasestudentrole-create') &&
    can('phasestudentrole-edit') &&
    can('phasestudentrole-delete')
)

const activityHasRoleTypes = computed(() => !!activityRef?.value?.activity_role_type_id)

const { getPhase, deletePhase, patchPhaseTeam } = useActivityPhases()
const { getTeamStudentsList, getTeamMemberRoles } = useActivityTeams()
const { saveTeacherPhaseStudentAssignment } = usePhaseStudentRoles()

const loading = ref(true)
const loadError = ref(false)
const row = ref({})

const assignmentRows = ref([])
const assignmentRoleOptions = ref([])
const assignmentsPanelLoading = ref(false)
const phaseRoleDialogVisible = ref(false)
const phaseRoleEditRow = ref(null)
const phaseRoleEditSelectedId = ref(null)
const phaseRoleSaveLoading = ref(false)

const phaseId = computed(() => Number(route.params.phaseId) || 0)
const aid = computed(() => activityId?.value)
/** Solo en ruta `app.activity.team.phase.show` (detalle desde el espacio de un equipo). */
const contextTeamId = computed(() => {
  const raw = route.params.teamId
  const n = Number(raw)
  return Number.isFinite(n) && n > 0 ? n : 0
})

const teamName = computed(() => teamRef?.value?.name || '')

const phaseStudentRolesForTeam = computed(() => {
  const tid = contextTeamId.value
  if (!tid) return []
  return (row.value.phase_student_roles || []).filter((p) => Number(p.team_id) === tid)
})

const sprintWorkflowVisible = computed(() => {
  if (!contextTeamId.value || !row.value?.is_sprint || !activityRef?.value?.has_sprints) return false
  return can('phase-view') || can('phase-edit')
})

const phaseTeamSlice = computed(() => {
  const tid = contextTeamId.value
  const list = row.value.phase_teams || []
  return list.find((p) => Number(p.team_id) === tid) ?? null
})

const sprintStatusValue = computed(() => {
  const v = phaseTeamSlice.value?.sprint_status?.value
  return v !== undefined && v !== null ? Number(v) : 4
})

const canAdvanceSprintDetail = computed(
  () => sprintWorkflowVisible.value && (can('phase-view') || can('phase-edit'))
)

const sprintSaving = ref(false)
const sprintSaveMode = ref('')
const retroDraft = reactive({ well: '', bad: '', improve: '' })
const feedbackDraft = ref('')

function syncSprintDraftsFromRow() {
  const pt = phaseTeamSlice.value
  retroDraft.well = pt?.retro_well ?? ''
  retroDraft.bad = pt?.retro_bad ?? ''
  retroDraft.improve = pt?.retro_improvement ?? ''
  feedbackDraft.value = pt?.teacher_feedback ?? ''
}

watch(
  () => [
    row.value?.id,
    contextTeamId.value,
    phaseTeamSlice.value?.sprint_status?.value,
    phaseTeamSlice.value?.retro_well,
    phaseTeamSlice.value?.retro_bad,
    phaseTeamSlice.value?.retro_improvement,
    phaseTeamSlice.value?.teacher_feedback,
  ],
  () => syncSprintDraftsFromRow(),
  { immediate: true }
)

function canClickAdvanceSprintDetail() {
  const cur = sprintStatusValue.value
  if (nextSprintStatusValue(cur) === null) return false
  if (cur === 3) {
    return retroCompleteForFinish({
      retro_well: retroDraft.well,
      retro_bad: retroDraft.bad,
      retro_improvement: retroDraft.improve,
    })
  }
  return true
}

function api422FirstMessage(error, fallback) {
  const d = error?.response?.data
  if (d?.errors && typeof d.errors === 'object') {
    const first = Object.values(d.errors)[0]
    if (Array.isArray(first) && first[0]) return String(first[0])
  }
  if (typeof d?.message === 'string') return d.message
  return fallback
}

async function saveRetroDraft() {
  const aidVal = aid.value
  const tid = contextTeamId.value
  const pid = phaseId.value
  if (!aidVal || !tid || !pid || sprintStatusValue.value !== 3) return
  sprintSaveMode.value = 'retro'
  sprintSaving.value = true
  try {
    await patchPhaseTeam(aidVal, pid, tid, {
      retro_well: retroDraft.well || null,
      retro_bad: retroDraft.bad || null,
      retro_improvement: retroDraft.improve || null,
    })
    toast.success('Retrospectiva', 'Cambios guardados.')
    await load()
  } catch (e) {
    toast.error('Retrospectiva', api422FirstMessage(e, 'No se pudo guardar.'))
  } finally {
    sprintSaving.value = false
    sprintSaveMode.value = ''
  }
}

async function saveFeedbackDraft() {
  const aidVal = aid.value
  const tid = contextTeamId.value
  const pid = phaseId.value
  if (!aidVal || !tid || !pid || !canEdit.value) return
  sprintSaveMode.value = 'feedback'
  sprintSaving.value = true
  try {
    await patchPhaseTeam(aidVal, pid, tid, {
      teacher_feedback: feedbackDraft.value || null,
    })
    toast.success('Feedback', 'Guardado.')
    await load()
  } catch (e) {
    toast.error('Feedback', api422FirstMessage(e, 'No se pudo guardar el feedback.'))
  } finally {
    sprintSaving.value = false
    sprintSaveMode.value = ''
  }
}

async function advanceSprintFromDetail() {
  const aidVal = aid.value
  const tid = contextTeamId.value
  const pid = phaseId.value
  if (!aidVal || !tid || !pid) return
  const cur = sprintStatusValue.value
  const next = nextSprintStatusValue(cur)
  if (next === null) return
  sprintSaveMode.value = 'advance'
  sprintSaving.value = true
  try {
    const payload = { sprint_status: next }
    if (cur === 3) {
      payload.retro_well = retroDraft.well || null
      payload.retro_bad = retroDraft.bad || null
      payload.retro_improvement = retroDraft.improve || null
    }
    await patchPhaseTeam(aidVal, pid, tid, payload)
    toast.success('Sprint', `Estado: ${sprintStatusLabel(next)}`)
    await load()
  } catch (e) {
    toast.error('Sprint', api422FirstMessage(e, 'No se pudo avanzar el sprint.'))
  } finally {
    sprintSaving.value = false
    sprintSaveMode.value = ''
  }
}

/** Filas `phase_teams` visibles: una sola si la ruta lleva `teamId`, todas si es vista global. */
const phaseTeamsForDisplay = computed(() => {
  const list = row.value.phase_teams || []
  const tid = contextTeamId.value
  if (!tid) return list
  return list.filter((p) => Number(p.team_id) === tid)
})

const tabQuery = computed(() => {
  const raw = route.query.fromSubjectGroup
  if (raw == null || raw === '') return {}
  return { fromSubjectGroup: String(raw) }
})

const activityTitle = computed(() => {
  const a = activityRef?.value
  return a?.title || `Actividad #${a?.id ?? ''}`
})

function studentLabel(psr) {
  return formatStudentDisplayName(psr.student, psr.student_id)
}

function samePhaseRole(a, b) {
  const na = a == null || a === '' ? null : Number(a)
  const nb = b == null || b === '' ? null : Number(b)
  if (na === null && nb === null) return true
  if (na === null || nb === null) return false
  return na === nb
}

function roleNameById(roleId) {
  if (roleId == null || roleId === '') return null
  const id = Number(roleId)
  const opt = assignmentRoleOptions.value.find((r) => Number(r.id) === id)
  return opt?.name ?? null
}

async function loadAssignmentEditor() {
  const activityIdVal = aid.value
  const pid = phaseId.value
  const tid = contextTeamId.value
  if (
    !activityIdVal ||
    !pid ||
    !tid ||
    !canManagePhaseStudentRoles.value ||
    !activityHasRoleTypes.value
  ) {
    assignmentRows.value = []
    return
  }
  assignmentsPanelLoading.value = true
  try {
    assignmentRoleOptions.value = await getTeamMemberRoles(activityIdVal)
    const psrs = row.value.phase_student_roles || []
    const members = await getTeamStudentsList(activityIdVal, tid)
    const rows = []
    for (const m of members) {
      const sid = Number(m.student_id)
      const psr = psrs.find(
        (p) => Number(p.student_id) === sid && Number(p.team_id) === tid
      )
      const saved = psr?.activity_role_id ?? null
      const roleDisplayName =
        psr?.activity_role?.name ?? roleNameById(saved) ?? '—'
      rows.push({
        rowKey: `${tid}-${sid}`,
        team_id: tid,
        student_id: sid,
        studentLabel: formatStudentDisplayName(
          m.student ?? { user: m.student?.user ?? m.user, student_number: m.student?.student_number },
          sid
        ),
        psr_id: psr?.id ?? null,
        savedRoleId: saved,
        roleDisplayName,
      })
    }
    assignmentRows.value = rows
  } catch {
    assignmentRows.value = []
    assignmentRoleOptions.value = []
  } finally {
    assignmentsPanelLoading.value = false
  }
}

function openPhaseRoleDialog(row) {
  phaseRoleEditRow.value = row
  phaseRoleEditSelectedId.value = row.savedRoleId ?? null
  phaseRoleDialogVisible.value = true
}

async function savePhaseRoleFromDialog() {
  const r = phaseRoleEditRow.value
  const aidVal = aid.value
  const pid = phaseId.value
  if (!r || !aidVal || !pid) return
  phaseRoleSaveLoading.value = true
  try {
    await saveTeacherPhaseStudentAssignment({
      existingId: r.psr_id,
      phase_id: pid,
      student_id: r.student_id,
      team_id: r.team_id,
      activity_role_id: phaseRoleEditSelectedId.value ?? null,
    })
    toast.success('Rol en fase', 'Asignación guardada.')
    phaseRoleDialogVisible.value = false
    phaseRoleEditRow.value = null
    await load()
    await loadAssignmentEditor()
  } catch {
    toast.error('Error', 'No se pudo guardar el rol en la fase.')
  } finally {
    phaseRoleSaveLoading.value = false
  }
}

function goList() {
  const tid = contextTeamId.value
  if (tid) {
    router.push({
      name: 'app.activity.team.phases',
      params: { activityId: String(aid.value), teamId: String(tid) },
      query: { ...tabQuery.value },
    })
    return
  }
  router.push({
    name: 'app.activity.phases',
    params: { activityId: String(aid.value) },
    query: { ...tabQuery.value },
  })
}

function goEdit() {
  router.push({
    name: 'app.activity.phase.edit',
    params: { activityId: String(aid.value), phaseId: String(phaseId.value) },
    query: { ...tabQuery.value },
  })
}

const swal = inject('$swal', null)

function confirmDelete() {
  const title = row.value.title || `Fase #${phaseId.value}`
  const run = async () => {
    try {
      await deletePhase(aid.value, phaseId.value)
      goList()
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
    text: `Se eliminará «${title}».`,
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#ef4444',
  }).then((result) => {
    if (result.isConfirmed) run()
  })
}

async function load() {
  if (!aid.value || !phaseId.value) return
  loading.value = true
  loadError.value = false
  try {
    const p = await getPhase(aid.value, phaseId.value)
    row.value = p && typeof p === 'object' ? { ...p } : {}
    if (contextTeamId.value && canManagePhaseStudentRoles.value && activityHasRoleTypes.value) {
      await loadAssignmentEditor()
    } else {
      assignmentRows.value = []
      assignmentRoleOptions.value = []
    }
  } catch {
    loadError.value = true
    row.value = {}
  } finally {
    loading.value = false
  }
}

watch(
  () => [aid.value, phaseId.value, contextTeamId.value],
  () => load(),
  { immediate: true }
)

watch(
  () => activityRef?.value?.activity_role_type_id,
  () => {
    if (
      row.value?.id &&
      contextTeamId.value &&
      canManagePhaseStudentRoles.value &&
      activityHasRoleTypes.value
    ) {
      loadAssignmentEditor()
    }
  }
)
</script>
