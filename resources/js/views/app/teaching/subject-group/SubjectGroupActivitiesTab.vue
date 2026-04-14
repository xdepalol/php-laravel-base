<template>
  <Card>
    <template #title>
      <div class="flex flex-wrap items-center justify-between gap-3 w-full">
        <span>Actividades</span>
        <Button
          v-if="canCreate"
          label="Nueva actividad"
          icon="pi pi-plus"
          size="small"
          @click="goCreate"
        />
      </div>
    </template>
    <template #content>
      <DataTable
        :value="listings.groupActivities"
        :loading="listings.activitiesLoading"
        data-key="id"
        striped-rows
        class="text-sm"
      >
        <template #empty>
          <span class="text-slate-500">No hay actividades asociadas a este grupo.</span>
        </template>
        <Column field="title" header="Título">
          <template #body="{ data }">
            <router-link
              :to="{
                name: 'app.activity.overview',
                params: { activityId: data.id },
                query: { fromSubjectGroup: String(groupId) },
              }"
              class="text-blue-700 hover:underline font-medium"
            >
              {{ data.title || `Actividad #${data.id}` }}
            </router-link>
          </template>
        </Column>
        <Column
          header="Inicio"
          class="hidden md:table-cell w-32"
          header-class="hidden md:table-cell"
        >
          <template #body="{ data }">
            <UtcFormatted :value="data.dates?.start" variant="date" />
          </template>
        </Column>
        <Column
          header="Fin"
          class="hidden md:table-cell w-32"
          header-class="hidden md:table-cell"
        >
          <template #body="{ data }">
            <UtcFormatted :value="data.dates?.end" variant="date" />
          </template>
        </Column>
        <Column header="Estado" class="w-36">
          <template #body="{ data }">
            <Tag
              v-if="data.status"
              :value="data.status.name || data.status.label || '—'"
              severity="secondary"
            />
            <span v-else>—</span>
          </template>
        </Column>
        <Column header="Tipo" class="w-44">
          <template #body="{ data }">
            {{ data.type?.label || data.type?.name || '—' }}
          </template>
        </Column>
        <Column v-if="canDelete" class="w-14 text-right">
          <template #header>
            <span class="sr-only">Eliminar</span>
          </template>
          <template #body="{ data }">
            <Button
              v-tooltip.top="'Eliminar actividad'"
              icon="pi pi-trash"
              rounded
              text
              severity="danger"
              size="small"
              :aria-label="`Eliminar ${data.title || 'actividad'}`"
              @click="confirmDelete(data)"
            />
          </template>
        </Column>
      </DataTable>
    </template>
  </Card>
</template>

<script setup>
import { computed, inject } from 'vue'
import { useRouter } from 'vue-router'
import { useAbility } from '@casl/vue'
import useActivities from '@/composables/activities'

const { can } = useAbility()
const canCreate = computed(() => can('activity-create'))
const canDelete = computed(() => can('activity-delete'))

const router = useRouter()
const groupIdRef = inject('subjectGroupId')
const listings = inject('subjectGroupListings')
const reloadActivities = inject('reloadSubjectGroupActivities', null)

const { deleteActivity } = useActivities()

const groupId = computed(() => groupIdRef?.value ?? '')

const swal = inject('$swal', null)

function goCreate() {
  router.push({
    name: 'app.subject-group.activity.create',
    params: { id: String(groupId.value) },
  })
}

async function confirmDelete(row) {
  if (!row?.id) return
  const title = row.title || `Actividad #${row.id}`

  const run = async () => {
    try {
      await deleteActivity(row.id)
      if (typeof reloadActivities === 'function') {
        await reloadActivities()
      }
    } catch {
      /* toast en composable */
    }
  }

  if (!swal) {
    if (typeof window !== 'undefined' && window.confirm(`¿Eliminar «${title}»?`)) {
      await run()
    }
    return
  }

  swal({
    icon: 'warning',
    title: '¿Eliminar actividad?',
    text: `Se eliminará «${title}» de forma permanente.`,
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#ef4444',
  }).then((result) => {
    if (result.isConfirmed) run()
  })
}
</script>
