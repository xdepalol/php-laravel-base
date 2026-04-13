<template>
  <Card>
    <template #title>Estudiantes</template>
    <template #content>
      <DataTable
        :value="listings.enrollments"
        :loading="listings.enrollmentsLoading"
        data-key="id"
        striped-rows
        class="text-sm"
      >
        <template #empty>
          <span class="text-slate-500">No hay matrículas en este grupo.</span>
        </template>
        <Column field="student.user.name" header="Estudiante">
          <template #body="{ data }">
            {{ studentDisplayName(data) }}
          </template>
        </Column>
        <Column field="status.label" header="Estado">
          <template #body="{ data }">
            <Tag
              v-if="data.status"
              :value="data.status.label || data.status.name || '—'"
              severity="secondary"
            />
            <span v-else>—</span>
          </template>
        </Column>
      </DataTable>
    </template>
  </Card>
</template>

<script setup>
import { inject } from 'vue'

const listings = inject('subjectGroupListings')

function studentDisplayName(row) {
  const u = row.student?.user
  if (!u) return '—'
  const parts = [u.name, u.surname1, u.surname2].filter(Boolean)
  return parts.length ? parts.join(' ') : u.email || '—'
}
</script>
