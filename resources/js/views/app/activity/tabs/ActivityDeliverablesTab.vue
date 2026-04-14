<template>
  <Card>
    <template #title>
      <div class="flex flex-wrap items-center justify-between gap-3 w-full">
        <span>Entregables</span>
        <Button
          v-if="can('deliverable-create')"
          label="Nuevo entregable"
          icon="pi pi-plus"
          size="small"
          @click="goCreate"
        />
      </div>
    </template>
    <template #content>
      <DataTable :value="deliverables" :loading="isLoading" data-key="id" striped-rows class="text-sm">
        <template #empty>
          <span class="text-slate-500">No hay entregables.</span>
        </template>
        <Column field="short_code" header="Código" class="w-28" />
        <Column field="title" header="Título" />
        <Column field="due_date" header="Entrega" class="whitespace-nowrap">
          <template #body="{ data }">
            <UtcFormatted :value="data.due_date" variant="datetime" />
          </template>
        </Column>
        <Column header="Estado" class="w-36">
          <template #body="{ data }">
            <Tag :value="deliverableStatusLabel(data)" severity="secondary" />
          </template>
        </Column>
        <Column header="Ámbito" class="w-32">
          <template #body="{ data }">
            {{ data.is_group_deliverable ? 'Grupo' : 'Individual' }}
          </template>
        </Column>
        <Column
          v-if="
            can('submission-list') ||
            can('deliverable-edit') ||
            can('deliverable-delete')
          "
          header="Acciones"
          class="w-40"
        >
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button
                v-if="can('submission-list')"
                v-tooltip.top="'Ver entregas'"
                icon="pi pi-inbox"
                rounded
                text
                severity="secondary"
                size="small"
                @click="goSubmissions(data.id)"
              />
              <Button
                v-if="can('deliverable-edit')"
                v-tooltip.top="'Editar'"
                icon="pi pi-pencil"
                rounded
                text
                severity="secondary"
                size="small"
                @click="goEdit(data.id)"
              />
              <Button
                v-if="can('deliverable-delete')"
                v-tooltip.top="'Eliminar'"
                icon="pi pi-trash"
                rounded
                text
                severity="danger"
                size="small"
                @click="confirmDelete(data)"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </template>
  </Card>
</template>

<script setup>
import { computed, inject, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAbility } from '@casl/vue'
import useActivityDeliverables from '@/composables/activityDeliverables'

const DELIVERABLE_STATUS_LABELS = {
  0: 'Borrador',
  1: 'Publicado',
  2: 'Cerrado',
}

const { can } = useAbility()
const route = useRoute()
const router = useRouter()
const swal = inject('$swal')

const activityId = inject('activityId')
const { deliverables, isLoading, getDeliverables, deleteDeliverable } = useActivityDeliverables()

const tabQuery = computed(() => {
  const raw = route.query.fromSubjectGroup
  if (raw == null || raw === '') return {}
  return { fromSubjectGroup: String(raw) }
})

function goCreate() {
  router.push({
    name: 'app.activity.deliverable.create',
    params: { activityId: activityId?.value },
    query: { ...tabQuery.value },
  })
}

function goEdit(deliverableId) {
  router.push({
    name: 'app.activity.deliverable.edit',
    params: { activityId: activityId?.value, deliverableId },
    query: { ...tabQuery.value },
  })
}

function goSubmissions(deliverableId) {
  router.push({
    name: 'app.activity.deliverable.submissions',
    params: { activityId: activityId?.value, deliverableId },
    query: { ...tabQuery.value },
  })
}

function deliverableStatusLabel(row) {
  const s = row?.status
  const v = typeof s === 'object' && s !== null ? s.value : s
  if (v == null) return '—'
  return DELIVERABLE_STATUS_LABELS[v] ?? String(v)
}

function confirmDelete(row) {
  const run = () => deleteDeliverable(activityId?.value, row.id).then(() => getDeliverables(activityId?.value))

  if (!swal) {
    run()
    return
  }

  swal({
    icon: 'warning',
    title: '¿Eliminar entregable?',
    text: `Se eliminará «${row.title || 'este entregable'}» de forma permanente.`,
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#ef4444',
  }).then((result) => {
    if (result.isConfirmed) run()
  })
}

watch(
  () => activityId?.value,
  async (id) => {
    if (!id) return
    try {
      await getDeliverables(id)
    } catch {
      /* toast en composable */
    }
  },
  { immediate: true }
)
</script>
