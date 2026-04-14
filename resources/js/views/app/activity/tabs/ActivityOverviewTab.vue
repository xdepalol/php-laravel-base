<template>
  <div>
    <Card v-if="!showEditForm">
      <template #title>
        <div class="flex flex-wrap items-center justify-between gap-3 w-full">
          <span>Resumen</span>
          <Button
            v-if="canEdit"
            label="Editar"
            icon="pi pi-pencil"
            size="small"
            outlined
            @click="startEdit"
          />
        </div>
      </template>
      <template #content>
        <div v-if="!activity?.id" class="text-slate-500 text-sm">Cargando…</div>
        <dl v-else class="grid gap-4 sm:grid-cols-2 text-sm">
          <div>
            <dt class="text-slate-500 font-medium">Descripción</dt>
            <dd class="mt-1 text-slate-800 whitespace-pre-wrap">{{ activity.description || '—' }}</dd>
          </div>
          <div>
            <dt class="text-slate-500 font-medium">Tipo</dt>
            <dd class="mt-1 text-slate-800">{{ typeLabel }}</dd>
          </div>
          <div>
            <dt class="text-slate-500 font-medium">Estado</dt>
            <dd class="mt-1 text-slate-800">{{ statusLabel }}</dd>
          </div>
          <div>
            <dt class="text-slate-500 font-medium">Fechas</dt>
            <dd class="mt-1 text-slate-800 flex flex-wrap items-center gap-1">
              <UtcFormatted :value="activity.start_date" variant="date" />
              <span aria-hidden="true">→</span>
              <UtcFormatted :value="activity.end_date" variant="date" />
            </dd>
          </div>
          <div class="sm:col-span-2">
            <dt class="text-slate-500 font-medium">Tipo de roles</dt>
            <dd class="mt-1 text-slate-800">{{ activityRoleTypeLabel }}</dd>
          </div>
          <div class="sm:col-span-2">
            <dt class="text-slate-500 font-medium">Metodología y alcance</dt>
            <dd class="mt-1 flex flex-wrap gap-2">
              <Tag
                v-if="activity.has_sprints"
                value="Fases iterativas (sprints)"
                severity="info"
              />
              <Tag
                v-if="activity.has_backlog"
                value="Backlog de producto"
                severity="secondary"
              />
              <Tag v-if="activity.is_intermodular" value="Intermodular" severity="warn" />
              <span
                v-if="!activity.has_sprints && !activity.has_backlog && !activity.is_intermodular"
                class="text-slate-600"
                >—</span
              >
            </dd>
            <p
              v-if="methodologyNote"
              class="mt-3 text-xs text-slate-500 leading-relaxed max-w-3xl"
            >
              {{ methodologyNote }}
            </p>
          </div>
          <div v-if="subjectGroupLabels.length" class="sm:col-span-2">
            <dt class="text-slate-500 font-medium">Grupos de asignatura</dt>
            <dd class="mt-1 flex flex-wrap gap-2">
              <Tag v-for="(g, i) in subjectGroupLabels" :key="i" :value="g" severity="contrast" />
            </dd>
          </div>
        </dl>
      </template>
    </Card>

    <Card v-else>
      <template #title>Editar actividad</template>
      <template #subtitle>
        Ajusta datos generales, fechas, estado y el tipo de roles disponibles para los estudiantes.
      </template>
      <template #content>
        <form class="space-y-4 max-w-3xl" @submit.prevent="onSave">
          <div>
            <label for="act-title" class="text-sm font-medium text-slate-700 block mb-1">Título</label>
            <InputText
              id="act-title"
              v-model="form.title"
              class="w-full"
              :class="{ 'p-invalid': !!fieldError('title') }"
            />
            <small v-if="fieldError('title')" class="text-red-500">{{ fieldError('title') }}</small>
          </div>
          <div>
            <label for="act-desc" class="text-sm font-medium text-slate-700 block mb-1"
              >Descripción</label
            >
            <Textarea id="act-desc" v-model="form.description" class="w-full" rows="5" />
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
              <label for="act-start" class="text-sm font-medium text-slate-700 block mb-1"
                >Fecha de inicio</label
              >
              <InputText
                id="act-start"
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
              <label for="act-end" class="text-sm font-medium text-slate-700 block mb-1"
                >Fecha de fin</label
              >
              <InputText
                id="act-end"
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
              <Checkbox v-model="form.has_sprints" input-id="act-sprints" binary />
              <label for="act-sprints" class="text-sm cursor-pointer"
                >Fases iterativas (sprints)</label
              >
            </div>
            <div class="flex items-center gap-2">
              <Checkbox v-model="form.has_backlog" input-id="act-backlog" binary />
              <label for="act-backlog" class="text-sm cursor-pointer">Backlog de producto</label>
            </div>
            <div class="flex items-center gap-2">
              <Checkbox v-model="form.is_intermodular" input-id="act-inter" binary />
              <label for="act-inter" class="text-sm cursor-pointer">Actividad intermodular</label>
            </div>
          </div>
          <div class="flex flex-wrap gap-2 pt-2">
            <Button type="submit" label="Guardar cambios" icon="pi pi-check" :loading="saving" />
            <Button
              type="button"
              label="Cancelar"
              severity="secondary"
              outlined
              @click="cancelEdit"
            />
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { computed, inject, onMounted, reactive, ref, watch } from 'vue'
import { useAbility } from '@casl/vue'
import useActivityRoleTypes from '@/composables/activityRoleTypes'
import { toDateInputValue } from '@/utils/dateInput'

const { can } = useAbility()
const canEdit = computed(() => can('activity-edit'))
const editing = ref(false)
const showEditForm = computed(() => canEdit.value && editing.value)

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

const activityRef = inject('activity')
const saveActivity = inject('saveActivity')
const activityEditErrors = inject('activityEditErrors', null)

const { activityRoleTypes, getActivityRoleTypes, isLoading: roleTypesLoading } =
  useActivityRoleTypes()

const activity = computed(() => activityRef?.value ?? {})

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

function syncFormFromActivity() {
  const a = activityRef?.value
  if (!a?.id) return
  form.title = a.title ?? ''
  form.description = a.description ?? ''
  form.type = a.type ?? 0
  form.status = a.status ?? 0
  form.activity_role_type_id = a.activity_role_type_id ?? null
  form.has_sprints = !!a.has_sprints
  form.has_backlog = !!a.has_backlog
  form.is_intermodular = !!a.is_intermodular
  form.start_date = toDateInputValue(a.start_date)
  form.end_date = toDateInputValue(a.end_date)
}

watch(activityRef, syncFormFromActivity, { deep: true, immediate: true })

function startEdit() {
  syncFormFromActivity()
  getActivityRoleTypes().catch(() => {})
  editing.value = true
}

function cancelEdit() {
  editing.value = false
  syncFormFromActivity()
}

onMounted(() => {
  getActivityRoleTypes().catch(() => {})
})

const roleTypeOptions = computed(() => {
  const base = { label: 'Sin tipo asignado', value: null }
  const rest = activityRoleTypes.value.map((t) => ({
    label: t.name,
    value: t.id,
  }))
  return [base, ...rest]
})

const saving = ref(false)

function fieldError(field) {
  return activityEditErrors?.getError?.(field) ?? ''
}

async function onSave() {
  saving.value = true
  try {
    await saveActivity({
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
    })
    editing.value = false
  } catch {
    /* toast / validation en composable */
  } finally {
    saving.value = false
  }
}

const typeLabel = computed(() => {
  const t = activity.value?.type
  if (t == null) return '—'
  if (typeof t === 'object') return t.label || t.name || String(t.value)
  return ACTIVITY_TYPE_LABELS[t] ?? String(t)
})

const statusLabel = computed(() => {
  const s = activity.value?.status
  if (s == null) return '—'
  if (typeof s === 'object') return s.name || String(s.value)
  return ACTIVITY_STATUS_LABELS[s] ?? String(s)
})

const activityRoleTypeLabel = computed(() => {
  const id = activity.value?.activity_role_type_id
  if (id == null) return '—'
  const found = activityRoleTypes.value.find((t) => t.id === id)
  if (found) return found.name
  return `Tipo #${id}`
})

const subjectGroupLabels = computed(() => {
  const sg = activity.value?.subject_groups
  if (!Array.isArray(sg)) return []
  return sg.map((x) => {
    if (typeof x === 'object' && x !== null) return x.name || x.title || `Grupo #${x.id}`
    return `Grupo #${x}`
  })
})

const methodologyNote = computed(() => {
  const a = activity.value
  if (!a?.id) return ''
  const parts = []
  if (a.has_sprints) {
    parts.push('Las fases (incluidos sprints tipo Scrum) se gestionan en el espacio de cada equipo.')
  }
  if (a.has_backlog) {
    parts.push(
      'El backlog compartido (profesorado) y el del equipo se consultan en cada equipo cuando el backlog está activo en la actividad.'
    )
  }
  return parts.join(' ')
})
</script>
