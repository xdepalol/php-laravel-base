<template>
  <Card>
    <template #title>Resumen del equipo</template>
    <template #content>
      <p class="text-sm text-slate-600 mb-6">
        Trabajo del equipo en el contexto de esta actividad: fases compartidas, backlog y tareas (si la
        actividad tiene backlog), y entregas asociadas a este equipo.
      </p>
      <h3 class="text-sm font-semibold text-slate-800 mb-3">Miembros</h3>
      <ul v-if="members.length" class="text-sm text-slate-800 space-y-2">
        <li v-for="m in members" :key="m.key" class="flex flex-wrap gap-2 items-baseline">
          <span class="font-medium">{{ m.name }}</span>
          <span v-if="m.role" class="text-slate-500 text-xs">{{ m.role }}</span>
        </li>
      </ul>
      <p v-else class="text-slate-500 text-sm">No hay estudiantes asignados a este equipo.</p>
    </template>
  </Card>
</template>

<script setup>
import { computed, inject } from 'vue'

const teamRef = inject('team')

const members = computed(() => {
  const students = teamRef?.value?.students
  if (!Array.isArray(students)) return []
  return students.map((row, i) => {
    const inner = row.student ?? row
    const u = inner.user ?? row.user
    const parts = u ? [u.name, u.surname1, u.surname2].filter(Boolean) : []
    const sid = inner.user_id ?? row.student_id ?? i
    const name = parts.length ? parts.join(' ') : u?.email || `Estudiante #${sid}`
    const roleName = row.activity_role?.name ?? null
    return { key: sid, name, role: roleName }
  })
})
</script>
