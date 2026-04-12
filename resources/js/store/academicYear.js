import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import useAcademicYears from '../composables/academicYears'

export const useAcademicYearStore = defineStore(
  'academicYear',
  () => {
    const academicYearsApi = useAcademicYears()

    const academicYears = ref([])
    const selectedAcademicYearId = ref(null)
    const loaded = ref(false)

    const loading = computed(() => academicYearsApi.isLoading.value)

    const activeAcademicYear = computed(() => {
      const list = academicYears.value
      return list.find((y) => y.is_active) ?? null
    })

    const currentAcademicYear = computed(() => {
      const list = academicYears.value
      if (!list.length) return null
      const id = selectedAcademicYearId.value
      if (id == null) return activeAcademicYear.value
      return list.find((y) => y.id === id) ?? activeAcademicYear.value
    })

    const currentAcademicYearLabel = computed(() => {
      const y = currentAcademicYear.value
      if (!y) return ''
      return y.year_code || ''
    })

    async function fetchAll() {
      await academicYearsApi.getAcademicYears()
      academicYears.value = [...academicYearsApi.academicYears.value]
      loaded.value = true
    }

    /**
     * @param {boolean} canSwitch - usuario con permiso academicyear-switch (admin / teacher)
     */
    function applySelectionRules(canSwitch) {
      const list = academicYears.value
      const active = list.find((y) => y.is_active) ?? null

      if (!canSwitch) {
        selectedAcademicYearId.value = active?.id ?? null
        return
      }

      const sid = selectedAcademicYearId.value
      const stillValid = sid != null && list.some((y) => y.id === sid)
      if (!stillValid) {
        selectedAcademicYearId.value = active?.id ?? list[0]?.id ?? null
      }
    }

    function setWorkingYear(id) {
      const list = academicYears.value
      if (!list.some((y) => y.id === id)) return
      selectedAcademicYearId.value = id
    }

    function $reset() {
      academicYears.value = []
      academicYearsApi.academicYears.value = []
      selectedAcademicYearId.value = null
      loaded.value = false
    }

    return {
      academicYears,
      selectedAcademicYearId,
      loaded,
      loading,
      activeAcademicYear,
      currentAcademicYear,
      currentAcademicYearLabel,
      fetchAll,
      applySelectionRules,
      setWorkingYear,
      $reset
    }
  },
  {
    persist: {
      pick: ['selectedAcademicYearId']
    }
  }
)
