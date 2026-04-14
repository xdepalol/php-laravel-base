<template>
  <div class="space-y-6">
    <Card>
      <template #title>Resumen del equipo</template>
      <template #content>
        <p class="text-sm text-slate-600 mb-6">
          Trabajo del equipo en el contexto de esta actividad: fases compartidas, backlog y tareas (si la
          actividad tiene backlog), y entregas asociadas a este equipo.
        </p>

        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
          <h3 class="text-sm font-semibold text-slate-800">Miembros del equipo</h3>
          <div class="flex flex-wrap gap-2">
            <Button
              v-if="can('team-edit')"
              label="Añadir o quitar miembros"
              icon="pi pi-users"
              size="small"
              outlined
              @click="membersDialogOpen = true"
            />
            <Button
              v-if="can('team-delete') && memberRows.length === 0"
              label="Eliminar equipo"
              icon="pi pi-trash"
              severity="danger"
              size="small"
              outlined
              @click="confirmDeleteTeam"
            />
          </div>
        </div>

        <DataTable :value="memberRows" data-key="student_id" striped-rows size="small" class="text-sm">
          <template #empty>
            <span class="text-slate-500">No hay estudiantes asignados a este equipo.</span>
          </template>
          <Column header="Nombre">
            <template #body="{ data }">
              <span class="font-medium text-slate-800">{{ data.name }}</span>
            </template>
          </Column>
          <Column v-if="showRoleColumn" header="Rol" class="w-48">
            <template #body="{ data }">
              {{ data.activity_role?.name || '—' }}
            </template>
          </Column>
          <Column v-if="can('team-edit') && showRoleColumn" class="w-16">
            <template #header>
              <span class="sr-only">Editar rol</span>
            </template>
            <template #body="{ data }">
              <Button
                v-tooltip.top="'Cambiar rol'"
                icon="pi pi-pencil"
                rounded
                text
                severity="secondary"
                size="small"
                :aria-label="`Editar rol de ${data.name}`"
                @click="openRoleDialog(data)"
              />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <TeamMembersPickDialog
      v-model="membersDialogOpen"
      :activity-id="numericActivityId"
      :team="teamSummary"
      @saved="onMembersDialogSaved"
    />

    <Dialog
      v-model:visible="roleDialogVisible"
      modal
      header="Rol en el equipo"
      :style="{ width: '420px' }"
      @hide="roleEditRow = null"
    >
      <div v-if="roleEditRow" class="flex flex-col gap-4">
        <p class="text-sm text-slate-700">
          <span class="font-medium">{{ roleEditRow.name }}</span>
        </p>
        <div>
          <label for="member-role-select" class="text-sm font-medium text-slate-700 block mb-1">Rol</label>
          <Select
            id="member-role-select"
            v-model="roleEditSelectedId"
            :options="activityRoles"
            option-label="name"
            option-value="id"
            placeholder="Sin rol (asignar después)"
            show-clear
            class="w-full"
          />
        </div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="roleDialogVisible = false" />
        <Button
          label="Guardar"
          icon="pi pi-check"
          :loading="roleSaveLoading"
          @click="saveMemberRole"
        />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { computed, inject, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAbility } from '@casl/vue'
import useActivityTeams from '@/composables/activityTeams'
import TeamMembersPickDialog from './TeamMembersPickDialog.vue'

const { can } = useAbility()
const route = useRoute()
const router = useRouter()
const swal = inject('$swal')

const teamRef = inject('team')
const activityId = inject('activityId')
const teamId = inject('teamId')
const reloadTeam = inject('reloadTeam', null)

const {
  getTeamMemberRoles,
  getTeamStudentsList,
  syncTeamStudents,
  deleteTeam,
} = useActivityTeams()

const tabQuery = computed(() => {
  const raw = route.query.fromSubjectGroup
  if (raw == null || raw === '') return {}
  return { fromSubjectGroup: String(raw) }
})

const membersDialogOpen = ref(false)
const activityRoles = ref([])
const roleDialogVisible = ref(false)
const roleEditRow = ref(null)
const roleEditSelectedId = ref(null)
const roleSaveLoading = ref(false)

const numericActivityId = computed(() => Number(activityId?.value) || 0)

const teamSummary = computed(() => {
  const t = teamRef?.value
  if (!t?.id) return null
  return { id: t.id, name: t.name }
})

const memberRows = computed(() => {
  const students = teamRef?.value?.students
  if (!Array.isArray(students)) return []
  return students.map((row) => {
    const inner = row.student ?? row
    const u = inner.user ?? row.user
    const parts = u ? [u.name, u.surname1, u.surname2].filter(Boolean) : []
    const sid = row.student_id ?? inner.user_id
    const name = parts.length ? parts.join(' ') : u?.email || `Estudiante #${sid}`
    return {
      student_id: sid,
      name,
      activity_role_id: row.activity_role_id,
      activity_role: row.activity_role,
      raw: row,
    }
  })
})

const showRoleColumn = computed(() => activityRoles.value.length > 0)

async function loadActivityRoles() {
  const aid = numericActivityId.value
  if (!aid) {
    activityRoles.value = []
    return
  }
  try {
    activityRoles.value = await getTeamMemberRoles(aid)
  } catch {
    activityRoles.value = []
  }
}

watch(
  () => teamRef?.value?.id,
  () => {
    loadActivityRoles()
  },
  { immediate: true }
)

function openRoleDialog(data) {
  roleEditRow.value = data
  roleEditSelectedId.value = data.activity_role_id ?? null
  roleDialogVisible.value = true
}

async function saveMemberRole() {
  const aid = numericActivityId.value
  const tid = teamId?.value
  const edit = roleEditRow.value
  if (!aid || !tid || !edit) return

  roleSaveLoading.value = true
  try {
    const list = await getTeamStudentsList(aid, tid)
    const rows = list.map((m) => ({
      student_id: m.student_id,
      activity_role_id:
        Number(m.student_id) === Number(edit.student_id)
          ? roleEditSelectedId.value ?? null
          : m.activity_role_id,
    }))
    await syncTeamStudents(aid, tid, rows)
    if (typeof reloadTeam === 'function') {
      await reloadTeam()
    }
    roleDialogVisible.value = false
    roleEditRow.value = null
  } catch {
    /* toast en composable */
  } finally {
    roleSaveLoading.value = false
  }
}

async function onMembersDialogSaved() {
  if (typeof reloadTeam === 'function') {
    await reloadTeam()
  }
  await loadActivityRoles()
}

function confirmDeleteTeam() {
  const aid = numericActivityId.value
  const tid = teamId?.value
  const t = teamRef?.value
  if (!aid || !tid || memberRows.value.length > 0 || !t?.id) return

  const run = async () => {
    await deleteTeam(aid, tid)
    router.push({
      name: 'app.activity.teams',
      params: { activityId: aid },
      query: { ...tabQuery.value },
    })
  }

  if (!swal) {
    run()
    return
  }

  swal({
    icon: 'warning',
    title: '¿Eliminar equipo?',
    text: `Se eliminará «${t.name || 'este equipo'}» de forma permanente.`,
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#ef4444',
  }).then((result) => {
    if (result.isConfirmed) run()
  })
}
</script>
