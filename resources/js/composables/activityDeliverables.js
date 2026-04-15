import { ref } from 'vue'
import * as yup from 'yup'
import axios from 'axios'
import { useToast } from './useToast'
import { useValidation } from './useValidation'
import { normalizeDueDateFromApi } from '@/utils/datetime'
import { sortDeliverablesByDueDateThenId } from '@/utils/deliverablesSort'

const baseUrl = (activityId) => `/api/activities/${activityId}/deliverables`

const DELIVERABLE_STATUSES = [0, 1, 2]

const SHORT_CODE_REGEX = /^[a-zA-Z0-9][a-zA-Z0-9_-]*$/

function normalizeDeliverable(data) {
  if (!data) return null
  return {
    id: data.id ?? null,
    title: data.title ?? '',
    short_code: data.short_code ?? '',
    description: data.description ?? null,
    due_date: normalizeDueDateFromApi(data.due_date),
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
    short_code: '',
    description: null,
    due_date: null,
    status: 0,
    is_group_deliverable: false
  }

  const deliverable = ref({ ...initialDeliverable })

  const { errors, validate, clearErrors, hasError, getError } = useValidation()

  const deliverableSchema = yup.object({
    title: yup.string().trim().required('El título es obligatorio').max(100),
    short_code: yup
      .string()
      .trim()
      .required('El código corto es obligatorio')
      .max(32, 'Máximo 32 caracteres')
      .matches(
        SHORT_CODE_REGEX,
        'Letras, números, guiones; debe empezar por letra o número'
      ),
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
          short_code: n.short_code,
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
    short_code: (data.short_code || '').trim(),
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
      deliverables.value = sortDeliverablesByDueDateThenId(Array.isArray(data) ? data : [])
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

  /** GET sin toast ni estado global (p. ej. título en otra vista). */
  const fetchDeliverableById = async (activityId, deliverableId) => {
    if (!activityId || !deliverableId) return null
    try {
      const response = await axios.get(`${baseUrl(activityId)}/${deliverableId}`)
      return unwrap(response)
    } catch {
      return null
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
    fetchDeliverableById,
    createDeliverable,
    updateDeliverable,
    deleteDeliverable,
    DELIVERABLE_STATUSES
  }
}
