<template>
  <div class="deliverable-submissions-page space-y-6">
    <div class="flex flex-wrap items-center gap-3">
      <router-link
        :to="backToDeliverables"
        class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-slate-900"
      >
        <i class="pi pi-arrow-left" />
        Volver a entregables
      </router-link>
      <router-link
        v-if="can('deliverable-edit')"
        :to="editDeliverableTo"
        class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-slate-900"
      >
        <i class="pi pi-pencil" />
        Editar entregable
      </router-link>
    </div>

    <Card class="max-w-5xl">
      <template #title>
        <span>Entregas</span>
        <span v-if="deliverableLabel" class="text-slate-600 font-normal text-base">
          — {{ deliverableLabel }}
        </span>
      </template>
      <template #subtitle>
        <span v-if="isGroupScope">
          Una fila por equipo. Se muestra la última entrega registrada (fecha y calificación, si existen).
        </span>
        <span v-else>
          Una fila por estudiante de la actividad. Se muestra la última entrega registrada (fecha y calificación, si existen).
        </span>
      </template>
      <template #content>
        <div v-if="pageBusy" class="flex justify-center py-12 text-blue-600">
          <i class="pi pi-spin pi-spinner text-2xl" aria-hidden="true" />
        </div>
        <DataTable
          v-else
          :value="rows"
          data-key="rowKey"
          size="small"
          striped-rows
          class="text-sm"
        >
          <template #empty>
            <span class="text-slate-500">{{ emptyMessage }}</span>
          </template>
          <Column :header="participantColumnHeader" class="min-w-[180px]">
            <template #body="{ data }">
              <span class="font-medium text-slate-800">{{ data.subjectLabel }}</span>
            </template>
          </Column>
          <Column header="Entregada" class="w-44 whitespace-nowrap">
            <template #body="{ data }">
              <UtcFormatted v-if="data.submitted_at" :value="data.submitted_at" variant="datetime" />
              <span v-else class="text-slate-400">—</span>
            </template>
          </Column>
          <Column header="Calificación" class="w-28">
            <template #body="{ data }">
              <span v-if="data.grade != null && data.grade !== ''" class="text-slate-800">{{
                formatGrade(data.grade)
              }}</span>
              <span v-else class="text-slate-400">—</span>
            </template>
          </Column>
          <Column header="Estado" class="w-36">
            <template #body="{ data }">
              <Tag v-if="data.statusLabel" :value="data.statusLabel" severity="secondary" />
              <span v-else class="text-slate-400">—</span>
            </template>
          </Column>
          <Column class="w-28">
            <template #body="{ data }">
              <router-link
                v-if="data.submissionId && can('submission-view')"
                :to="submissionDetailTo(data.submissionId)"
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
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAbility } from '@casl/vue'
import useActivityDeliverables from '@/composables/activityDeliverables'
import useActivityTeams from '@/composables/activityTeams'
import useDeliverableSubmissions from '@/composables/deliverableSubmissions'
import { formatStudentDisplayName } from '@/utils/studentDisplayName'

const { can } = useAbility()
const route = useRoute()
const router = useRouter()

const { deliverable, getDeliverable } = useActivityDeliverables()
const { fetchTeamsList, fetchTeamStudentsList } = useActivityTeams()
const { submissions, getSubmissions } = useDeliverableSubmissions()

const activityId = computed(() => Number(route.params.activityId))
const deliverableId = computed(() => Number(route.params.deliverableId))

const rows = ref([])

const isGroupScope = computed(() => !!deliverable.value?.is_group_deliverable)

const participantColumnHeader = computed(() =>
  isGroupScope.value ? 'Equipo' : 'Estudiante'
)

const emptyMessage = computed(() => {
  if (isGroupScope.value) {
    return 'No hay equipos en esta actividad.'
  }
  return 'No hay estudiantes asignados a equipos en esta actividad.'
})

const tabQuery = computed(() => {
  const raw = route.query.fromSubjectGroup
  if (raw == null || raw === '') return {}
  return { fromSubjectGroup: String(raw) }
})

const deliverableLabel = computed(() => {
  const d = deliverable.value
  if (!d?.id) return ''
  const code = (d.short_code || '').trim()
  const title = (d.title || '').trim()
  if (code && title) return `${code} · ${title}`
  return title || (code || `Entregable #${d.id}`)
})

const backToDeliverables = computed(() => ({
  name: 'app.activity.deliverables',
  params: { activityId: activityId.value },
  query: { ...tabQuery.value },
}))

const editDeliverableTo = computed(() => ({
  name: 'app.activity.deliverable.edit',
  params: { activityId: activityId.value, deliverableId: deliverableId.value },
  query: { ...tabQuery.value },
}))

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

const SUBMISSION_STATUS = {
  0: 'Pendiente',
  1: 'Entregada',
  2: 'Calificada',
}

function submissionStatusLabel(sub) {
  if (!sub) return null
  const s = sub?.status
  const v = typeof s === 'object' && s !== null ? s.value : s
  if (v == null) return null
  return SUBMISSION_STATUS[v] ?? String(v)
}

function pickLastSubmission(subs) {
  if (!Array.isArray(subs) || !subs.length) return null
  return subs.reduce((a, b) => {
    const ta = a.submitted_at ? new Date(a.submitted_at).getTime() : 0
    const tb = b.submitted_at ? new Date(b.submitted_at).getTime() : 0
    if (tb !== ta) return tb >= ta ? b : a
    return (b.id ?? 0) >= (a.id ?? 0) ? b : a
  })
}

function formatMemberName(member) {
  const st = member?.student ?? (member?.user ? { user: member.user } : null)
  return formatStudentDisplayName(st, member?.student_id)
}

function formatGrade(g) {
  if (g == null || g === '') return '—'
  const n = Number(g)
  return Number.isFinite(n) ? String(n) : String(g)
}

function rowFromSubmission(sub, rowKey, subjectLabel) {
  if (!sub) {
    return {
      rowKey,
      subjectLabel,
      submitted_at: null,
      grade: null,
      submissionId: null,
      statusLabel: null,
    }
  }
  return {
    rowKey,
    subjectLabel,
    submitted_at: sub.submitted_at ?? null,
    grade: sub.grade ?? null,
    submissionId: sub.id ?? null,
    statusLabel: submissionStatusLabel(sub),
  }
}

const pageBusy = ref(false)

async function buildRows() {
  const aid = activityId.value
  const did = deliverableId.value
  const d = deliverable.value
  if (!aid || !did || !d?.id) {
    rows.value = []
    return
  }

  const subs = Array.isArray(submissions.value) ? submissions.value : []
  const isGroup = !!d.is_group_deliverable

  const teamList = await fetchTeamsList(aid)

  if (isGroup) {
    const built = []
    for (const team of teamList) {
      const forTeam = subs.filter((s) => Number(s.team_id) === Number(team.id))
      const last = pickLastSubmission(forTeam)
      const label = team.name || `Equipo #${team.id}`
      built.push(rowFromSubmission(last, `team-${team.id}`, label))
    }
    rows.value = built
    return
  }

  const memberLists = await Promise.all(
    teamList.map((team) => fetchTeamStudentsList(aid, team.id))
  )

  const studentsUnique = new Map()
  for (let i = 0; i < teamList.length; i++) {
    const members = Array.isArray(memberLists[i]) ? memberLists[i] : []
    for (const m of members) {
      const sid = m.student_id ?? m.student?.user_id
      if (sid == null || studentsUnique.has(sid)) continue
      studentsUnique.set(sid, {
        student_id: sid,
        label: formatMemberName(m),
      })
    }
  }

  const built = []
  for (const st of studentsUnique.values()) {
    const forSt = subs.filter((s) => Number(s.student_id) === Number(st.student_id))
    const last = pickLastSubmission(forSt)
    built.push(rowFromSubmission(last, `student-${st.student_id}`, st.label))
  }
  rows.value = built
}

async function load() {
  const aid = activityId.value
  const did = deliverableId.value
  if (!aid || !did) return
  pageBusy.value = true
  try {
    await getDeliverable(aid, did)
    await getSubmissions(did)
    await buildRows()
  } finally {
    pageBusy.value = false
  }
}

watch(
  () => [activityId.value, deliverableId.value],
  () => {
    load()
  },
  { immediate: true }
)

onMounted(() => {
  if (!can('submission-list')) {
    router.replace(backToDeliverables.value)
  }
})
</script>
