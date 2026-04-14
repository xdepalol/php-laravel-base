import { ref } from 'vue'
import * as yup from 'yup'
import axios from 'axios'
import { useToast } from './useToast'
import { useValidation } from './useValidation'
import { toDateInputValue } from '@/utils/dateInput'

const baseUrl = (activityId) => `/api/activities/${activityId}/deliverables`

const DELIVERABLE_STATUSES = [0, 1, 2]

function normalizeDeliverable(data) {
  if (!data) return null
  return {
    id: data.id ?? null,
    title: data.title ?? '',
    description: data.description ?? null,
    due_date: toDateInputValue(data.due_date),
    status: data.status?.value ?? data.status ?? 0,
    is_group_deliverable: !!data.is_group_deliverable
  }
}

export default function useActivityDeliverables() {
  const deliverables = ref([])
  const isLoading = ref(false)
  const toast = useToast()

  const initialDeliverable = {
    id: null,
    title: '',
    description: null,
    due_date: null,
    status: 0,
    is_group_deliverable: false
  }

  const deliverable = ref({ ...initialDeliverable })

  const { errors, validate, clearErrors, hasError, getError } = useValidation()

  const deliverableSchema = yup.object({
    title: yup.string().trim().required('El título es obligatorio').max(100),
    description: yup.string().nullable(),
    due_date: yup.string().nullable(),
    status: yup.number().required().integer().oneOf(DELIVERABLE_STATUSES),
    is_group_deliverable: yup.boolean()
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

  const resetDeliverable = () => {
    deliverable.value = { ...initialDeliverable }
    clearErrors()
  }

  const setDeliverable = (data = {}) => {
    const n = normalizeDeliverable(data)
    deliverable.value = n
      ? {
          id: n.id,
          title: n.title,
          description: n.description,
          due_date: n.due_date,
          status: n.status,
          is_group_deliverable: n.is_group_deliverable
        }
      : { ...initialDeliverable }
    clearErrors()
  }

  const upsertDeliverableRecord = (record) => {
    if (!record?.id) return
    deliverables.value = [
      record,
      ...deliverables.value.filter((d) => d.id !== record.id)
    ]
  }

  const unwrap = (response) => response.data?.data ?? response.data

  const buildPayload = (data) => ({
    title: data.title,
    description: data.description || null,
    due_date: data.due_date || null,
    status: data.status,
    is_group_deliverable: !!data.is_group_deliverable
  })

  const getDeliverables = async (activityId) => {
    if (!activityId) throw new Error('activityId requerido')
    try {
      const response = await withLoading(() => axios.get(baseUrl(activityId)))
      const data = unwrap(response)
      deliverables.value = Array.isArray(data) ? data : []
      return response
    } catch (error) {
      toast.error('Error', 'No se pudieron cargar los entregables')
      throw error
    }
  }

  const getDeliverable = async (activityId, deliverableId) => {
    if (!activityId || !deliverableId) return null
    try {
      const response = await withLoading(() =>
        axios.get(`${baseUrl(activityId)}/${deliverableId}`)
      )
      const raw = unwrap(response)
      setDeliverable(raw)
      return raw
    } catch (error) {
      toast.error('Error', 'No se pudo obtener el entregable')
      throw error
    }
  }

  const createDeliverable = async (activityId, payload) => {
    if (!activityId) throw new Error('activityId requerido')
    const data = payload ?? deliverable.value
    const { isValid } = validate(deliverableSchema, data)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }
    try {
      const response = await withLoading(() =>
        axios.post(baseUrl(activityId), buildPayload(data))
      )
      const created = unwrap(response)
      toast.crud.created('Entregable')
      return created
    } catch (error) {
      toast.error('Error', 'No se pudo crear el entregable')
      throw error
    }
  }

  const updateDeliverable = async (activityId, deliverableId, payload) => {
    if (!activityId || !deliverableId) throw new Error('IDs requeridos')
    const data = { ...(payload ?? deliverable.value) }
    const { isValid } = validate(deliverableSchema, data)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }
    try {
      const response = await withLoading(() =>
        axios.put(`${baseUrl(activityId)}/${deliverableId}`, buildPayload(data))
      )
      const updated = unwrap(response)
      toast.crud.updated('Entregable')
      return updated
    } catch (error) {
      toast.error('Error', 'No se pudo actualizar el entregable')
      throw error
    }
  }

  const deleteDeliverable = async (activityId, deliverableId) => {
    if (!activityId || !deliverableId) throw new Error('IDs requeridos')
    try {
      await withLoading(() =>
        axios.delete(`${baseUrl(activityId)}/${deliverableId}`)
      )
      deliverables.value = deliverables.value.filter((d) => d.id !== deliverableId)
      toast.crud.deleted('Entregable')
    } catch (error) {
      toast.error('Error', 'No se pudo eliminar el entregable')
      throw error
    }
  }

  return {
    deliverables,
    deliverable,
    isLoading,
    errors,
    hasError,
    getError,
    resetDeliverable,
    setDeliverable,
    upsertDeliverableRecord,
    getDeliverables,
    getDeliverable,
    createDeliverable,
    updateDeliverable,
    deleteDeliverable,
    DELIVERABLE_STATUSES
  }
}
