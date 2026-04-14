<template>
  <Card>
    <template #title>
      <div class="flex flex-wrap items-center justify-between gap-3 w-full">
        <span>Equipos</span>
        <Button
          v-if="can('team-create')"
          label="Nuevo equipo"
          icon="pi pi-plus"
          size="small"
          @click="openCreateDialog"
        />
      </div>
    </template>
    <template #content>
      <p class="text-sm text-slate-600 mb-4">
        Abre un equipo para ver fases, backlog y tareas (si aplica) y las entregas del equipo.
      </p>
      <DataTable :value="teams" :loading="isLoading" data-key="id" striped-rows class="text-sm">
        <template #empty>
          <span class="text-slate-500">No hay equipos en esta actividad.</span>
        </template>
        <Column header="Nombre">
          <template #body="{ data }">
            <router-link
              :to="teamWorkspaceTo(data.id)"
              class="text-blue-700 hover:underline font-medium"
            >
              {{ data.name || `Equipo #${data.id}` }}
            </router-link>
          </template>
        </Column>
        <Column header="Estudiantes" class="w-32 text-center">
          <template #body="{ data }">
            {{ memberCount(data) }}
          </template>
        </Column>
        <Column header="Entregas enviadas" class="w-40 text-center">
          <template #body="{ data }">
            {{ data.submissions_delivered_count ?? 0 }}
          </template>
        </Column>
      </DataTable>
    </template>
  </Card>

  <Dialog
    v-model:visible="createDialogOpen"
    modal
    header="Nuevo equipo"
    :style="{ width: '420px' }"
    @hide="onDialogHide"
  >
    <div class="flex flex-col gap-3">
      <div>
        <label for="team-name" class="text-sm font-medium text-slate-700 block mb-1">Nombre</label>
        <InputText
          id="team-name"
          v-model="newTeamName"
          class="w-full"
          :class="{ 'p-invalid': hasError('name') }"
          placeholder="Nombre del equipo"
          @keyup.enter="submitNewTeam"
        />
        <small v-if="hasError('name')" class="text-red-500">{{ getError('name') }}</small>
      </div>
    </div>
    <template #footer>
      <Button label="Cancelar" severity="secondary" text @click="createDialogOpen = false" />
      <Button
        label="Crear"
        icon="pi pi-check"
        :loading="isLoading"
        @click="submitNewTeam"
      />
    </template>
  </Dialog>
</template>

<script setup>
import { computed, inject, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAbility } from '@casl/vue'
import useActivityTeams from '@/composables/activityTeams'

const { can } = useAbility()
const route = useRoute()
const activityId = inject('activityId')
const { teams, isLoading, getTeams, createTeam, hasError, getError, upsertTeamRecord } =
  useActivityTeams()

const createDialogOpen = ref(false)
const newTeamName = ref('')

const tabQuery = computed(() => {
  const raw = route.query.fromSubjectGroup
  if (raw == null || raw === '') return {}
  return { fromSubjectGroup: String(raw) }
})

function teamWorkspaceTo(teamId) {
  return {
    name: 'app.activity.team.overview',
    params: {
      activityId: activityId?.value,
      teamId,
    },
    query: { ...tabQuery.value },
  }
}

function memberCount(row) {
  if (row.students_count != null) return row.students_count
  const s = row.students
  return Array.isArray(s) ? s.length : 0
}

function openCreateDialog() {
  newTeamName.value = ''
  createDialogOpen.value = true
}

function onDialogHide() {
  newTeamName.value = ''
}

async function submitNewTeam() {
  const id = activityId?.value
  if (!id) return
  try {
    const created = await createTeam(id, { name: newTeamName.value })
    if (created?.id) upsertTeamRecord(created)
    else await getTeams(id)
    createDialogOpen.value = false
  } catch {
    /* validación / toast en composable */
  }
}

watch(
  () => activityId?.value,
  async (id) => {
    if (!id) return
    try {
      await getTeams(id)
    } catch {
      /* toast en composable */
    }
  },
  { immediate: true }
)
</script>
