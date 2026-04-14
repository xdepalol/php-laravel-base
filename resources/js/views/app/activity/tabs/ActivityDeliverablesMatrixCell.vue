<template>
  <div class="flex flex-col items-center gap-0.5 py-0.5">
    <Tag
      v-if="cell?.statusLabel"
      :value="cell.statusLabel"
      severity="secondary"
      class="text-[0.7rem] px-1"
    />
    <span v-else class="text-slate-400">—</span>
    <router-link
      v-if="cell?.submissionId"
      :to="detailTo"
      class="text-[0.7rem] text-blue-700 hover:underline"
    >
      Ver
    </router-link>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  cell: { type: Object, default: null },
  activityId: { type: Number, required: true },
  deliverableId: { type: Number, required: true },
  tabQuery: { type: Object, default: () => ({}) },
})

const detailTo = computed(() => ({
  name: 'app.activity.submission.detail',
  params: {
    activityId: props.activityId,
    deliverableId: props.deliverableId,
    submissionId: props.cell?.submissionId,
  },
  query: { ...props.tabQuery },
}))
</script>
