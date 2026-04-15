import { ref } from 'vue'
import * as yup from 'yup'
import axios from 'axios'
import { useToast } from './useToast'
import { useValidation } from './useValidation'

const baseUrl = (activityId) => `/api/activities/${activityId}/tasks`

const TASK_STATUSES = [0, 1, 2, 3]

function normalizeTask(data) {
  if (!data) return null
  return {
    id: data.id ?? null,
    activity_id: data.activity_id ?? null,
    backlog_item_id: data.backlog_item_id ?? null,
    title: data.title ?? '',
    description: data.description ?? null,
    status: data.status?.value ?? data.status ?? 0,
    position: data.position ?? 0
  }
}

export default function useActivityTasks() {
  const tasks = ref([])
  const isLoading = ref(false)
  const toast = useToast()

  const initialTask = {
    id: null,
    backlog_item_id: null,
    title: '',
    description: null,
    status: 0,
    position: 0
  }

  const task = ref({ ...initialTask })

  const { errors, validate, clearErrors, hasError, getError } = useValidation()

  const taskSchema = yup.object({
    backlog_item_id: yup.number().required('El ítem de backlog es obligatorio').integer().positive(),
    title: yup.string().trim().required('El título es obligatorio').max(150),
    description: yup.string().nullable(),
    status: yup.number().nullable().integer().oneOf(TASK_STATUSES),
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

  const resetTask = () => {
    task.value = { ...initialTask }
    clearErrors()
  }

  const setTask = (data = {}) => {
    const n = normalizeTask(data)
    task.value = n
      ? {
          id: n.id,
          backlog_item_id: n.backlog_item_id,
          title: n.title,
          description: n.description,
          status: n.status,
          position: n.position
        }
      : { ...initialTask }
    clearErrors()
  }

  const upsertTaskRecord = (record) => {
    if (!record?.id) return
    tasks.value = [record, ...tasks.value.filter((t) => t.id !== record.id)]
  }

  const unwrap = (response) => response.data?.data ?? response.data

  const buildPayload = (data) => ({
    backlog_item_id: data.backlog_item_id,
    title: data.title,
    description: data.description || null,
    status: data.status ?? 0,
    position: data.position ?? 0
  })

  const getTasks = async (activityId) => {
    if (!activityId) throw new Error('activityId requerido')
    try {
      const response = await withLoading(() => axios.get(baseUrl(activityId)))
      const data = unwrap(response)
      tasks.value = Array.isArray(data) ? data : []
      return response
    } catch (error) {
      toast.error('Error', 'No se pudieron cargar las tareas')
      throw error
    }
  }

  const getTask = async (activityId, taskId) => {
    if (!activityId || !taskId) return null
    try {
      const response = await withLoading(() =>
        axios.get(`${baseUrl(activityId)}/${taskId}`)
      )
      const raw = unwrap(response)
      setTask(raw)
      return raw
    } catch (error) {
      toast.error('Error', 'No se pudo obtener la tarea')
      throw error
    }
  }

  const createTask = async (activityId, payload) => {
    if (!activityId) throw new Error('activityId requerido')
    const data = payload ?? task.value
    const { isValid } = validate(taskSchema, data)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }
    try {
      const response = await withLoading(() =>
        axios.post(baseUrl(activityId), buildPayload(data))
      )
      const created = unwrap(response)
      toast.crud.created('Tarea')
      return created
    } catch (error) {
      toast.error('Error', 'No se pudo crear la tarea')
      throw error
    }
  }

  const updateTask = async (activityId, taskId, payload) => {
    if (!activityId || !taskId) throw new Error('IDs requeridos')
    const data = { ...(payload ?? task.value) }
    const { isValid } = validate(taskSchema, data)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }
    try {
      const response = await withLoading(() =>
        axios.put(`${baseUrl(activityId)}/${taskId}`, buildPayload(data))
      )
      const updated = unwrap(response)
      toast.crud.updated('Tarea')
      return updated
    } catch (error) {
      toast.error('Error', 'No se pudo actualizar la tarea')
      throw error
    }
  }

  const deleteTask = async (activityId, taskId) => {
    if (!activityId || !taskId) throw new Error('IDs requeridos')
    try {
      await withLoading(() => axios.delete(`${baseUrl(activityId)}/${taskId}`))
      tasks.value = tasks.value.filter((t) => t.id !== taskId)
      toast.crud.deleted('Tarea')
    } catch (error) {
      toast.error('Error', 'No se pudo eliminar la tarea')
      throw error
    }
  }

  /** Reordenar todas las tareas de un ítem de backlog (mismo conjunto de ids que en BD). */
  const reorderTasks = async (activityId, { team_id, backlog_item_id, ids }) => {
    if (!activityId) throw new Error('activityId requerido')
    await axios.post(`${baseUrl(activityId)}/reorder`, {
      team_id,
      backlog_item_id,
      ids,
    })
  }

  return {
    tasks,
    task,
    isLoading,
    errors,
    hasError,
    getError,
    resetTask,
    setTask,
    upsertTaskRecord,
    getTasks,
    getTask,
    createTask,
    updateTask,
    deleteTask,
    reorderTasks,
    clearErrors,
    TASK_STATUSES
  }
}
