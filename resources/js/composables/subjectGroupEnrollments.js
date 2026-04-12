import { ref } from 'vue'
import * as yup from 'yup'
import axios from 'axios'
import { useToast } from './useToast'
import { useValidation } from './useValidation'

const baseUrl = (subjectGroupId) => `/api/subject-groups/${subjectGroupId}/enrollments`

const ENROLLMENT_STATUSES = [1, 2, 3, 4]

function normalizeEnrollment(data) {
  if (!data) return null
  return {
    id: data.id ?? null,
    student_id: data.student_id ?? null,
    subject_group_id: data.subject_group_id ?? null,
    status: data.status?.value ?? data.status ?? 1
  }
}

export default function useSubjectGroupEnrollments() {
  const enrollments = ref([])
  const isLoading = ref(false)
  const toast = useToast()

  const initialEnrollment = {
    id: null,
    student_id: null,
    subject_group_id: null,
    status: 1
  }

  const enrollment = ref({ ...initialEnrollment })

  const { errors, validate, clearErrors, hasError, getError } = useValidation()

  const storeSchema = yup.object({
    student_id: yup.number().required().integer().positive(),
    status: yup.number().nullable().integer().oneOf(ENROLLMENT_STATUSES)
  })

  const updateSchema = yup.object({
    student_id: yup.number().required().integer().positive(),
    status: yup.number().nullable().integer().oneOf(ENROLLMENT_STATUSES)
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

  const resetEnrollment = () => {
    enrollment.value = { ...initialEnrollment }
    clearErrors()
  }

  const setEnrollment = (data = {}) => {
    enrollment.value = normalizeEnrollment(data) ?? { ...initialEnrollment }
    clearErrors()
  }

  const unwrap = (response) => response.data?.data ?? response.data

  const getEnrollments = async (subjectGroupId) => {
    if (!subjectGroupId) throw new Error('subjectGroupId requerido')
    try {
      const response = await withLoading(() => axios.get(baseUrl(subjectGroupId)))
      const data = unwrap(response)
      enrollments.value = Array.isArray(data) ? data : []
      return response
    } catch (error) {
      toast.error('Error', 'No se pudieron cargar las matrículas')
      throw error
    }
  }

  const getEnrollment = async (subjectGroupId, enrollmentId) => {
    if (!subjectGroupId || !enrollmentId) return null
    try {
      const response = await withLoading(() =>
        axios.get(`${baseUrl(subjectGroupId)}/${enrollmentId}`)
      )
      const data = unwrap(response)
      setEnrollment(data)
      return data
    } catch (error) {
      toast.error('Error', 'No se pudo obtener la matrícula')
      throw error
    }
  }

  const createEnrollment = async (subjectGroupId, payload) => {
    if (!subjectGroupId) throw new Error('subjectGroupId requerido')
    const data = { ...payload }
    const { isValid } = validate(storeSchema, data)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }
    try {
      const body = { student_id: data.student_id }
      if (data.status != null) body.status = data.status
      const response = await withLoading(() => axios.post(baseUrl(subjectGroupId), body))
      const created = unwrap(response)
      toast.crud.created('Matrícula')
      return created
    } catch (error) {
      const message =
        error?.response?.data?.message || 'No se pudo crear la matrícula'
      toast.error('Error', message)
      throw error
    }
  }

  const updateEnrollment = async (subjectGroupId, enrollmentId, payload) => {
    if (!subjectGroupId || !enrollmentId) throw new Error('IDs requeridos')
    const data = { ...(payload ?? enrollment.value), id: enrollmentId }
    const { isValid } = validate(updateSchema, data)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }
    try {
      const body = { student_id: data.student_id }
      if (data.status != null) body.status = data.status
      const response = await withLoading(() =>
        axios.put(`${baseUrl(subjectGroupId)}/${enrollmentId}`, body)
      )
      const updated = unwrap(response)
      toast.crud.updated('Matrícula')
      return updated
    } catch (error) {
      const message =
        error?.response?.data?.message || 'No se pudo actualizar la matrícula'
      toast.error('Error', message)
      throw error
    }
  }

  const deleteEnrollment = async (subjectGroupId, enrollmentId) => {
    if (!subjectGroupId || !enrollmentId) throw new Error('IDs requeridos')
    try {
      await withLoading(() =>
        axios.delete(`${baseUrl(subjectGroupId)}/${enrollmentId}`)
      )
      enrollments.value = enrollments.value.filter((e) => e.id !== enrollmentId)
      toast.crud.deleted('Matrícula')
    } catch (error) {
      toast.error('Error', 'No se pudo eliminar la matrícula')
      throw error
    }
  }

  return {
    enrollments,
    enrollment,
    isLoading,
    errors,
    hasError,
    getError,
    resetEnrollment,
    setEnrollment,
    getEnrollments,
    getEnrollment,
    createEnrollment,
    updateEnrollment,
    deleteEnrollment,
    ENROLLMENT_STATUSES
  }
}
