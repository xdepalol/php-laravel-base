import { ref } from 'vue'
import * as yup from 'yup'
import axios from 'axios'
import { useToast } from './useToast'
import { useValidation } from './useValidation'

const baseUrl = (deliverableId) => `/api/deliverables/${deliverableId}/submissions`

const SUBMISSION_STATUSES = [0, 1, 2]

function normalizeSubmission(data) {
  if (!data) return null
  return {
    id: data.id ?? null,
    deliverable_id: data.deliverable_id ?? null,
    activity_id: data.activity_id ?? null,
    student_id: data.student_id ?? null,
    team_id: data.team_id ?? null,
    content_url: data.content_url ?? null,
    content_text: data.content_text ?? null,
    submitted_at: data.submitted_at ?? null,
    status: data.status?.value ?? data.status ?? 0,
    grade: data.grade ?? null,
    feedback: data.feedback ?? null
  }
}

const submissionBodySchema = yup.object({
  student_id: yup.number().nullable().optional().integer().positive(),
  team_id: yup.number().nullable().optional().integer().positive(),
  content_url: yup.string().nullable().optional().max(256),
  content_text: yup.string().nullable().optional(),
  submitted_at: yup.string().nullable().optional(),
  status: yup.number().nullable().optional().integer().oneOf(SUBMISSION_STATUSES),
  grade: yup.number().nullable().optional().min(0).max(99.99),
  feedback: yup.string().nullable().optional()
})

export default function useDeliverableSubmissions() {
  const submissions = ref([])
  const isLoading = ref(false)
  const toast = useToast()

  const initialSubmission = {
    id: null,
    student_id: null,
    team_id: null,
    content_url: null,
    content_text: null,
    submitted_at: null,
    status: 0,
    grade: null,
    feedback: null
  }

  const submission = ref({ ...initialSubmission })

  const { errors, validate, clearErrors, hasError, getError } = useValidation()

  const withLoading = async (fn) => {
    if (isLoading.value) throw new Error('Operación en curso')
    isLoading.value = true
    try {
      return await fn()
    } finally {
      isLoading.value = false
    }
  }

  const resetSubmission = () => {
    submission.value = { ...initialSubmission }
    clearErrors()
  }

  const setSubmission = (data = {}) => {
    const n = normalizeSubmission(data)
    if (!n) {
      submission.value = { ...initialSubmission }
      clearErrors()
      return
    }
    submission.value = {
      ...initialSubmission,
      ...n,
      status: data.status ?? n.status,
      student: data.student ?? null,
      team: data.team ?? null,
    }
    clearErrors()
  }

  const unwrap = (response) => response.data?.data ?? response.data

  const statusToNumber = (s) => {
    if (s == null) return null
    if (typeof s === 'object' && s.value != null) return s.value
    return s
  }

  const buildPayload = (data) => {
    const body = {}
    if (data.student_id != null) body.student_id = data.student_id
    if (data.team_id != null) body.team_id = data.team_id
    if (data.content_url != null) body.content_url = data.content_url
    if (data.content_text != null) body.content_text = data.content_text
    if (data.submitted_at != null) body.submitted_at = data.submitted_at
    const st = statusToNumber(data.status)
    if (st != null) body.status = st
    if (data.grade != null) body.grade = data.grade
    if (data.feedback != null) body.feedback = data.feedback
    return body
  }

  const getSubmissions = async (deliverableId) => {
    if (!deliverableId) throw new Error('deliverableId requerido')
    try {
      const response = await withLoading(() => axios.get(baseUrl(deliverableId)))
      const data = unwrap(response)
      submissions.value = Array.isArray(data) ? data : []
      return response
    } catch (error) {
      toast.error('Error', 'No se pudieron cargar las entregas')
      throw error
    }
  }

  /** Listado sin toast ni loading global (matrices, filas en paralelo). */
  const fetchSubmissionsList = async (deliverableId) => {
    if (!deliverableId) return []
    try {
      const response = await axios.get(baseUrl(deliverableId))
      const data = unwrap(response)
      return Array.isArray(data) ? data : []
    } catch {
      return []
    }
  }

  const getSubmission = async (deliverableId, submissionId) => {
    if (!deliverableId || !submissionId) return null
    try {
      const response = await withLoading(() =>
        axios.get(`${baseUrl(deliverableId)}/${submissionId}`)
      )
      const raw = unwrap(response)
      setSubmission(raw)
      return raw
    } catch (error) {
      toast.error('Error', 'No se pudo obtener la entrega')
      throw error
    }
  }

  const createSubmission = async (deliverableId, payload, options = {}) => {
    const { notifySuccess = true, notifyError = true } = options
    if (!deliverableId) throw new Error('deliverableId requerido')
    const data = { ...(payload ?? submission.value) }
    data.status = statusToNumber(data.status) ?? data.status
    const { isValid } = validate(submissionBodySchema, data)
    if (!isValid) {
      if (notifyError) toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }
    try {
      const response = await withLoading(() =>
        axios.post(baseUrl(deliverableId), buildPayload(data))
      )
      const created = unwrap(response)
      if (notifySuccess) toast.crud.created('Entrega')
      return created
    } catch (error) {
      if (notifyError) toast.error('Error', 'No se pudo crear la entrega')
      throw error
    }
  }

  const updateSubmission = async (deliverableId, submissionId, payload, options = {}) => {
    const { notifySuccess = true, notifyError = true } = options
    if (!deliverableId || !submissionId) throw new Error('IDs requeridos')
    const data = { ...(payload ?? submission.value) }
    data.status = statusToNumber(data.status) ?? data.status
    const { isValid } = validate(submissionBodySchema, data)
    if (!isValid) {
      if (notifyError) toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }
    try {
      const response = await withLoading(() =>
        axios.put(`${baseUrl(deliverableId)}/${submissionId}`, buildPayload(data))
      )
      const updated = unwrap(response)
      if (notifySuccess) toast.crud.updated('Entrega')
      return updated
    } catch (error) {
      if (notifyError) toast.error('Error', 'No se pudo actualizar la entrega')
      throw error
    }
  }

  const deleteSubmission = async (deliverableId, submissionId) => {
    if (!deliverableId || !submissionId) throw new Error('IDs requeridos')
    try {
      await withLoading(() =>
        axios.delete(`${baseUrl(deliverableId)}/${submissionId}`)
      )
      submissions.value = submissions.value.filter((s) => s.id !== submissionId)
      toast.crud.deleted('Entrega')
    } catch (error) {
      toast.error('Error', 'No se pudo eliminar la entrega')
      throw error
    }
  }

  return {
    submissions,
    submission,
    isLoading,
    errors,
    hasError,
    getError,
    resetSubmission,
    setSubmission,
    getSubmissions,
    fetchSubmissionsList,
    getSubmission,
    createSubmission,
    updateSubmission,
    deleteSubmission,
    SUBMISSION_STATUSES
  }
}
