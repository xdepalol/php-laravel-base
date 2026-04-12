import { ref } from 'vue'
import * as yup from 'yup'
import axios from 'axios'
import { useToast } from './useToast'
import { useValidation } from './useValidation'

const API = '/api/activity-role-types'

export default function useActivityRoleTypes() {
  const activityRoleTypes = ref([])
  const isLoading = ref(false)
  const toast = useToast()

  const initialActivityRoleType = {
    id: null,
    name: '',
    description: null
  }

  const activityRoleType = ref({ ...initialActivityRoleType })

  const {
    errors,
    validate,
    clearErrors,
    hasError,
    getError
  } = useValidation()

  const activityRoleTypeSchema = yup.object({
    name: yup.string().trim().required('El nombre es obligatorio').max(255),
    description: yup.string().nullable()
  })

  const withLoading = async (fn) => {
    if (isLoading.value) throw new Error('Operación en curso')
    isLoading.value = true
    try {
      return await fn()
    } finally {
      isLoading.value = false
    }
  }

  const resetActivityRoleType = () => {
    activityRoleType.value = { ...initialActivityRoleType }
    clearErrors()
  }

  const setActivityRoleType = (data = {}) => {
    activityRoleType.value = {
      id: data.id ?? null,
      name: data.name ?? '',
      description: data.description ?? null
    }
    clearErrors()
  }

  const upsertActivityRoleTypeRecord = (record) => {
    if (!record?.id) return
    activityRoleTypes.value = [
      record,
      ...activityRoleTypes.value.filter((t) => t.id !== record.id)
    ]
  }

  const unwrap = (response) => response.data?.data ?? response.data

  const getActivityRoleTypes = async () => {
    try {
      const response = await withLoading(() => axios.get(API))
      const data = unwrap(response)
      activityRoleTypes.value = Array.isArray(data) ? data : []
      return response
    } catch (error) {
      toast.error('Error', 'No se pudieron cargar los tipos de rol de actividad')
      throw error
    }
  }

  const getActivityRoleType = async (id) => {
    if (!id) return null
    try {
      const response = await withLoading(() => axios.get(`${API}/${id}`))
      const data = unwrap(response)
      setActivityRoleType(data)
      return data
    } catch (error) {
      toast.error('Error', 'No se pudo obtener el tipo de rol')
      throw error
    }
  }

  const createActivityRoleType = async (payload) => {
    const data = payload ?? activityRoleType.value
    const { isValid } = validate(activityRoleTypeSchema, data)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }

    try {
      const response = await withLoading(() =>
        axios.post(API, {
          name: data.name,
          description: data.description || null
        })
      )
      const created = unwrap(response)
      toast.crud.created('Tipo de rol de actividad')
      return created
    } catch (error) {
      toast.error('Error', 'No se pudo crear el tipo de rol')
      throw error
    }
  }

  const updateActivityRoleType = async (payload) => {
    const data = payload ?? activityRoleType.value
    const { isValid } = validate(activityRoleTypeSchema, data)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }

    try {
      const response = await withLoading(() =>
        axios.put(`${API}/${data.id}`, {
          name: data.name,
          description: data.description || null
        })
      )
      const updated = unwrap(response)
      toast.crud.updated('Tipo de rol de actividad')
      return updated
    } catch (error) {
      toast.error('Error', 'No se pudo actualizar el tipo de rol')
      throw error
    }
  }

  const deleteActivityRoleType = async (id) => {
    try {
      await withLoading(() => axios.delete(`${API}/${id}`))
      activityRoleTypes.value = activityRoleTypes.value.filter((t) => t.id !== id)
      toast.crud.deleted('Tipo de rol de actividad')
    } catch (error) {
      const message =
        error?.response?.data?.message || 'No se pudo eliminar el tipo de rol'
      toast.error('Error', message)
      throw error
    }
  }

  return {
    activityRoleTypes,
    activityRoleType,
    isLoading,
    errors,
    hasError,
    getError,
    resetActivityRoleType,
    setActivityRoleType,
    upsertActivityRoleTypeRecord,
    getActivityRoleTypes,
    getActivityRoleType,
    createActivityRoleType,
    updateActivityRoleType,
    deleteActivityRoleType
  }
}
