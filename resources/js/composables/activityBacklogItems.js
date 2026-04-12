import { ref } from 'vue'
import * as yup from 'yup'
import axios from 'axios'
import { useToast } from './useToast'
import { useValidation } from './useValidation'

const baseUrl = (activityId) => `/api/activities/${activityId}/backlog-items`

const BACKLOG_PRIORITIES = [1, 2, 3, 4]
const BACKLOG_STATUSES = [0, 1, 2, 3]

function normalizeBacklogItem(data) {
  if (!data) return null
  return {
    id: data.id ?? null,
    activity_id: data.activity_id ?? null,
    team_id: data.team_id ?? null,
    title: data.title ?? '',
    description: data.description ?? null,
    priority: data.priority?.value ?? data.priority ?? 2,
    points: data.points ?? null,
    status: data.status?.value ?? data.status ?? 0,
    position: data.position ?? 0
  }
}

export default function useActivityBacklogItems() {
  const backlogItems = ref([])
  const isLoading = ref(false)
  const toast = useToast()

  const initialBacklogItem = {
    id: null,
    team_id: null,
    title: '',
    description: null,
    priority: 2,
    points: null,
    status: 0,
    position: 0
  }

  const backlogItem = ref({ ...initialBacklogItem })

  const { errors, validate, clearErrors, hasError, getError } = useValidation()

  const backlogItemSchema = yup.object({
    team_id: yup.number().nullable().integer().positive(),
    title: yup.string().trim().required('El título es obligatorio').max(150),
    description: yup.string().nullable(),
    priority: yup.number().required().integer().oneOf(BACKLOG_PRIORITIES),
    points: yup.number().nullable().integer().min(0),
    status: yup.number().required().integer().oneOf(BACKLOG_STATUSES),
    position: yup.number().nullable().integer().min(0)
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

  const resetBacklogItem = () => {
    backlogItem.value = { ...initialBacklogItem }
    clearErrors()
  }

  const setBacklogItem = (data = {}) => {
    const n = normalizeBacklogItem(data)
    backlogItem.value = n
      ? {
          id: n.id,
          team_id: n.team_id,
          title: n.title,
          description: n.description,
          priority: n.priority,
          points: n.points,
          status: n.status,
          position: n.position
        }
      : { ...initialBacklogItem }
    clearErrors()
  }

  const upsertBacklogItemRecord = (record) => {
    if (!record?.id) return
    backlogItems.value = [
      record,
      ...backlogItems.value.filter((b) => b.id !== record.id)
    ]
  }

  const unwrap = (response) => response.data?.data ?? response.data

  const buildPayload = (data) => ({
    team_id: data.team_id || null,
    title: data.title,
    description: data.description || null,
    priority: data.priority,
    points: data.points ?? null,
    status: data.status,
    position: data.position ?? 0
  })

  const getBacklogItems = async (activityId) => {
    if (!activityId) throw new Error('activityId requerido')
    try {
      const response = await withLoading(() => axios.get(baseUrl(activityId)))
      const data = unwrap(response)
      backlogItems.value = Array.isArray(data) ? data : []
      return response
    } catch (error) {
      toast.error('Error', 'No se pudo cargar el backlog')
      throw error
    }
  }

  const getBacklogItem = async (activityId, backlogItemId) => {
    if (!activityId || !backlogItemId) return null
    try {
      const response = await withLoading(() =>
        axios.get(`${baseUrl(activityId)}/${backlogItemId}`)
      )
      const raw = unwrap(response)
      setBacklogItem(raw)
      return raw
    } catch (error) {
      toast.error('Error', 'No se pudo obtener el ítem de backlog')
      throw error
    }
  }

  const createBacklogItem = async (activityId, payload) => {
    if (!activityId) throw new Error('activityId requerido')
    const data = payload ?? backlogItem.value
    const { isValid } = validate(backlogItemSchema, data)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }
    try {
      const response = await withLoading(() =>
        axios.post(baseUrl(activityId), buildPayload(data))
      )
      const created = unwrap(response)
      toast.crud.created('Ítem de backlog')
      return created
    } catch (error) {
      toast.error('Error', 'No se pudo crear el ítem de backlog')
      throw error
    }
  }

  const updateBacklogItem = async (activityId, backlogItemId, payload) => {
    if (!activityId || !backlogItemId) throw new Error('IDs requeridos')
    const data = { ...(payload ?? backlogItem.value) }
    const { isValid } = validate(backlogItemSchema, data)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }
    try {
      const response = await withLoading(() =>
        axios.put(`${baseUrl(activityId)}/${backlogItemId}`, buildPayload(data))
      )
      const updated = unwrap(response)
      toast.crud.updated('Ítem de backlog')
      return updated
    } catch (error) {
      toast.error('Error', 'No se pudo actualizar el ítem de backlog')
      throw error
    }
  }

  const deleteBacklogItem = async (activityId, backlogItemId) => {
    if (!activityId || !backlogItemId) throw new Error('IDs requeridos')
    try {
      await withLoading(() =>
        axios.delete(`${baseUrl(activityId)}/${backlogItemId}`)
      )
      backlogItems.value = backlogItems.value.filter((b) => b.id !== backlogItemId)
      toast.crud.deleted('Ítem de backlog')
    } catch (error) {
      toast.error('Error', 'No se pudo eliminar el ítem de backlog')
      throw error
    }
  }

  return {
    backlogItems,
    backlogItem,
    isLoading,
    errors,
    hasError,
    getError,
    resetBacklogItem,
    setBacklogItem,
    upsertBacklogItemRecord,
    getBacklogItems,
    getBacklogItem,
    createBacklogItem,
    updateBacklogItem,
    deleteBacklogItem,
    BACKLOG_PRIORITIES,
    BACKLOG_STATUSES
  }
}
