<template>
  <Card>
    <template #title>Entregas</template>
    <template #subtitle>
      Por cada entregable y equipo o estudiante, se muestra la última entrega registrada (si existe).
    </template>
    <template #content>
      <DataTable
        :value="rows"
        :loading="loading"
        data-key="rowKey"
        striped-rows
        class="text-sm"
        paginator
        :rows="15"
        :rows-per-page-options="[15, 30, 50]"
      >
        <template #empty>
          <span class="text-slate-500">No hay datos para mostrar.</span>
        </template>
        <Column field="deliverableTitle" header="Entregable" class="min-w-[140px]" />
        <Column header="Ámbito" class="w-28">
          <template #body="{ data }">
            {{ data.isGroup ? 'Grupo' : 'Individual' }}
          </template>
        </Column>
        <Column header="Equipo / estudiante" class="min-w-[160px]">
          <template #body="{ data }">
            {{ data.subjectLabel }}
          </template>
        </Column>
        <Column header="Última entrega" class="whitespace-nowrap w-40">
          <template #body="{ data }">
            {{ formatDateTime(data.lastSubmittedAt) }}
          </template>
        </Column>
        <Column header="Estado" class="w-36">
          <template #body="{ data }">
            <Tag v-if="data.lastStatusLabel" :value="data.lastStatusLabel" severity="secondary" />
            <span v-else class="text-slate-500">—</span>
          </template>
        </Column>
        <Column header="" class="w-36">
          <template #body="{ data }">
            <router-link
              v-if="data.lastSubmissionId"
              :to="detailTo(data)"
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
</template>

<script setup>
import { computed, inject, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAbility } from '@casl/vue'
import axios from 'axios'

const { can } = useAbility()
const router = useRouter()

const SUBMISSION_STATUS = {
  0: 'Pendiente',
  1: 'Entregada',
  2: 'Calificada',
}

const activityId = inject('activityId')
const route = useRoute()

const rows = ref([])
const loading = ref(false)

const tabQuery = computed(() => {
  const raw = route.query.fromSubjectGroup
  if (raw == null || raw === '') return {}
  return { fromSubjectGroup: String(raw) }
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

function formatMemberName(member) {
  const u = member?.student?.user ?? member?.user
  if (!u) return `Estudiante #${member?.student_id ?? '—'}`
  const parts = [u.name, u.surname1, u.surname2].filter(Boolean)
  return parts.length ? parts.join(' ') : `Usuario #${u.id ?? member.student_id}`
}

function formatDateTime(iso) {
  if (!iso) return '—'
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return '—'
  return d.toLocaleString('es-ES', {
    dateStyle: 'short',
    timeStyle: 'short',
  })
}

function statusLabel(sub) {
  if (!sub) return null
  const v = sub.status?.value ?? sub.status
  if (v == null) return null
  return SUBMISSION_STATUS[v] ?? String(v)
}

function detailTo(data) {
  return {
    name: 'app.activity.submission.detail',
    params: {
      activityId: activityId?.value,
      deliverableId: data.deliverableId,
      submissionId: data.lastSubmissionId,
    },
    query: { ...tabQuery.value },
  }
}

async function load() {
  const aid = activityId?.value
  if (!aid) return
  loading.value = true
  rows.value = []
  try {
    const [dRes, tRes] = await Promise.all([
      axios.get(`/api/activities/${aid}/deliverables`),
      axios.get(`/api/activities/${aid}/teams`),
    ])
    const deliverables = Array.isArray(unwrap(dRes)) ? unwrap(dRes) : []
    const teams = Array.isArray(unwrap(tRes)) ? unwrap(tRes) : []

    const memberLists = await Promise.all(
      teams.map((team) =>
        axios
          .get(`/api/activities/${aid}/teams/${team.id}/students`)
          .then((r) => ({ team, members: unwrap(r) }))
          .catch(() => ({ team, members: [] }))
      )
    )

    const studentsUnique = new Map()
    for (const { team, members } of memberLists) {
      const list = Array.isArray(members) ? members : []
      for (const m of list) {
        const sid = m.student_id ?? m.student?.user_id
        if (sid == null || studentsUnique.has(sid)) continue
        studentsUnique.set(sid, {
          student_id: sid,
          label: formatMemberName(m),
          teamName: team.name || `Equipo #${team.id}`,
        })
      }
    }
    const studentRows = [...studentsUnique.values()]

    const submissionLists = await Promise.all(
      deliverables.map(async (d) => {
        try {
          const sRes = await axios.get(`/api/deliverables/${d.id}/submissions`)
          const subs = unwrap(sRes)
          return { deliverable: d, subs: Array.isArray(subs) ? subs : [] }
        } catch {
          return { deliverable: d, subs: [] }
        }
      })
    )

    const built = []
    for (const { deliverable: d, subs } of submissionLists) {
      const isGroup = !!d.is_group_deliverable
      const title = d.title || `Entregable #${d.id}`

      if (isGroup) {
        for (const team of teams) {
          const forKey = subs.filter((s) => Number(s.team_id) === Number(team.id))
          const last = pickLastSubmission(forKey)
          built.push({
            rowKey: `${d.id}-t-${team.id}`,
            deliverableId: d.id,
            deliverableTitle: title,
            isGroup: true,
            subjectLabel: team.name || `Equipo #${team.id}`,
            lastSubmissionId: last?.id ?? null,
            lastSubmittedAt: last?.submitted_at ?? null,
            lastStatusLabel: statusLabel(last),
          })
        }
      } else {
        for (const st of studentRows) {
          const forKey = subs.filter((s) => Number(s.student_id) === Number(st.student_id))
          const last = pickLastSubmission(forKey)
          built.push({
            rowKey: `${d.id}-s-${st.student_id}`,
            deliverableId: d.id,
            deliverableTitle: title,
            isGroup: false,
            subjectLabel: st.label,
            lastSubmissionId: last?.id ?? null,
            lastSubmittedAt: last?.submitted_at ?? null,
            lastStatusLabel: statusLabel(last),
          })
        }
      }
    }

    rows.value = built
  } finally {
    loading.value = false
  }
}

watch(() => activityId?.value, load, { immediate: true })

onMounted(() => {
  if (!can('submission-list')) {
    router.replace({
      name: 'app.activity.overview',
      params: { activityId: activityId?.value },
      query: { ...tabQuery.value },
    })
  }
})
</script>
