import { ref } from 'vue'
import * as yup from 'yup'
import axios from 'axios'
import { useToast } from './useToast'
import { useValidation } from './useValidation'

const API = '/api/subjects'

export default function useSubjects() {
  const subjects = ref([])
  const isLoading = ref(false)
  const toast = useToast()

  const initialSubject = {
    id: null,
    name: '',
    acronym: '',
    year_hours: null
  }

  const subject = ref({ ...initialSubject })

  const { errors, validate, clearErrors, hasError, getError } = useValidation()

  const subjectSchema = yup.object({
    name: yup.string().trim().required('El nombre es obligatorio').max(255),
    acronym: yup.string().trim().required('El acrónimo es obligatorio').max(50),
    year_hours: yup.number().nullable().integer().min(0)
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

  const resetSubject = () => {
    subject.value = { ...initialSubject }
    clearErrors()
  }

  const setSubject = (data = {}) => {
    subject.value = {
      id: data.id ?? null,
      name: data.name ?? '',
      acronym: data.acronym ?? '',
      year_hours: data.year_hours ?? null
    }
    clearErrors()
  }

  const upsertSubjectRecord = (record) => {
    if (!record?.id) return
    subjects.value = [record, ...subjects.value.filter((s) => s.id !== record.id)]
  }

  const unwrap = (response) => response.data?.data ?? response.data

  const getSubjects = async () => {
    try {
      const response = await withLoading(() => axios.get(API))
      const data = unwrap(response)
      subjects.value = Array.isArray(data) ? data : []
      return response
    } catch (error) {
      toast.error('Error', 'No se pudieron cargar las asignaturas')
      throw error
    }
  }

  const getSubject = async (id) => {
    if (!id) return null
    try {
      const response = await withLoading(() => axios.get(`${API}/${id}`))
      const data = unwrap(response)
      setSubject(data)
      return data
    } catch (error) {
      toast.error('Error', 'No se pudo obtener la asignatura')
      throw error
    }
  }

  const buildPayload = (data) => ({
    name: data.name,
    acronym: data.acronym,
    year_hours: data.year_hours
  })

  const createSubject = async (payload) => {
    const data = payload ?? subject.value
    const { isValid } = validate(subjectSchema, data)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }
    try {
      const response = await withLoading(() => axios.post(API, buildPayload(data)))
      const created = unwrap(response)
      toast.crud.created('Asignatura')
      return created
    } catch (error) {
      toast.error('Error', 'No se pudo crear la asignatura')
      throw error
    }
  }

  const updateSubject = async (payload) => {
    const data = payload ?? subject.value
    const { isValid } = validate(subjectSchema, data)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }
    try {
      const response = await withLoading(() =>
        axios.put(`${API}/${data.id}`, buildPayload(data))
      )
      const updated = unwrap(response)
      toast.crud.updated('Asignatura')
      return updated
    } catch (error) {
      toast.error('Error', 'No se pudo actualizar la asignatura')
      throw error
    }
  }

  const deleteSubject = async (id) => {
    try {
      await withLoading(() => axios.delete(`${API}/${id}`))
      subjects.value = subjects.value.filter((s) => s.id !== id)
      toast.crud.deleted('Asignatura')
    } catch (error) {
      const message =
        error?.response?.data?.message || 'No se pudo eliminar la asignatura'
      toast.error('Error', message)
      throw error
    }
  }

  return {
    subjects,
    subject,
    isLoading,
    errors,
    hasError,
    getError,
    resetSubject,
    setSubject,
    upsertSubjectRecord,
    getSubjects,
    getSubject,
    createSubject,
    updateSubject,
    deleteSubject
  }
}
