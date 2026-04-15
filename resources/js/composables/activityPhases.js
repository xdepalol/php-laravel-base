import { ref } from 'vue'
import * as yup from 'yup'
import axios from 'axios'
import { useToast } from './useToast'
import { useValidation } from './useValidation'

const baseUrl = (activityId) => `/api/activities/${activityId}/phases`

export default function useActivityPhases() {
  const phases = ref([])
  const isLoading = ref(false)
  const toast = useToast()

  const initialPhase = {
    id: null,
    title: '',
    is_sprint: false,
    start_date: null,
    end_date: null,
    retro_well: null,
    retro_bad: null,
    retro_improvement: null,
    teacher_feedback: null,
    teams_may_assign_phase_roles: false,
  }

  const phase = ref({ ...initialPhase })

  const { errors, validate, clearErrors, hasError, getError } = useValidation()

  const phaseSchema = yup.object({
    title: yup.string().trim().required('El título es obligatorio').max(255),
    is_sprint: yup.boolean(),
    start_date: yup.string().nullable(),
    end_date: yup
      .string()
      .nullable()
      .test(
        'after-or-equal-start',
        'La fecha de fin debe ser posterior o igual a la de inicio',
        function endRule(v) {
          const start = this.parent.start_date
          if (!v || !start) return true
          return new Date(v) >= new Date(start)
        }
      ),
    retro_well: yup.string().nullable(),
    retro_bad: yup.string().nullable(),
    retro_improvement: yup.string().nullable(),
    teacher_feedback: yup.string().nullable(),
    teams_may_assign_phase_roles: yup.boolean(),
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

  const resetPhase = () => {
    phase.value = { ...initialPhase }
    clearErrors()
  }

  const setPhase = (data = {}) => {
    phase.value = {
      id: data.id ?? null,
      title: data.title ?? '',
      is_sprint: !!data.is_sprint,
      start_date: data.start_date ?? null,
      end_date: data.end_date ?? null,
      retro_well: data.retro_well ?? null,
      retro_bad: data.retro_bad ?? null,
      retro_improvement: data.retro_improvement ?? null,
      teacher_feedback: data.teacher_feedback ?? null,
      teams_may_assign_phase_roles: !!data.teams_may_assign_phase_roles,
    }
    clearErrors()
  }

  const upsertPhaseRecord = (record) => {
    if (!record?.id) return
    phases.value = [record, ...phases.value.filter((p) => p.id !== record.id)]
  }

  const unwrap = (response) => response.data?.data ?? response.data

  const buildPayload = (data) => ({
    title: data.title,
    is_sprint: !!data.is_sprint,
    start_date: data.start_date || null,
    end_date: data.end_date || null,
    retro_well: data.retro_well || null,
    retro_bad: data.retro_bad || null,
    retro_improvement: data.retro_improvement || null,
    teacher_feedback: data.teacher_feedback || null,
    teams_may_assign_phase_roles: !!data.teams_may_assign_phase_roles,
  })

  const getPhases = async (activityId) => {
    if (!activityId) throw new Error('activityId requerido')
    try {
      const response = await withLoading(() => axios.get(baseUrl(activityId)))
      const data = unwrap(response)
      phases.value = Array.isArray(data) ? data : []
      return response
    } catch (error) {
      toast.error('Error', 'No se pudieron cargar las fases')
      throw error
    }
  }

  const getPhase = async (activityId, phaseId) => {
    if (!activityId || !phaseId) return null
    try {
      const response = await withLoading(() =>
        axios.get(`${baseUrl(activityId)}/${phaseId}`)
      )
      const data = unwrap(response)
      setPhase(data)
      return data
    } catch (error) {
      toast.error('Error', 'No se pudo obtener la fase')
      throw error
    }
  }

  const createPhase = async (activityId, payload) => {
    if (!activityId) throw new Error('activityId requerido')
    const data = payload ?? phase.value
    const { isValid } = validate(phaseSchema, data)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }
    try {
      const response = await withLoading(() =>
        axios.post(baseUrl(activityId), buildPayload(data))
      )
      const created = unwrap(response)
      toast.crud.created('Fase')
      return created
    } catch (error) {
      toast.error('Error', 'No se pudo crear la fase')
      throw error
    }
  }

  const updatePhase = async (activityId, phaseId, payload) => {
    if (!activityId || !phaseId) throw new Error('IDs requeridos')
    const data = { ...(payload ?? phase.value) }
    const { isValid } = validate(phaseSchema, data)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }
    try {
      const response = await withLoading(() =>
        axios.put(`${baseUrl(activityId)}/${phaseId}`, buildPayload(data))
      )
      const updated = unwrap(response)
      toast.crud.updated('Fase')
      return updated
    } catch (error) {
      toast.error('Error', 'No se pudo actualizar la fase')
      throw error
    }
  }

  const deletePhase = async (activityId, phaseId) => {
    if (!activityId || !phaseId) throw new Error('IDs requeridos')
    try {
      await withLoading(() => axios.delete(`${baseUrl(activityId)}/${phaseId}`))
      phases.value = phases.value.filter((p) => p.id !== phaseId)
      toast.crud.deleted('Fase')
    } catch (error) {
      toast.error('Error', 'No se pudo eliminar la fase')
      throw error
    }
  }

  /**
   * Importación masiva (CSV / pegado Excel). Requiere permiso phase-create en el servidor.
   *
   * @returns {Promise<{ created: number, skipped: number, errors: { row: number, message: string }[] }>}
   */
  const importPhasesCsv = async (activityId, csvText) => {
    if (!activityId) throw new Error('activityId requerido')
    try {
      const response = await withLoading(() =>
        axios.post(`${baseUrl(activityId)}/csv-import`, { csv: csvText })
      )
      const data = unwrap(response)
      return data
    } catch (error) {
      toast.error('Error', 'No se pudo importar el CSV de fases')
      throw error
    }
  }

  return {
    phases,
    phase,
    isLoading,
    errors,
    hasError,
    getError,
    clearErrors,
    resetPhase,
    setPhase,
    upsertPhaseRecord,
    getPhases,
    getPhase,
    createPhase,
    updatePhase,
    deletePhase,
    importPhasesCsv
  }
}
