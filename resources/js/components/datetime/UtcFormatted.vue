<template>
  <span>{{ text }}</span>
</template>

<script setup>
import { computed } from 'vue'
import { formatUtcIso } from '@/utils/datetime'

const props = defineProps({
  /** ISO 8601 UTC, fecha Y-m-d, o null */
  value: { type: [String, Number, Date, Object], default: null },
  /** date = solo fecha; datetime = fecha y hora local; time = solo hora (requiere instante con hora) */
  variant: {
    type: String,
    default: 'datetime',
    validator: (v) => ['date', 'datetime', 'time'].includes(v),
  },
  empty: { type: String, default: '—' },
})

const text = computed(() => formatUtcIso(props.value, props.variant) ?? props.empty)
</script>
