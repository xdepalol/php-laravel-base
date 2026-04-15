<template>
  <div class="submission-detail-page space-y-6">
    <div class="flex flex-wrap items-center gap-3">
      <router-link
        :to="backTo"
        class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-slate-900"
      >
        <i class="pi pi-arrow-left" />
        Volver al listado de entregas
      </router-link>
    </div>

    <Card>
      <template #title>Detalle de la entrega</template>
      <template #content>
        <div v-if="pageBusy" class="flex justify-center py-12 text-blue-600">
          <i class="pi pi-spin pi-spinner text-2xl" aria-hidden="true" />
        </div>
        <div v-else-if="!submission?.id" class="text-slate-600 text-sm">
          No se pudo cargar la entrega.
        </div>
        <dl v-else class="grid gap-4 sm:grid-cols-2 text-sm max-w-3xl">
          <div class="sm:col-span-2">
            <dt class="text-slate-500 font-medium">Entregable</dt>
            <dd class="mt-1 text-slate-900">{{ deliverableTitle }}</dd>
          </div>
          <div v-if="submission.team_id">
            <dt class="text-slate-500 font-medium">Equipo</dt>
            <dd class="mt-1 text-slate-800">{{ teamLabel }}</dd>
          </div>
          <div v-if="submission.student_id">
            <dt class="text-slate-500 font-medium">Estudiante</dt>
            <dd class="mt-1 text-slate-800">{{ studentLabel }}</dd>
          </div>
          <div>
            <dt class="text-slate-500 font-medium">Estado</dt>
            <dd class="mt-1">
              <Tag :value="statusLabel" severity="secondary" />
            </dd>
          </div>
          <div>
            <dt class="text-slate-500 font-medium">Entregada el</dt>
            <dd class="mt-1 text-slate-800">
              <UtcFormatted :value="submission.submitted_at" variant="datetime" />
            </dd>
          </div>
          <div v-if="submission.grade != null && submission.grade !== ''">
            <dt class="text-slate-500 font-medium">Calificación</dt>
            <dd class="mt-1 text-slate-800">{{ submission.grade }}</dd>
          </div>
          <div v-if="submission.content_url" class="sm:col-span-2">
            <dt class="text-slate-500 font-medium">Enlace</dt>
            <dd class="mt-1">
              <a
                :href="submission.content_url"
                target="_blank"
                rel="noopener noreferrer"
                class="text-blue-700 hover:underline break-all"
                >{{ submission.content_url }}</a
              >
            </dd>
          </div>
          <div v-if="submission.content_text" class="sm:col-span-2">
            <dt class="text-slate-500 font-medium">Texto</dt>
            <dd class="mt-1 text-slate-800 whitespace-pre-wrap">{{ submission.content_text }}</dd>
          </div>
          <div v-if="submission.feedback" class="sm:col-span-2">
            <dt class="text-slate-500 font-medium">Retroalimentación</dt>
            <dd class="mt-1 text-slate-800 whitespace-pre-wrap">{{ submission.feedback }}</dd>
          </div>
        </dl>
      </template>
    </Card>

    <Card v-if="can('submission-grading') && submission?.id && !pageBusy" class="max-w-3xl">
      <template #title>Calificar entrega</template>
      <template #content>
        <div class="flex flex-col gap-4 max-w-lg">
          <div class="flex flex-col gap-1.5">
            <label for="grading-grade" class="text-sm font-medium text-slate-700">Nota (opcional)</label>
            <InputNumber
              id="grading-grade"
              v-model="gradingGrade"
              :min="0"
              :max="100"
              :min-fraction-digits="0"
              :max-fraction-digits="2"
              class="w-full"
              input-class="w-full"
              placeholder="—"
              :disabled="gradingSaving"
            />
            <span class="text-xs text-slate-500">Escala 0–100; admite decimales.</span>
          </div>
          <div class="flex flex-col gap-1.5">
            <label for="grading-status" class="text-sm font-medium text-slate-700">Estado</label>
            <Select
              id="grading-status"
              v-model="gradingStatus"
              :options="gradingStatusOptions"
              option-label="label"
              option-value="value"
              class="w-full"
              :disabled="gradingSaving"
            />
          </div>
          <div class="flex flex-col gap-1.5">
            <label for="grading-feedback" class="text-sm font-medium text-slate-700">Retroalimentación (opcional)</label>
            <Textarea
              id="grading-feedback"
              v-model="gradingFeedback"
              rows="5"
              auto-resize
              class="w-full"
              :disabled="gradingSaving"
            />
          </div>
          <Button
            label="Guardar calificación"
            icon="pi pi-save"
            :loading="gradingSaving"
            :disabled="gradingSaving"
            @click="saveGrading"
          />
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAbility } from '@casl/vue'
import useActivityDeliverables from '@/composables/activityDeliverables'
import useDeliverableSubmissions from '@/composables/deliverableSubmissions'

const route = useRoute()
const router = useRouter()
const { can } = useAbility()
const { fetchDeliverableById } = useActivityDeliverables()
const { submission, getSubmission, gradeSubmission } = useDeliverableSubmissions()

const activityId = computed(() => Number(route.params.activityId))
const deliverableId = computed(() => Number(route.params.deliverableId))
const submissionId = computed(() => Number(route.params.submissionId))

const pageBusy = ref(false)
const deliverableTitle = ref('—')

const gradingGrade = ref(null)
const gradingFeedback = ref('')
const gradingStatus = ref(1)
const gradingSaving = ref(false)

const gradingStatusOptions = [
  { value: 1, label: 'Entregada' },
  { value: 2, label: 'Calificada' },
]

const tabQuery = computed(() => {
  const raw = route.query.fromSubjectGroup
  if (raw == null || raw === '') return {}
  return { fromSubjectGroup: String(raw) }
})

const backTo = computed(() => ({
  name: 'app.activity.deliverable.submissions',
  params: {
    activityId: activityId.value,
    deliverableId: deliverableId.value,
  },
  query: { ...tabQuery.value },
}))

const SUBMISSION_STATUS = {
  0: 'Pendiente',
  1: 'Entregada',
  2: 'Calificada',
}

const statusLabel = computed(() => {
  const s = submission.value?.status
  if (s == null) return '—'
  if (typeof s === 'object' && s !== null) {
    return s.name ?? SUBMISSION_STATUS[s.value] ?? '—'
  }
  return SUBMISSION_STATUS[s] ?? String(s)
})

const studentLabel = computed(() => {
  const sub = submission.value
  const u = sub?.student?.user
  if (!u) return sub?.student_id ? `ID ${sub.student_id}` : '—'
  const parts = [u.name, u.surname1, u.surname2].filter(Boolean)
  return parts.length ? parts.join(' ') : `Usuario #${u.id}`
})

const teamLabel = computed(() => {
  const t = submission.value?.team
  if (!t) return submission.value?.team_id ? `Equipo #${submission.value.team_id}` : '—'
  return t.name || `Equipo #${t.id}`
})

function syncGradingFormFromSubmission() {
  const s = submission.value
  if (!s?.id) return
  const g = s.grade
  gradingGrade.value = g != null && g !== '' ? Number(g) : null
  gradingFeedback.value = s.feedback ? String(s.feedback) : ''
  const st = s.status?.value ?? s.status
  gradingStatus.value = st === 2 ? 2 : 1
}

async function loadDeliverableTitle() {
  const aid = activityId.value
  const did = deliverableId.value
  if (!aid || !did) return
  const data = await fetchDeliverableById(aid, did)
  deliverableTitle.value = data?.title || `Entregable #${did}`
}

async function load() {
  const did = deliverableId.value
  const sid = submissionId.value
  if (!did || !sid) return
  pageBusy.value = true
  try {
    await loadDeliverableTitle()
    await getSubmission(did, sid)
    syncGradingFormFromSubmission()
  } finally {
    pageBusy.value = false
  }
}

async function saveGrading() {
  const did = deliverableId.value
  const sid = submissionId.value
  if (!did || !sid) return
  gradingSaving.value = true
  try {
    await gradeSubmission(did, sid, {
      grade: gradingGrade.value,
      feedback: gradingFeedback.value?.trim() || null,
      status: gradingStatus.value,
    })
    syncGradingFormFromSubmission()
  } catch {
    /* toast en composable */
  } finally {
    gradingSaving.value = false
  }
}

watch(
  () => [activityId.value, deliverableId.value, submissionId.value],
  () => {
    load()
  },
  { immediate: true }
)

watch(
  () => submission.value?.id,
  () => {
    syncGradingFormFromSubmission()
  }
)

onMounted(() => {
  if (!can('submission-view')) {
    router.replace(backTo.value)
  }
})
</script>
