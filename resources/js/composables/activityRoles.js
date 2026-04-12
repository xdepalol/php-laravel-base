import { ref } from 'vue'
import * as yup from 'yup'
import axios from 'axios'
import { useToast } from './useToast'
import { useValidation } from './useValidation'

const API = '/api/activity-roles'

export default function useActivityRoles() {
  const activityRoles = ref([])
  const isLoading = ref(false)
  const toast = useToast()

  const initialActivityRole = {
    id: null,
    activity_role_type_id: null,
    name: '',
    description: null,
    is_mandatory: false,
    max_per_team: 1,
    position: 1
  }

  const activityRole = ref({ ...initialActivityRole })

  const {
    errors,
    validate,
    clearErrors,
    hasError,
    getError
  } = useValidation()

  const activityRoleSchema = yup.object({
    activity_role_type_id: yup
      .number()
      .required('El tipo de rol es obligatorio')
      .integer()
      .positive(),
    name: yup.string().trim().required('El nombre es obligatorio').max(255),
    description: yup.string().nullable(),
    is_mandatory: yup.boolean(),
    max_per_team: yup
      .number()
      .required()
      .integer()
      .min(1, 'Debe ser al menos 1'),
    position: yup
      .number()
      .required()
      .integer()
      .min(1, 'La posición debe ser al menos 1')
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

  const resetActivityRole = () => {
    activityRole.value = { ...initialActivityRole }
    clearErrors()
  }

  const setActivityRole = (data = {}) => {
    activityRole.value = {
      id: data.id ?? null,
      activity_role_type_id: data.activity_role_type_id ?? null,
      name: data.name ?? '',
      description: data.description ?? null,
      is_mandatory: !!data.is_mandatory,
      max_per_team: data.max_per_team ?? 1,
      position: data.position ?? 1
    }
    clearErrors()
  }

  const upsertActivityRoleRecord = (record) => {
    if (!record?.id) return
    activityRoles.value = [record, ...activityRoles.value.filter((r) => r.id !== record.id)]
  }

  const unwrap = (response) => response.data?.data ?? response.data

  const buildPayload = (data) => ({
    activity_role_type_id: data.activity_role_type_id,
    name: data.name,
    description: data.description || null,
    is_mandatory: !!data.is_mandatory,
    max_per_team: data.max_per_team,
    position: data.position
  })

  const getActivityRoles = async () => {
    try {
      const response = await withLoading(() => axios.get(API))
      const data = unwrap(response)
      activityRoles.value = Array.isArray(data) ? data : []
      return response
    } catch (error) {
      toast.error('Error', 'No se pudieron cargar los roles de actividad')
      throw error
    }
  }

  const getActivityRole = async (id) => {
    if (!id) return null
    try {
      const response = await withLoading(() => axios.get(`${API}/${id}`))
      const data = unwrap(response)
      setActivityRole(data)
      return data
    } catch (error) {
      toast.error('Error', 'No se pudo obtener el rol de actividad')
      throw error
    }
  }

  const createActivityRole = async (payload) => {
    const data = payload ?? activityRole.value
    const { isValid } = validate(activityRoleSchema, data)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }

    try {
      const response = await withLoading(() => axios.post(API, buildPayload(data)))
      const created = unwrap(response)
      toast.crud.created('Rol de actividad')
      return created
    } catch (error) {
      toast.error('Error', 'No se pudo crear el rol de actividad')
      throw error
    }
  }

  const updateActivityRole = async (payload) => {
    const data = payload ?? activityRole.value
    const { isValid } = validate(activityRoleSchema, data)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }

    try {
      const response = await withLoading(() =>
        axios.put(`${API}/${data.id}`, buildPayload(data))
      )
      const updated = unwrap(response)
      toast.crud.updated('Rol de actividad')
      return updated
    } catch (error) {
      toast.error('Error', 'No se pudo actualizar el rol de actividad')
      throw error
    }
  }

  const deleteActivityRole = async (id) => {
    try {
      await withLoading(() => axios.delete(`${API}/${id}`))
      activityRoles.value = activityRoles.value.filter((r) => r.id !== id)
      toast.crud.deleted('Rol de actividad')
    } catch (error) {
      const message =
        error?.response?.data?.message || 'No se pudo eliminar el rol de actividad'
      toast.error('Error', message)
      throw error
    }
  }

  return {
    activityRoles,
    activityRole,
    isLoading,
    errors,
    hasError,
    getError,
    resetActivityRole,
    setActivityRole,
    upsertActivityRoleRecord,
    getActivityRoles,
    getActivityRole,
    createActivityRole,
    updateActivityRole,
    deleteActivityRole
  }
}
