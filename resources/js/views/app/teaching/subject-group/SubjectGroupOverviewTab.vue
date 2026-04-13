<template>
  <Card>
    <template #title>Resumen</template>
    <template #content>
      <p class="text-sm text-slate-600 leading-relaxed">
        Datos del grupo de asignatura. Usa las pestañas <strong>Estudiantes</strong> para ver las
        matrículas y <strong>Actividades</strong> para abrir las actividades vinculadas a este grupo.
      </p>
      <dl class="mt-6 grid gap-4 sm:grid-cols-2 text-sm border-t border-slate-100 pt-6">
        <div>
          <dt class="text-slate-500 font-medium">Asignatura</dt>
          <dd class="mt-1 text-slate-800">{{ subjectTitle }}</dd>
        </div>
        <div>
          <dt class="text-slate-500 font-medium">Grupo</dt>
          <dd class="mt-1 text-slate-800">{{ groupLabel }}</dd>
        </div>
        <div v-if="academicYearLabel">
          <dt class="text-slate-500 font-medium">Curso académico</dt>
          <dd class="mt-1 text-slate-800">{{ academicYearLabel }}</dd>
        </div>
      </dl>
    </template>
  </Card>
</template>

<script setup>
import { computed, inject } from 'vue'

const subjectGroupRef = inject('subjectGroup')

const subjectTitle = computed(() => {
  const s = subjectGroupRef?.value?.subject
  return s?.title || s?.name || '—'
})

const groupLabel = computed(() => {
  const g = subjectGroupRef?.value?.group
  if (g?.name) return g.name
  const id = subjectGroupRef?.value?.group_id
  return id != null ? `Grupo #${id}` : '—'
})

const academicYearLabel = computed(() => {
  const y = subjectGroupRef?.value?.academic_year
  if (typeof y === 'object' && y !== null) return y.name || y.label || y.title || null
  const id = subjectGroupRef?.value?.academic_year_id
  return id != null ? `Curso #${id}` : null
})
</script>
