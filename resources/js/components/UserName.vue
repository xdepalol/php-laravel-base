<template>
  <div v-if="user" class="user">
    <span class="user__name">{{ user.name }}</span>
    <span class="user__surname">{{ user.surname1 }}</span>
  </div>
  <span v-else class="user user--empty">—</span>
</template>

<script setup>
defineProps({
  user: {
    type: Object,
    required: false,
    default: null,
  },
})
</script>