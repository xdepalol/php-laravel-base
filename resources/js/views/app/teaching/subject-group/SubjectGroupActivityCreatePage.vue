<template>
  <div>
    <Card v-if="!canCreate">
      <template #content>
        <p class="text-sm text-slate-600">No tienes permiso para crear actividades en este contexto.</p>
        <Button
          label="Volver a actividades"
          class="mt-3"
          severity="secondary"
          outlined
          @click="goToActivitiesList"
        />
      </template>
    </Card>

    <Card v-else>
      <template #title>Nueva actividad</template>
      <template #subtitle>
        Los mismos datos que al editar una actividad: se asocia a este grupo y al curso académico del grupo.
      </template>
      <template #content>
        <form class="space-y-4 max-w-3xl" @submit.prevent="onSubmit">
          <div
            v-if="academicYearLabel"
            class="rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2 text-sm text-slate-700"
          >
            <span class="font-medium text-slate-800">Curso académico:</span>
            {{ academicYearLabel }}
          </div>
          <div>
            <label for="create-act-title" class="text-sm font-medium text-slate-700 block mb-1"
              >Título</label
            >
            <InputText
              id="create-act-title"
              v-model="form.title"
              class="w-full"
              :class="{ 'p-invalid': !!fieldError('title') }"
            />
            <small v-if="fieldError('title')" class="text-red-500">{{ fieldError('title') }}</small>
          </div>
          <div>
            <label for="create-act-desc" class="text-sm font-medium text-slate-700 block mb-1"
              >Descripción</label
            >
            <Textarea id="create-act-desc" v-model="form.description" class="w-full" rows="5" />
            <small v-if="fieldError('description')" class="text-red-500">{{
              fieldError('description')
            }}</small>
          </div>
          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="text-sm font-medium text-slate-700 block mb-1">Tipo de actividad</label>
              <Select
                v-model="form.type"
                :options="typeOptions"
                option-label="label"
                option-value="value"
                class="w-full"
              />
              <small v-if="fieldError('type')" class="text-red-500">{{ fieldError('type') }}</small>
            </div>
            <div>
              <label class="text-sm font-medium text-slate-700 block mb-1">Estado</label>
              <Select
                v-model="form.status"
                :options="statusOptions"
                option-label="label"
                option-value="value"
                class="w-full"
              />
              <small v-if="fieldError('status')" class="text-red-500">{{ fieldError('status') }}</small>
            </div>
          </div>
          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label for="create-act-start" class="text-sm font-medium text-slate-700 block mb-1"
                >Fecha de inicio</label
              >
              <InputText
                id="create-act-start"
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
              <label for="create-act-end" class="text-sm font-medium text-slate-700 block mb-1"
                >Fecha de fin</label
              >
              <InputText
                id="create-act-end"
                v-model="form.end_date"
                type="date"
                class="w-full"
                :class="{ 'p-invalid': !!fieldError('end_date') }"
              />
              <small v-if="fieldError('end_date')" class="text-red-500">{{
                fieldError('end_date')
              }}</small>
            </div>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700 block mb-1"
              >Tipo de roles de actividad</label
            >
            <Select
              v-model="form.activity_role_type_id"
              :options="roleTypeOptions"
              option-label="label"
              option-value="value"
              class="w-full"
              :loading="roleTypesLoading"
              placeholder="Selecciona un tipo"
            />
            <p class="mt-1 text-xs text-slate-500">
              Define qué roles pueden elegir los estudiantes; la definición detallada de cada tipo se
              gestiona en el panel de administración.
            </p>
            <small v-if="fieldError('activity_role_type_id')" class="text-red-500">{{
              fieldError('activity_role_type_id')
            }}</small>
          </div>
          <div class="space-y-3 rounded-lg border border-slate-200 p-4">
            <p class="text-sm font-medium text-slate-800">Metodología y alcance</p>
            <div class="flex items-center gap-2">
              <Checkbox v-model="form.has_sprints" input-id="create-act-sprints" binary />
              <label for="create-act-sprints" class="text-sm cursor-pointer"
                >Fases iterativas (sprints)</label
              >
            </div>
            <div class="flex items-center gap-2">
              <Checkbox v-model="form.has_backlog" input-id="create-act-backlog" binary />
              <label for="create-act-backlog" class="text-sm cursor-pointer">Backlog de producto</label>
            </div>
            <div class="flex items-center gap-2">
              <Checkbox v-model="form.is_intermodular" input-id="create-act-inter" binary />
              <label for="create-act-inter" class="text-sm cursor-pointer">Actividad intermodular</label>
            </div>
          </div>
          <div class="flex flex-wrap gap-2 pt-2">
            <Button
              type="submit"
              label="Crear actividad"
              icon="pi pi-check"
              :loading="saving"
              :disabled="!groupReady"
            />
            <Button
              type="button"
              label="Cancelar"
              severity="secondary"
              outlined
              @click="goToActivitiesList"
            />
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { computed, inject, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAbility } from '@casl/vue'
import useActivities from '@/composables/activities'
import useActivityRoleTypes from '@/composables/activityRoleTypes'

const { can } = useAbility()
const canCreate = computed(() => can('activity-create'))

const router = useRouter()
const subjectGroupRef = inject('subjectGroup')
const groupIdRef = inject('subjectGroupId')

const groupId = computed(() => Number(groupIdRef?.value) || 0)

const subjectGroup = computed(() => subjectGroupRef?.value ?? {})

const academicYearLabel = computed(() => {
  const y = subjectGroup.value?.academic_year
  if (typeof y === 'object' && y !== null) return y.name || y.label || y.title || null
  const id = subjectGroup.value?.academic_year_id
  return id != null ? `Curso #${id}` : null
})

const groupReady = computed(
  () => groupId.value > 0 && subjectGroup.value?.academic_year_id != null
)

const { createActivity, clearErrors, getError } = useActivities()
const { activityRoleTypes, getActivityRoleTypes, isLoading: roleTypesLoading } =
  useActivityRoleTypes()

const ACTIVITY_TYPE_LABELS = {
  0: 'General',
  1: 'Proyecto',
  2: 'Práctica de laboratorio',
  3: 'Examen',
  4: 'Documento técnico',
}

const ACTIVITY_STATUS_LABELS = {
  0: 'Borrador',
  1: 'Publicada',
  2: 'Cerrada',
}

const typeOptions = Object.entries(ACTIVITY_TYPE_LABELS).map(([value, label]) => ({
  label,
  value: Number(value),
}))

const statusOptions = Object.entries(ACTIVITY_STATUS_LABELS).map(([value, label]) => ({
  label,
  value: Number(value),
}))

const form = reactive({
  title: '',
  description: '',
  type: 0,
  status: 0,
  activity_role_type_id: null,
  has_sprints: false,
  has_backlog: false,
  is_intermodular: false,
  start_date: null,
  end_date: null,
})

const roleTypeOptions = computed(() => {
  const base = { label: 'Sin tipo asignado', value: null }
  const rest = activityRoleTypes.value.map((t) => ({
    label: t.name,
    value: t.id,
  }))
  return [base, ...rest]
})

function fieldError(field) {
  return getError(field) ?? ''
}

const saving = ref(false)

function goToActivitiesList() {
  router.push({
    name: 'app.subject-group.activities',
    params: { id: String(groupId.value) },
  })
}

async function onSubmit() {
  if (!canCreate.value || !groupReady.value) return
  clearErrors()
  saving.value = true
  try {
    const payload = {
      academic_year_id: subjectGroup.value.academic_year_id,
      title: form.title,
      description: form.description,
      type: form.type,
      status: form.status,
      activity_role_type_id: form.activity_role_type_id,
      has_sprints: form.has_sprints,
      has_backlog: form.has_backlog,
      is_intermodular: form.is_intermodular,
      start_date: form.start_date || null,
      end_date: form.end_date || null,
      subject_groups: [groupId.value],
    }
    const created = await createActivity(payload)
    const newId = created?.id
    if (newId) {
      router.push({
        name: 'app.activity.overview',
        params: { activityId: newId },
        query: { fromSubjectGroup: String(groupId.value) },
      })
    }
  } catch {
    /* validación / toast en composable */
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  getActivityRoleTypes().catch(() => {})
})
</script>
