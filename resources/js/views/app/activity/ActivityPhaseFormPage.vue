<template>
  <Card>
    <template #title>{{ isEdit ? 'Editar fase' : 'Nueva fase' }}</template>
    <template #subtitle>
      Define el título, si es sprint, y el rango de fechas. La retrospectiva y el feedback se pueden
      completar desde la vista de la fase.
    </template>
    <template #content>
      <p v-if="!canSubmit" class="text-sm text-amber-800 mb-4">
        No tienes permiso para {{ isEdit ? 'editar' : 'crear' }} fases.
      </p>
      <div v-else-if="loadError" class="text-sm text-red-600">No se pudo cargar la fase.</div>
      <form v-else class="space-y-4 max-w-xl" @submit.prevent="onSubmit">
        <div>
          <label for="ph-title" class="text-sm font-medium text-slate-700 block mb-1">Título</label>
          <InputText
            id="ph-title"
            v-model="form.title"
            class="w-full"
            :class="{ 'p-invalid': !!fieldError('title') }"
          />
          <small v-if="fieldError('title')" class="text-red-500">{{ fieldError('title') }}</small>
        </div>
        <div class="flex items-center gap-2">
          <Checkbox v-model="form.is_sprint" input-id="ph-sprint" binary />
          <label for="ph-sprint" class="text-sm cursor-pointer">Es un sprint (iteración)</label>
        </div>
        <div class="flex items-center gap-2">
          <Checkbox v-model="form.teams_may_assign_phase_roles" input-id="ph-team-roles" binary />
          <label for="ph-team-roles" class="text-sm cursor-pointer">
            Los estudiantes del equipo pueden elegir su rol en esta fase
          </label>
        </div>
        <p class="text-xs text-slate-500 -mt-2">
          Solo aplica si la actividad tiene un tipo de roles definido. El reparto sigue siendo
          editable por el profesorado.
        </p>
        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label for="ph-start" class="text-sm font-medium text-slate-700 block mb-1"
              >Fecha de inicio</label
            >
            <InputText
              id="ph-start"
              v-model="form.start_date"
              type="date"
              class="w-full"
              :class="{ 'p-invalid': !!fieldError('start_date') }"
            />
            <small v-if="fieldError('start_date')" class="text-red-500">{{
              fieldError('start_date')
            }}</small>
          </div>
          <div>
            <label for="ph-end" class="text-sm font-medium text-slate-700 block mb-1"
              >Fecha de fin</label
            >
            <InputText
              id="ph-end"
              v-model="form.end_date"
              type="date"
              class="w-full"
              :class="{ 'p-invalid': !!fieldError('end_date') }"
            />
            <small v-if="fieldError('end_date')" class="text-red-500">{{ fieldError('end_date') }}</small>
          </div>
        </div>
        <div class="flex flex-wrap gap-2 pt-2">
          <Button
            type="submit"
            :label="isEdit ? 'Guardar cambios' : 'Crear fase'"
            icon="pi pi-check"
            :loading="saving"
            :disabled="!aid"
          />
          <Button type="button" label="Cancelar" severity="secondary" outlined @click="goBack" />
        </div>
      </form>
    </template>
  </Card>
</template>

<script setup>
import { computed, inject, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAbility } from '@casl/vue'
import useActivityPhases from '@/composables/activityPhases'
import { toDateInputValue } from '@/utils/dateInput'

const route = useRoute()
const router = useRouter()
const { can } = useAbility()
const activityId = inject('activityId')

const isEdit = computed(() => route.name === 'app.activity.phase.edit')
const phaseId = computed(() => (route.params.phaseId ? Number(route.params.phaseId) : null))
const aid = computed(() => activityId?.value)

const canSubmit = computed(() => (isEdit.value ? can('phase-edit') : can('phase-create')))

const { createPhase, updatePhase, getPhase, getError, clearErrors } = useActivityPhases()

const form = reactive({
  title: '',
  is_sprint: false,
  teams_may_assign_phase_roles: false,
  start_date: null,
  end_date: null,
})

const saving = ref(false)
const loadError = ref(false)
/** Valores cargados del API para no borrar retrospectiva / feedback al guardar. */
const loadedExtras = ref({
  retro_well: null,
  retro_bad: null,
  retro_improvement: null,
  teacher_feedback: null,
})

const tabQuery = computed(() => {
  const raw = route.query.fromSubjectGroup
  if (raw == null || raw === '') return {}
  return { fromSubjectGroup: String(raw) }
})

function fieldError(f) {
  return getError(f) ?? ''
}

function goBack() {
  router.push({
    name: 'app.activity.phases',
    params: { activityId: String(aid.value) },
    query: { ...tabQuery.value },
  })
}

async function loadPhase() {
  if (!isEdit.value || !phaseId.value || !aid.value) return
  loadError.value = false
  clearErrors()
  try {
    const p = await getPhase(aid.value, phaseId.value)
    form.title = p.title ?? ''
    form.is_sprint = !!p.is_sprint
    form.start_date = toDateInputValue(p.start_date)
    form.end_date = toDateInputValue(p.end_date)
    form.teams_may_assign_phase_roles = !!p.teams_may_assign_phase_roles
    loadedExtras.value = {
      retro_well: p.retro_well ?? null,
      retro_bad: p.retro_bad ?? null,
      retro_improvement: p.retro_improvement ?? null,
      teacher_feedback: p.teacher_feedback ?? null,
    }
  } catch {
    loadError.value = true
  }
}

onMounted(() => {
  if (isEdit.value) loadPhase()
})

async function onSubmit() {
  if (!canSubmit.value || !aid.value) return
  clearErrors()
  saving.value = true
  try {
    const base = {
      title: form.title,
      is_sprint: form.is_sprint,
      start_date: form.start_date || null,
      end_date: form.end_date || null,
      teams_may_assign_phase_roles: form.teams_may_assign_phase_roles,
    }
    if (isEdit.value && phaseId.value) {
      await updatePhase(aid.value, phaseId.value, {
        ...loadedExtras.value,
        ...base,
      })
    } else {
      await createPhase(aid.value, {
        ...base,
        retro_well: null,
        retro_bad: null,
        retro_improvement: null,
        teacher_feedback: null,
      })
    }
    goBack()
  } catch {
    /* validación / toast en composable */
  } finally {
    saving.value = false
  }
}
</script>
