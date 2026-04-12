import { ref } from 'vue'
import * as yup from 'yup'
import axios from 'axios'
import { useToast } from './useToast'
import { useValidation } from './useValidation'

const indexUrl = (activityId) => `/api/activities/${activityId}/subject-groups`

const syncSchema = yup.object({
  subject_groups: yup.array().of(yup.number().integer().positive()).required()
})

export default function useActivitySubjectGroups() {
  const subjectGroups = ref([])
  const isLoading = ref(false)
  const toast = useToast()
  const { validate, clearErrors, errors, hasError, getError } = useValidation()

  const withLoading = async (fn) => {
    if (isLoading.value) throw new Error('Operación en curso')
    isLoading.value = true
    try {
      return await fn()
    } finally {
      isLoading.value = false
    }
  }

  const unwrap = (response) => response.data?.data ?? response.data

  const getActivitySubjectGroups = async (activityId) => {
    if (!activityId) throw new Error('activityId requerido')
    try {
      const response = await withLoading(() => axios.get(indexUrl(activityId)))
      const data = unwrap(response)
      subjectGroups.value = Array.isArray(data) ? data : []
      return response
    } catch (error) {
      toast.error('Error', 'No se pudieron cargar los grupos vinculados a la actividad')
      throw error
    }
  }

  /** @param {number} activityId
   * @param {number[]} subjectGroupIds ids de subject_groups a sincronizar en el pivot */
  const syncActivitySubjectGroups = async (activityId, subjectGroupIds) => {
    if (!activityId) throw new Error('activityId requerido')
    const payload = { subject_groups: subjectGroupIds ?? [] }
    const { isValid } = validate(syncSchema, payload)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los datos de grupos.')
      throw new Error('Validación')
    }
    clearErrors()
    try {
      const response = await withLoading(() =>
        axios.put(indexUrl(activityId), payload)
      )
      const data = unwrap(response)
      subjectGroups.value = Array.isArray(data) ? data : []
      toast.crud.saved('Vínculos de grupos')
      return response
    } catch (error) {
      toast.error('Error', 'No se pudo sincronizar los grupos de la actividad')
      throw error
    }
  }

  return {
    subjectGroups,
    isLoading,
    errors,
    hasError,
    getError,
    getActivitySubjectGroups,
    syncActivitySubjectGroups
  }
}
