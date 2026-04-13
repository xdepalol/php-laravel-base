<template>
  <Card>
    <template #title>Resumen</template>
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
          <dd class="mt-1 text-slate-800">
            {{ activity.start_date || '—' }} → {{ activity.end_date || '—' }}
          </dd>
        </div>
        <div class="sm:col-span-2">
          <dt class="text-slate-500 font-medium">Configuración</dt>
          <dd class="mt-1 flex flex-wrap gap-2">
            <Tag v-if="activity.has_sprints" value="Sprints" severity="info" />
            <Tag v-if="activity.has_backlog" value="Backlog" severity="secondary" />
            <Tag v-if="activity.is_intermodular" value="Intermodular" severity="warn" />
            <span
              v-if="!activity.has_sprints && !activity.has_backlog && !activity.is_intermodular"
              class="text-slate-600"
            >—</span>
          </dd>
        </div>
        <div class="sm:col-span-2" v-if="subjectGroupLabels.length">
          <dt class="text-slate-500 font-medium">Grupos de asignatura</dt>
          <dd class="mt-1 flex flex-wrap gap-2">
            <Tag v-for="(g, i) in subjectGroupLabels" :key="i" :value="g" severity="contrast" />
          </dd>
        </div>
      </dl>
    </template>
  </Card>
</template>

<script setup>
import { computed, inject } from 'vue'

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

const activityRef = inject('activity')

const activity = computed(() => activityRef?.value ?? {})

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

const subjectGroupLabels = computed(() => {
  const sg = activity.value?.subject_groups
  if (!Array.isArray(sg)) return []
  return sg.map((x) => {
    if (typeof x === 'object' && x !== null) return x.name || x.title || `Grupo #${x.id}`
    return `Grupo #${x}`
  })
})
</script>
