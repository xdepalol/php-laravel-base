import { ref } from 'vue'
import * as yup from 'yup'
import axios from 'axios'
import { useToast } from './useToast'
import { useValidation } from './useValidation'

const API = '/api/phase-tasks'

export default function usePhaseTasks() {
  const phaseTasks = ref([])
  const isLoading = ref(false)
  const toast = useToast()

  const initialPhaseTask = {
    id: null,
    phase_id: null,
    task_id: null,
    student_id: null,
    position: 0
  }

  const phaseTask = ref({ ...initialPhaseTask })

  const { errors, validate, clearErrors, hasError, getError } = useValidation()

  const phaseTaskSchema = yup.object({
    phase_id: yup.number().required().integer().positive(),
    task_id: yup.number().required().integer().positive(),
    student_id: yup.number().nullable().integer().positive(),
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

  const resetPhaseTask = () => {
    phaseTask.value = { ...initialPhaseTask }
    clearErrors()
  }

  const setPhaseTask = (data = {}) => {
    phaseTask.value = {
      id: data.id ?? null,
      phase_id: data.phase_id ?? null,
      task_id: data.task_id ?? null,
      student_id: data.student_id ?? null,
      position: data.position ?? 0
    }
    clearErrors()
  }

  const upsertPhaseTaskRecord = (record) => {
    if (!record?.id) return
    phaseTasks.value = [
      record,
      ...phaseTasks.value.filter((pt) => pt.id !== record.id)
    ]
  }

  const unwrap = (response) => response.data?.data ?? response.data

  const buildPayload = (data) => ({
    phase_id: data.phase_id,
    task_id: data.task_id,
    student_id: data.student_id || null,
    position: data.position ?? 0
  })

  const getPhaseTasks = async () => {
    try {
      const response = await withLoading(() => axios.get(API))
      const data = unwrap(response)
      phaseTasks.value = Array.isArray(data) ? data : []
      return response
    } catch (error) {
      toast.error('Error', 'No se pudieron cargar las tareas de fase')
      throw error
    }
  }

  const getPhaseTask = async (id) => {
    if (!id) return null
    try {
      const response = await withLoading(() => axios.get(`${API}/${id}`))
      const data = unwrap(response)
      setPhaseTask(data)
      return data
    } catch (error) {
      toast.error('Error', 'No se pudo obtener la tarea de fase')
      throw error
    }
  }

  const createPhaseTask = async (payload) => {
    const data = payload ?? phaseTask.value
    const { isValid } = validate(phaseTaskSchema, data)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }
    try {
      const response = await withLoading(() => axios.post(API, buildPayload(data)))
      const created = unwrap(response)
      toast.crud.created('Tarea de fase')
      return created
    } catch (error) {
      toast.error('Error', 'No se pudo crear la tarea de fase')
      throw error
    }
  }

  const updatePhaseTask = async (payload) => {
    const data = payload ?? phaseTask.value
    const { isValid } = validate(phaseTaskSchema, data)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }
    try {
      const response = await withLoading(() =>
        axios.put(`${API}/${data.id}`, buildPayload(data))
      )
      const updated = unwrap(response)
      toast.crud.updated('Tarea de fase')
      return updated
    } catch (error) {
      toast.error('Error', 'No se pudo actualizar la tarea de fase')
      throw error
    }
  }

  const deletePhaseTask = async (id) => {
    try {
      await withLoading(() => axios.delete(`${API}/${id}`))
      phaseTasks.value = phaseTasks.value.filter((pt) => pt.id !== id)
      toast.crud.deleted('Tarea de fase')
    } catch (error) {
      toast.error('Error', 'No se pudo eliminar la tarea de fase')
      throw error
    }
  }

  return {
    phaseTasks,
    phaseTask,
    isLoading,
    errors,
    hasError,
    getError,
    resetPhaseTask,
    setPhaseTask,
    upsertPhaseTaskRecord,
    getPhaseTasks,
    getPhaseTask,
    createPhaseTask,
    updatePhaseTask,
    deletePhaseTask
  }
}
