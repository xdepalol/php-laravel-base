<template>
  <Card>
    <template #title>Entregas del equipo</template>
    <template #content>
      <p class="text-sm text-slate-600 mb-4">
        Entregables de la actividad y entregas registradas para este equipo.
      </p>
      <DataTable :value="rows" :loading="loading" data-key="deliverable_id" striped-rows class="text-sm">
        <template #empty>
          <span class="text-slate-500">No hay entregables en esta actividad.</span>
        </template>
        <Column field="title" header="Entregable" />
        <Column field="due_date" header="Fecha límite" class="whitespace-nowrap w-32">
          <template #body="{ data }">
            {{ data.due_date || '—' }}
          </template>
        </Column>
        <Column header="Entregas (equipo)" class="w-36">
          <template #body="{ data }">
            {{ data.delivered_count }}/{{ data.team_submission_count }}
          </template>
        </Column>
        <Column header="Estado" class="w-40">
          <template #body="{ data }">
            <Tag v-if="data.last_status_label" :value="data.last_status_label" severity="secondary" />
            <span v-else class="text-slate-500">—</span>
          </template>
        </Column>
      </DataTable>
    </template>
  </Card>
</template>

<script setup>
import { inject, ref, watch } from 'vue'
import axios from 'axios'

const activityId = inject('activityId')
const teamId = inject('teamId')

const rows = ref([])
const loading = ref(false)

const SUBMISSION_STATUS = {
  0: 'Pendiente',
  1: 'Entregada',
  2: 'Calificada',
}

function unwrap(response) {
  return response.data?.data ?? response.data
}

async function load() {
  const aid = activityId?.value
  const tid = teamId?.value
  if (!aid || !tid) return
  loading.value = true
  rows.value = []
  try {
    const dRes = await axios.get(`/api/activities/${aid}/deliverables`)
    const dList = unwrap(dRes)
    const deliverables = Array.isArray(dList) ? dList : []
    const enriched = await Promise.all(
      deliverables.map(async (d) => {
        const id = d.id
        try {
          const sRes = await axios.get(`/api/deliverables/${id}/submissions`)
          const subs = unwrap(sRes)
          const list = Array.isArray(subs) ? subs : []
          const forTeam = list.filter((s) => Number(s.team_id) === Number(tid))
          const delivered = forTeam.filter((s) => {
            const st = s.status?.value ?? s.status
            return st >= 1
          })
          let lastStatusLabel = null
          if (forTeam.length) {
            const last = forTeam.reduce((a, b) => {
              const da = a.submitted_at ? new Date(a.submitted_at).getTime() : 0
              const db = b.submitted_at ? new Date(b.submitted_at).getTime() : 0
              return db >= da ? b : a
            })
            const sv = last.status?.value ?? last.status
            lastStatusLabel = SUBMISSION_STATUS[sv] ?? String(sv)
          }
          return {
            deliverable_id: id,
            title: d.title,
            due_date: d.due_date,
            team_submission_count: forTeam.length,
            delivered_count: delivered.length,
            last_status_label: lastStatusLabel,
          }
        } catch {
          return {
            deliverable_id: id,
            title: d.title,
            due_date: d.due_date,
            team_submission_count: 0,
            delivered_count: 0,
            last_status_label: null,
          }
        }
      })
    )
    rows.value = enriched
  } finally {
    loading.value = false
  }
}

watch([() => activityId?.value, () => teamId?.value], load, { immediate: true })
</script>
