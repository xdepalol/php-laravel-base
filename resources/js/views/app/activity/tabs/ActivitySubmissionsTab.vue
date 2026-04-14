<template>
  <Card>
    <template #title>Entregas</template>
    <template #subtitle>
      <template v-if="isStudentOnlyView">
        Resumen de las entregas de tu equipo y las tuyas personales. En cada celda, «Ver» abre el detalle de la entrega.
      </template>
      <template v-else>
        Vista matricial: filas = equipos o estudiantes, columnas = entregables (código corto). Cada celda refleja la última entrega. El detalle de cada entregable está en su ficha de edición.
      </template>
    </template>
    <template #content>
      <ActivityDeliverablesMatrix
        v-if="can('submission-list')"
        :deliverables="deliverables"
        :student-matrix-user-id="studentMatrixUserId"
        :student-matrix-row-label="studentMatrixRowLabel"
      />
      <p v-else class="text-sm text-slate-500">No tienes permiso para ver las entregas.</p>
    </template>
  </Card>
</template>

<script setup>
import { computed, inject, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAbility } from '@casl/vue'
import useActivityDeliverables from '@/composables/activityDeliverables'
import { useActivityViewerRole } from '@/composables/useActivityViewerRole'
import ActivityDeliverablesMatrix from './ActivityDeliverablesMatrix.vue'

const { can } = useAbility()
const { isStudentOnlyView, viewerUserId, viewerDisplayName } = useActivityViewerRole()

const studentMatrixUserId = computed(() =>
  isStudentOnlyView.value && viewerUserId.value != null ? Number(viewerUserId.value) : null
)

const studentMatrixRowLabel = computed(() => viewerDisplayName.value || '')
const router = useRouter()
const route = useRoute()

const activityId = inject('activityId')
const { deliverables, getDeliverables } = useActivityDeliverables()

const tabQuery = computed(() => {
  const raw = route.query.fromSubjectGroup
  if (raw == null || raw === '') return {}
  return { fromSubjectGroup: String(raw) }
})

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

onMounted(() => {
  if (!can('submission-list')) {
    router.replace({
      name: 'app.activity.overview',
      params: { activityId: activityId?.value },
      query: { ...tabQuery.value },
    })
  }
})
</script>
