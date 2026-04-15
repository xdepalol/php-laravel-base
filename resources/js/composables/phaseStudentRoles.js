import { ref } from 'vue'
import * as yup from 'yup'
import axios from 'axios'
import { useToast } from './useToast'
import { useValidation } from './useValidation'

const API = '/api/phase-student-roles'

export default function usePhaseStudentRoles() {
  const phaseStudentRoles = ref([])
  const isLoading = ref(false)
  const toast = useToast()

  const initialRow = {
    id: null,
    phase_id: null,
    student_id: null,
    team_id: null,
    activity_role_id: null
  }

  const phaseStudentRole = ref({ ...initialRow })

  const { errors, validate, clearErrors, hasError, getError } = useValidation()

  const rowSchema = yup.object({
    phase_id: yup.number().required().integer().positive(),
    student_id: yup.number().required().integer().positive(),
    team_id: yup.number().required().integer().positive(),
    activity_role_id: yup.number().required().integer().positive()
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

  const resetPhaseStudentRole = () => {
    phaseStudentRole.value = { ...initialRow }
    clearErrors()
  }

  const setPhaseStudentRole = (data = {}) => {
    phaseStudentRole.value = {
      id: data.id ?? null,
      phase_id: data.phase_id ?? null,
      student_id: data.student_id ?? null,
      team_id: data.team_id ?? null,
      activity_role_id: data.activity_role_id ?? null
    }
    clearErrors()
  }

  const upsertRecord = (record) => {
    if (!record?.id) return
    phaseStudentRoles.value = [
      record,
      ...phaseStudentRoles.value.filter((r) => r.id !== record.id)
    ]
  }

  const unwrap = (response) => response.data?.data ?? response.data

  const buildPayload = (data) => ({
    phase_id: data.phase_id,
    student_id: data.student_id,
    team_id: data.team_id,
    activity_role_id: data.activity_role_id
  })

  const getPhaseStudentRoles = async () => {
    try {
      const response = await withLoading(() => axios.get(API))
      const data = unwrap(response)
      phaseStudentRoles.value = Array.isArray(data) ? data : []
      return response
    } catch (error) {
      toast.error('Error', 'No se pudieron cargar los roles por fase')
      throw error
    }
  }

  const getPhaseStudentRole = async (id) => {
    if (!id) return null
    try {
      const response = await withLoading(() => axios.get(`${API}/${id}`))
      const data = unwrap(response)
      setPhaseStudentRole(data)
      return data
    } catch (error) {
      toast.error('Error', 'No se pudo obtener el registro')
      throw error
    }
  }

  const createPhaseStudentRole = async (payload) => {
    const data = payload ?? phaseStudentRole.value
    const { isValid } = validate(rowSchema, data)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }
    try {
      const response = await withLoading(() => axios.post(API, buildPayload(data)))
      const created = unwrap(response)
      toast.crud.created('Rol de estudiante en fase')
      return created
    } catch (error) {
      toast.error('Error', 'No se pudo crear el registro')
      throw error
    }
  }

  const updatePhaseStudentRole = async (payload) => {
    const data = payload ?? phaseStudentRole.value
    const { isValid } = validate(rowSchema, data)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }
    try {
      const response = await withLoading(() =>
        axios.put(`${API}/${data.id}`, buildPayload(data))
      )
      const updated = unwrap(response)
      toast.crud.updated('Rol de estudiante en fase')
      return updated
    } catch (error) {
      toast.error('Error', 'No se pudo actualizar el registro')
      throw error
    }
  }

  const deletePhaseStudentRole = async (id) => {
    try {
      await withLoading(() => axios.delete(`${API}/${id}`))
      phaseStudentRoles.value = phaseStudentRoles.value.filter((r) => r.id !== id)
      toast.crud.deleted('Rol de estudiante en fase')
    } catch (error) {
      toast.error('Error', 'No se pudo eliminar el registro')
      throw error
    }
  }

  /**
   * Profesorado: crear, actualizar o quitar rol en fase (sin withLoading; el caller controla toasts).
   *
   * @param {{ existingId?: number|null, phase_id: number, student_id: number, team_id: number, activity_role_id?: number|null }} params
   */
  const saveTeacherPhaseStudentAssignment = async (params) => {
    const { existingId, phase_id, student_id, team_id, activity_role_id } = params
    if (existingId && (activity_role_id == null || activity_role_id === '')) {
      await axios.delete(`${API}/${existingId}`)
      return null
    }
    if (activity_role_id == null || activity_role_id === '') {
      return null
    }
    const body = { phase_id, student_id, team_id, activity_role_id }
    if (existingId) {
      const response = await axios.put(`${API}/${existingId}`, body)
      return unwrap(response)
    }
    const response = await axios.post(API, body)
    return unwrap(response)
  }

  return {
    phaseStudentRoles,
    phaseStudentRole,
    isLoading,
    errors,
    hasError,
    getError,
    resetPhaseStudentRole,
    setPhaseStudentRole,
    upsertPhaseStudentRoleRecord: upsertRecord,
    getPhaseStudentRoles,
    getPhaseStudentRole,
    createPhaseStudentRole,
    updatePhaseStudentRole,
    deletePhaseStudentRole,
    saveTeacherPhaseStudentAssignment,
  }
}
