<template>
  <div class="deliverable-form-page space-y-6">
    <div class="flex flex-wrap items-center gap-3">
      <router-link
        :to="backTo"
        class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-slate-900"
      >
        <i class="pi pi-arrow-left" />
        Volver a entregables
      </router-link>
    </div>

    <Card>
      <template #title>{{ pageHeading }}</template>
      <template #content>
        <div v-if="pageBusy" class="flex justify-center py-12 text-blue-600">
          <i class="pi pi-spin pi-spinner text-2xl" aria-hidden="true" />
        </div>
        <form v-else class="space-y-4 max-w-2xl" @submit.prevent="submit">
          <div>
            <label for="deliverable-title" class="text-sm font-medium text-slate-700 block mb-1"
              >Título</label
            >
            <InputText
              id="deliverable-title"
              v-model="deliverable.title"
              class="w-full"
              :class="{ 'p-invalid': hasError('title') }"
            />
            <small v-if="hasError('title')" class="text-red-500">{{ getError('title') }}</small>
          </div>
          <div>
            <label for="deliverable-short-code" class="text-sm font-medium text-slate-700 block mb-1"
              >Código corto</label
            >
            <InputText
              id="deliverable-short-code"
              v-model="deliverable.short_code"
              class="w-full max-w-xs"
              maxlength="32"
              :class="{ 'p-invalid': hasError('short_code') }"
            />
            <p class="mt-1 text-xs text-slate-500">
              Único dentro de esta actividad (p. ej. REQ, FIG). Letras, números, guiones; debe empezar por letra o número.
            </p>
            <small v-if="hasError('short_code')" class="text-red-500">{{
              getError('short_code')
            }}</small>
          </div>
          <div>
            <label for="deliverable-desc" class="text-sm font-medium text-slate-700 block mb-1"
              >Descripción</label
            >
            <Textarea
              id="deliverable-desc"
              v-model="deliverable.description"
              class="w-full"
              rows="4"
            />
            <small v-if="hasError('description')" class="text-red-500">{{
              getError('description')
            }}</small>
          </div>
          <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
              <label for="deliverable-due-input" class="text-sm font-medium text-slate-700 block mb-1"
                >Fecha y hora de entrega</label
              >
              <DatePicker
                v-model="duePicker"
                show-time
                hour-format="24"
                date-format="dd/mm/yy"
                show-icon
                show-button-bar
                show-clear
                input-id="deliverable-due-input"
                class="w-full"
                :invalid="hasError('due_date')"
              />
              <p class="mt-1 text-xs text-slate-500">
                Se guarda en UTC; se muestra en la zona horaria del navegador.
              </p>
              <small v-if="hasError('due_date')" class="text-red-500">{{ getError('due_date') }}</small>
            </div>
            <div>
              <label class="text-sm font-medium text-slate-700 block mb-1">Estado</label>
              <Select
                v-model="deliverable.status"
                :options="statusOptions"
                option-label="label"
                option-value="value"
                class="w-full"
              />
              <small v-if="hasError('status')" class="text-red-500">{{ getError('status') }}</small>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <Checkbox
              v-model="deliverable.is_group_deliverable"
              input-id="deliverable-group"
              binary
            />
            <label for="deliverable-group" class="text-sm cursor-pointer"
              >Entregable de equipo (no individual)</label
            >
          </div>
          <div class="flex gap-2 pt-2">
            <Button type="submit" label="Guardar" icon="pi pi-check" :loading="isLoading" />
            <Button
              type="button"
              label="Cancelar"
              severity="secondary"
              outlined
              @click="goBack"
            />
          </div>
        </form>
      </template>
    </Card>

    <Card
      v-if="isEdit && can('submission-list')"
      class="max-w-4xl"
    >
      <template #title>Entregas registradas (este entregable)</template>
      <template #subtitle>
        Listado de entregas asociadas solo a este entregable.
      </template>
      <template #content>
        <div v-if="subsLoading" class="flex justify-center py-8 text-blue-600">
          <i class="pi pi-spin pi-spinner text-xl" aria-hidden="true" />
        </div>
        <DataTable
          v-else
          :value="submissionsList"
          data-key="id"
          size="small"
          striped-rows
          class="text-sm"
        >
          <template #empty>
            <span class="text-slate-500">Aún no hay entregas.</span>
          </template>
          <Column header="Participante">
            <template #body="{ data }">
              {{ participantLabel(data) }}
            </template>
          </Column>
          <Column header="Estado" class="w-32">
            <template #body="{ data }">
              <Tag :value="submissionStatusLabel(data)" severity="secondary" />
            </template>
          </Column>
          <Column header="Entregada" class="w-40">
            <template #body="{ data }">
              <UtcFormatted :value="data.submitted_at" variant="datetime" />
            </template>
          </Column>
          <Column class="w-28">
            <template #body="{ data }">
              <router-link
                v-if="can('submission-view')"
                :to="submissionDetailTo(data.id)"
                class="text-blue-700 hover:underline text-sm font-medium"
              >
                Ver detalle
              </router-link>
              <span v-else class="text-slate-400">—</span>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAbility } from '@casl/vue'
import axios from 'axios'
import useActivityDeliverables from '@/composables/activityDeliverables'
import { jsDateToUtcIso, utcIsoToJsDate } from '@/utils/datetime'

const { can } = useAbility()

const route = useRoute()
const router = useRouter()

const {
  deliverable,
  isLoading,
  hasError,
  getError,
  resetDeliverable,
  getDeliverable,
  createDeliverable,
  updateDeliverable,
} = useActivityDeliverables()

/** DatePicker trabaja con Date local; el composable guarda ISO UTC en `due_date`. */
const duePicker = computed({
  get() {
    return utcIsoToJsDate(deliverable.value?.due_date)
  },
  set(v) {
    deliverable.value.due_date = jsDateToUtcIso(v)
  },
})

const activityId = computed(() => Number(route.params.activityId))
const deliverableId = computed(() =>
  route.params.deliverableId != null && route.params.deliverableId !== ''
    ? Number(route.params.deliverableId)
    : null
)

const isEdit = computed(() => deliverableId.value != null && Number.isFinite(deliverableId.value))

const subsLoading = ref(false)
const submissionsList = ref([])

function unwrap(response) {
  return response.data?.data ?? response.data
}

const SUBMISSION_STATUS = {
  0: 'Pendiente',
  1: 'Entregada',
  2: 'Calificada',
}

function submissionStatusLabel(row) {
  const s = row?.status
  const v = typeof s === 'object' && s !== null ? s.value : s
  if (v == null) return '—'
  return SUBMISSION_STATUS[v] ?? String(v)
}

function participantLabel(s) {
  if (s.team_id != null) {
    const n = s.team?.name
    return n ? `Equipo: ${n}` : `Equipo #${s.team_id}`
  }
  if (s.student_id != null) {
    const u = s.student?.user
    if (u) {
      const parts = [u.name, u.surname1, u.surname2].filter(Boolean)
      return parts.length ? parts.join(' ') : `Estudiante #${s.student_id}`
    }
    return `Estudiante #${s.student_id}`
  }
  return '—'
}

function submissionDetailTo(submissionId) {
  return {
    name: 'app.activity.submission.detail',
    params: {
      activityId: activityId.value,
      deliverableId: deliverableId.value,
      submissionId,
    },
    query: { ...tabQuery.value },
  }
}

async function loadSubmissionsForDeliverable() {
  const did = deliverableId.value
  if (!did || !isEdit.value) {
    submissionsList.value = []
    return
  }
  subsLoading.value = true
  try {
    const res = await axios.get(`/api/deliverables/${did}/submissions`)
    const data = unwrap(res)
    submissionsList.value = Array.isArray(data) ? data : []
  } catch {
    submissionsList.value = []
  } finally {
    subsLoading.value = false
  }
}

const pageHeading = computed(() => (isEdit.value ? 'Editar entregable' : 'Nuevo entregable'))

const statusOptions = [
  { label: 'Borrador', value: 0 },
  { label: 'Publicado', value: 1 },
  { label: 'Cerrado', value: 2 },
]

/** Solo carga inicial en modo edición; el composable también marca isLoading al guardar. */
const pageBusy = ref(false)

const tabQuery = computed(() => {
  const raw = route.query.fromSubjectGroup
  if (raw == null || raw === '') return {}
  return { fromSubjectGroup: String(raw) }
})

const backTo = computed(() => ({
  name: 'app.activity.deliverables',
  params: { activityId: activityId.value },
  query: { ...tabQuery.value },
}))

function goBack() {
  router.push(backTo.value)
}

async function load() {
  const aid = activityId.value
  if (!aid) return
  if (isEdit.value) {
    pageBusy.value = true
    try {
      await getDeliverable(aid, deliverableId.value)
    } finally {
      pageBusy.value = false
    }
  } else {
    resetDeliverable()
  }
}

watch(
  () => [activityId.value, deliverableId.value, route.name],
  () => {
    load()
  },
  { immediate: true }
)

watch(
  () => [isEdit.value, deliverableId.value],
  () => {
    loadSubmissionsForDeliverable()
  },
  { immediate: true }
)

async function submit() {
  const aid = activityId.value
  if (!aid) return
  if (isEdit.value) {
    await updateDeliverable(aid, deliverableId.value, deliverable.value)
  } else {
    await createDeliverable(aid, deliverable.value)
  }
  goBack()
}
</script>
