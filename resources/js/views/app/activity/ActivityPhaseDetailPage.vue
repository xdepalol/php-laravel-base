<template>
  <Card>
    <template #title>
      <div class="flex flex-wrap items-center justify-between gap-3 w-full">
        <span>{{ row.title || 'Fase' }}</span>
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

        <div v-if="row.retro_well || row.retro_bad || row.retro_improvement" class="space-y-3">
          <h3 class="text-sm font-semibold text-slate-800">Retrospectiva</h3>
          <div v-if="row.retro_well" class="rounded-lg border border-slate-200 p-3">
            <p class="text-xs font-medium text-slate-500 mb-1">Qué fue bien</p>
            <p class="text-slate-700 whitespace-pre-wrap">{{ row.retro_well }}</p>
          </div>
          <div v-if="row.retro_bad" class="rounded-lg border border-slate-200 p-3">
            <p class="text-xs font-medium text-slate-500 mb-1">Qué mejorar</p>
            <p class="text-slate-700 whitespace-pre-wrap">{{ row.retro_bad }}</p>
          </div>
          <div v-if="row.retro_improvement" class="rounded-lg border border-slate-200 p-3">
            <p class="text-xs font-medium text-slate-500 mb-1">Acciones</p>
            <p class="text-slate-700 whitespace-pre-wrap">{{ row.retro_improvement }}</p>
          </div>
        </div>

        <div v-if="row.teacher_feedback" class="rounded-lg border border-amber-200 bg-amber-50/80 p-3">
          <p class="text-xs font-medium text-amber-900 mb-1">Feedback del profesorado</p>
          <p class="text-slate-800 whitespace-pre-wrap">{{ row.teacher_feedback }}</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 pt-2 border-t border-slate-100">
          <div>
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
          <div>
            <h3 class="text-sm font-semibold text-slate-800 mb-2">
              Roles por estudiante ({{ row.phase_student_roles?.length ?? 0 }})
            </h3>
            <p v-if="!row.phase_student_roles?.length" class="text-slate-500">Ninguno.</p>
            <ul v-else class="list-disc pl-5 text-slate-600 space-y-1">
              <li v-for="psr in row.phase_student_roles" :key="psr.id">
                {{ studentLabel(psr) }}
                <template v-if="psr.activity_role?.name"> — {{ psr.activity_role.name }}</template>
              </li>
            </ul>
          </div>
        </div>

        <Button label="Volver a la lista" icon="pi pi-arrow-left" severity="secondary" text @click="goList" />
      </div>
    </template>
  </Card>
</template>

<script setup>
import { computed, inject, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAbility } from '@casl/vue'
import useActivityPhases from '@/composables/activityPhases'
import { formatStudentDisplayName } from '@/utils/studentDisplayName'

const route = useRoute()
const router = useRouter()
const { can } = useAbility()
const activityRef = inject('activity')
const activityId = inject('activityId')

const canEdit = computed(() => can('phase-edit'))
const canDelete = computed(() => can('phase-delete'))

const { getPhase, deletePhase } = useActivityPhases()

const loading = ref(true)
const loadError = ref(false)
const row = ref({})

const phaseId = computed(() => Number(route.params.phaseId) || 0)
const aid = computed(() => activityId?.value)

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

function goList() {
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
  } catch {
    loadError.value = true
    row.value = {}
  } finally {
    loading.value = false
  }
}

watch(
  () => [aid.value, phaseId.value],
  () => load(),
  { immediate: true }
)
</script>
