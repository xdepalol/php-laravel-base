<template>
  <Dialog
    v-model:visible="dialogVisible"
    modal
    :header="dialogTitle"
    :style="{ width: 'min(96vw, 56rem)' }"
    :breakpoints="{ '960px': '95vw' }"
  >
    <div v-if="loadError" class="text-sm text-red-600 py-2">
      No se pudieron cargar los datos del equipo.
    </div>
    <div v-else-if="innerLoading" class="flex justify-center py-12 text-blue-600">
      <i class="pi pi-spin pi-spinner text-2xl" aria-hidden="true" />
    </div>
    <template v-else>
      <p class="text-sm text-slate-600 mb-3">
        <span class="font-medium text-slate-800">Disponibles:</span>
        estudiantes matriculados en la actividad que no están en ningún equipo.
        <span class="font-medium text-slate-800">Equipo:</span>
        miembros actuales. El rol se asigna o edita desde el resumen del equipo.
      </p>
      <PickList
        v-model="membersPick"
        data-key="student_id"
        breakpoint="992px"
        striped
        :show-source-controls="false"
        :show-target-controls="false"
        scroll-height="20rem"
        :pt="{
          root: { class: 'picklist' },
        }"
      >
        <template #sourceheader>Disponibles</template>
        <template #targetheader>En el equipo</template>
        <template #option="{ option }">
          <span>{{ displayName(option) }}</span>
        </template>
      </PickList>
    </template>
    <template #footer>
      <Button label="Cancelar" severity="secondary" text @click="close" />
      <Button
        label="Guardar"
        icon="pi pi-check"
        :loading="saveLoading"
        :disabled="innerLoading"
        @click="submit"
      />
    </template>
  </Dialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import PickList from 'primevue/picklist'
import useActivityTeams from '@/composables/activityTeams'
import { formatStudentDisplayName } from '@/utils/studentDisplayName'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  activityId: { type: Number, default: null },
  team: { type: Object, default: null },
})

const emit = defineEmits(['update:modelValue', 'saved'])

const { syncTeamStudents, getStudentsAvailableForTeams, getTeamStudentsList } =
  useActivityTeams()

const dialogVisible = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

const innerLoading = ref(false)
const saveLoading = ref(false)
const loadError = ref(false)
const membersPick = ref([[], []])

function displayName(option) {
  const st = option?.student ?? (option?.user ? { user: option.user, student_number: option.student_number } : null)
  return formatStudentDisplayName(st, option?.student_id)
}

function normalizeAvailableStudent(s) {
  return {
    student_id: s.user_id,
    student_number: s.student_number,
    user: s.user,
    student: { user: s.user, student_number: s.student_number },
  }
}

const dialogTitle = computed(() => {
  const n = props.team?.name || `Equipo #${props.team?.id ?? ''}`
  return `Miembros — ${n}`
})

function close() {
  emit('update:modelValue', false)
}

async function loadData() {
  const aid = props.activityId
  const team = props.team
  if (!aid || !team?.id) return
  innerLoading.value = true
  loadError.value = false
  try {
    const [rawAvail, assigned] = await Promise.all([
      getStudentsAvailableForTeams(aid),
      getTeamStudentsList(aid, team.id),
    ])

    membersPick.value = [rawAvail.map(normalizeAvailableStudent), assigned]
  } catch {
    loadError.value = true
    membersPick.value = [[], []]
  } finally {
    innerLoading.value = false
  }
}

async function submit() {
  const aid = props.activityId
  const team = props.team
  if (!aid || !team?.id) return

  const target = membersPick.value[1] ?? []
  const rows = target.map((m) => ({
    student_id: m.student_id,
    activity_role_id: m.activity_role_id ?? null,
  }))

  saveLoading.value = true
  try {
    await syncTeamStudents(aid, team.id, rows)
    emit('saved')
    close()
  } catch {
    /* toast en composable */
  } finally {
    saveLoading.value = false
  }
}

watch(
  () => props.modelValue,
  (open) => {
    if (open) loadData()
    else {
      membersPick.value = [[], []]
      loadError.value = false
    }
  }
)
</script>
