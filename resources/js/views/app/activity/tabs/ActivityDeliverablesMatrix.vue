<template>
  <div class="deliverables-matrix space-y-6">
    <div v-if="loading" class="flex justify-center py-10 text-blue-600">
      <i class="pi pi-spin pi-spinner text-2xl" aria-hidden="true" />
    </div>
    <template v-else>
      <div v-if="groupDeliverables.length && teamRows.length">
        <h3 class="text-sm font-semibold text-slate-800 mb-2">Entregas por equipo</h3>
        <p class="text-xs text-slate-500 mb-2">
          <template v-if="studentMatrixUserId">
            Solo tu equipo · Columnas = entregables de grupo. «Ver» abre el detalle de la entrega.
          </template>
          <template v-else>
            Filas = equipos · Columnas = entregables de grupo. Celdas = última entrega (si existe).
          </template>
        </p>
        <div class="overflow-x-auto border border-slate-200 rounded-lg">
          <DataTable
            :value="teamRows"
            data-key="rowKey"
            size="small"
            striped-rows
            class="text-xs min-w-[480px]"
          >
            <Column class="min-w-[140px] bg-white">
              <template #header>
                <span class="font-medium">Equipo</span>
              </template>
              <template #body="{ data }">
                <span class="font-medium text-slate-800">{{ data.rowLabel }}</span>
              </template>
            </Column>
            <Column
              v-for="col in groupDeliverables"
              :key="col.id"
              class="text-center min-w-[100px]"
            >
              <template #header>
                <span
                  v-tooltip.top="tooltipFor(col)"
                  class="cursor-default inline-block max-w-[7rem] truncate font-medium text-slate-700"
                >
                  {{ headerLabel(col) }}
                </span>
              </template>
              <template #body="{ data }">
                <ActivityDeliverablesMatrixCell
                  :cell="data.cells[col.id]"
                  :activity-id="numericActivityId"
                  :deliverable-id="col.id"
                  :tab-query="tabQuery"
                />
              </template>
            </Column>
          </DataTable>
        </div>
      </div>

      <div v-if="individualDeliverables.length && studentRows.length">
        <h3 class="text-sm font-semibold text-slate-800 mb-2">Entregas individuales</h3>
        <p class="text-xs text-slate-500 mb-2">
          <template v-if="studentMatrixUserId"> Tus entregas personales · «Ver» abre el detalle. </template>
          <template v-else> Filas = estudiantes · Columnas = entregables individuales. </template>
        </p>
        <div class="overflow-x-auto border border-slate-200 rounded-lg">
          <DataTable
            :value="studentRows"
            data-key="rowKey"
            size="small"
            striped-rows
            class="text-xs min-w-[480px]"
          >
            <Column class="min-w-[160px] bg-white">
              <template #header>
                <span class="font-medium">Estudiante</span>
              </template>
              <template #body="{ data }">
                <span class="text-slate-800">{{ data.rowLabel }}</span>
              </template>
            </Column>
            <Column
              v-for="col in individualDeliverables"
              :key="col.id"
              class="text-center min-w-[100px]"
            >
              <template #header>
                <span
                  v-tooltip.top="tooltipFor(col)"
                  class="cursor-default inline-block max-w-[7rem] truncate font-medium text-slate-700"
                >
                  {{ headerLabel(col) }}
                </span>
              </template>
              <template #body="{ data }">
                <ActivityDeliverablesMatrixCell
                  :cell="data.cells[col.id]"
                  :activity-id="numericActivityId"
                  :deliverable-id="col.id"
                  :tab-query="tabQuery"
                />
              </template>
            </Column>
          </DataTable>
        </div>
      </div>

      <p v-if="!loading && !matrixHasContent" class="text-sm text-slate-500">
        No hay datos para la matriz (añade equipos, estudiantes y entregables).
      </p>
    </template>
  </div>
</template>

<script setup>
import { computed, inject, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import { deliverableMatrixHeaderLabel } from '@/utils/deliverableUi'
import { sortDeliverablesByDueDateThenId } from '@/utils/deliverablesSort'
import ActivityDeliverablesMatrixCell from './ActivityDeliverablesMatrixCell.vue'

const SUBMISSION_STATUS = {
  0: 'Pendiente',
  1: 'Entregada',
  2: 'Calificada',
}

const props = defineProps({
  deliverables: { type: Array, default: () => [] },
  /** Vista alumno: una fila individual (student_id = user id). */
  studentMatrixUserId: { type: Number, default: null },
  /** Etiqueta de fila para entregas individuales del alumno. */
  studentMatrixRowLabel: { type: String, default: '' },
})

const activityId = inject('activityId')
const route = useRoute()

const numericActivityId = computed(() => Number(activityId?.value) || 0)

const tabQuery = computed(() => {
  const raw = route.query.fromSubjectGroup
  if (raw == null || raw === '') return {}
  return { fromSubjectGroup: String(raw) }
})

const loading = ref(false)
const teamRows = ref([])
const studentRows = ref([])

const orderedDeliverables = computed(() => sortDeliverablesByDueDateThenId(props.deliverables))

const groupDeliverables = computed(() =>
  orderedDeliverables.value.filter((d) => d.is_group_deliverable)
)
const individualDeliverables = computed(() =>
  orderedDeliverables.value.filter((d) => !d.is_group_deliverable)
)

const matrixHasContent = computed(() => {
  return (
    (groupDeliverables.value.length && teamRows.value.length) ||
    (individualDeliverables.value.length && studentRows.value.length)
  )
})

function unwrap(response) {
  return response.data?.data ?? response.data
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

function statusLabel(sub) {
  if (!sub) return null
  const v = sub.status?.value ?? sub.status
  if (v == null) return null
  return SUBMISSION_STATUS[v] ?? String(v)
}

function headerLabel(d) {
  return deliverableMatrixHeaderLabel(d)
}

function tooltipFor(d) {
  const t = (d.title || '').trim()
  const code = (d.short_code || '').trim()
  const parts = [`#${d.id}`]
  if (code) parts.push(code)
  if (t) parts.push(t)
  return parts.length > 1 ? parts.join(' — ') : `Entregable #${d.id}`
}

function formatMemberName(member) {
  const u = member?.student?.user ?? member?.user
  if (!u) return `Estudiante #${member?.student_id ?? '—'}`
  const parts = [u.name, u.surname1, u.surname2].filter(Boolean)
  return parts.length ? parts.join(' ') : `Usuario #${u.id ?? member.student_id}`
}

async function loadMatrix() {
  const aid = activityId?.value
  if (!aid || !props.deliverables?.length) {
    teamRows.value = []
    studentRows.value = []
    return
  }

  loading.value = true
  try {
    const tRes = await axios.get(`/api/activities/${aid}/teams`)
    const teamList = Array.isArray(unwrap(tRes)) ? unwrap(tRes) : []

    const memberLists = await Promise.all(
      teamList.map((team) =>
        axios
          .get(`/api/activities/${aid}/teams/${team.id}/students`)
          .then((r) => unwrap(r))
          .catch(() => [])
      )
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

    const subMap = {}
    await Promise.all(
      orderedDeliverables.value.map(async (d) => {
        try {
          const sRes = await axios.get(`/api/deliverables/${d.id}/submissions`)
          const subs = unwrap(sRes)
          subMap[d.id] = Array.isArray(subs) ? subs : []
        } catch {
          subMap[d.id] = []
        }
      })
    )

    const gDel = groupDeliverables.value
    const tRows = []
    for (const team of teamList) {
      const cells = {}
      for (const d of gDel) {
        const subs = subMap[d.id] || []
        const forTeam = subs.filter((s) => Number(s.team_id) === Number(team.id))
        const last = pickLastSubmission(forTeam)
        cells[d.id] = {
          last,
          statusLabel: statusLabel(last),
          submissionId: last?.id ?? null,
        }
      }
      tRows.push({
        rowKey: `team-${team.id}`,
        rowLabel: team.name || `Equipo #${team.id}`,
        cells,
      })
    }
    teamRows.value = tRows

    const iDel = individualDeliverables.value
    let sRows = []
    for (const st of studentsUnique.values()) {
      const cells = {}
      for (const d of iDel) {
        const subs = subMap[d.id] || []
        const forSt = subs.filter((s) => Number(s.student_id) === Number(st.student_id))
        const last = pickLastSubmission(forSt)
        cells[d.id] = {
          last,
          statusLabel: statusLabel(last),
          submissionId: last?.id ?? null,
        }
      }
      sRows.push({
        rowKey: `student-${st.student_id}`,
        rowLabel: st.label,
        cells,
      })
    }

    const scopedId = props.studentMatrixUserId
    if (scopedId != null) {
      sRows = sRows.filter((r) => r.rowKey === `student-${scopedId}`)
      if (iDel.length && sRows.length === 0) {
        const cells = {}
        for (const d of iDel) {
          const subs = subMap[d.id] || []
          const forSt = subs.filter((s) => Number(s.student_id) === Number(scopedId))
          const last = pickLastSubmission(forSt)
          cells[d.id] = {
            last,
            statusLabel: statusLabel(last),
            submissionId: last?.id ?? null,
          }
        }
        const label =
          props.studentMatrixRowLabel?.trim() ||
          `Estudiante #${scopedId}`
        sRows = [
          {
            rowKey: `student-${scopedId}`,
            rowLabel: label,
            cells,
          },
        ]
      }
    }

    studentRows.value = sRows
  } finally {
    loading.value = false
  }
}

watch(
  () => [
    activityId?.value,
    props.deliverables,
    props.studentMatrixUserId,
    props.studentMatrixRowLabel,
  ],
  () => {
    loadMatrix()
  },
  { deep: true, immediate: true }
)
</script>
