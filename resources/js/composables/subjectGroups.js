import { ref } from 'vue'
import * as yup from 'yup'
import axios from 'axios'
import { authStore } from '@/store/auth'
import { useToast } from './useToast'
import { useValidation } from './useValidation'

/**
 * Docentes: grupos donde imparte. Estudiantes (sin rol docente): grupos con matrícula activa.
 * Si el usuario es docente, tiene prioridad la vista docente.
 */
function mySubjectGroupsListUrl() {
  const roles = authStore().user?.roles ?? []
  const names = roles.map((r) => r.name)
  if (names.includes('teacher')) {
    return '/api/me/subject-groups'
  }
  if (names.includes('student')) {
    return '/api/me/student/subject-groups'
  }
  return '/api/me/subject-groups'
}

const API = '/api/subject-groups'

export default function useSubjectGroups() {
  const subjectGroups = ref([])
  const mySubjectGroups = ref([])
  const isLoading = ref(false)
  const toast = useToast()

  const initialSubjectGroup = {
    id: null,
    academic_year_id: null,
    group_id: null,
    subject_id: null,
    teachers: [],
    group: null,
    subject: null
  }

  const subjectGroup = ref({ ...initialSubjectGroup })

  const { errors, validate, clearErrors, hasError, getError } = useValidation()

  const subjectGroupSchema = yup.object({
    academic_year_id: yup.number().required().integer().positive(),
    group_id: yup.number().required().integer().positive(),
    subject_id: yup.number().required().integer().positive(),
    teachers: yup
      .array()
      .of(yup.number().integer().positive())
      .min(1, 'Debe haber al menos un profesor'),
    main_teacher_id: yup.number().nullable().integer().positive()
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

  const normalizeTeachers = (data) => {
    const t = data.teachers
    if (!Array.isArray(t)) return []
    return t.map((x) => (typeof x === 'object' && x !== null ? x.user_id ?? x.id : x))
  }

  const resetSubjectGroup = () => {
    subjectGroup.value = { ...initialSubjectGroup }
    clearErrors()
  }

  const setSubjectGroup = (data = {}) => {
    subjectGroup.value = {
      id: data.id ?? null,
      academic_year_id: data.academic_year_id ?? null,
      group_id: data.group_id ?? null,
      subject_id: data.subject_id ?? null,
      teachers: normalizeTeachers(data),
      main_teacher_id: data.main_teacher_id ?? null,
      group: data.group ?? null,
      subject: data.subject ?? null
    }
    clearErrors()
  }

  const upsertSubjectGroupRecord = (record) => {
    if (!record?.id) return
    subjectGroups.value = [
      record,
      ...subjectGroups.value.filter((g) => g.id !== record.id)
    ]
  }

  const unwrap = (response) => response.data?.data ?? response.data

  const buildPayload = (data) => {
    const payload = {
      academic_year_id: data.academic_year_id,
      group_id: data.group_id,
      subject_id: data.subject_id,
      teachers: Array.isArray(data.teachers) ? data.teachers : []
    }
    if (data.main_teacher_id != null) {
      payload.main_teacher_id = data.main_teacher_id
    }
    return payload
  }

  const getSubjectGroups = async () => {
    try {
      const response = await withLoading(() => axios.get(API))
      const data = unwrap(response)
      subjectGroups.value = Array.isArray(data) ? data : []
      return response
    } catch (error) {
      toast.error('Error', 'No se pudieron cargar los grupos de asignatura')
      throw error
    }
  }

  /** Grupos de asignatura (docencia o matrículas del alumno) en un curso académico. */
  const getMySubjectGroupsForYear = async (academicYearId) => {
    if (!academicYearId) {
      mySubjectGroups.value = []
      return null
    }
    try {
      const response = await withLoading(() =>
        axios.get(mySubjectGroupsListUrl(), {
          params: { academic_year_id: academicYearId }
        })
      )
      const data = unwrap(response)
      mySubjectGroups.value = Array.isArray(data) ? data : []
      return response
    } catch (error) {
      toast.error('Error', 'No se pudieron cargar tus asignaturas')
      throw error
    }
  }

  const getSubjectGroup = async (id) => {
    if (!id) return null
    try {
      const response = await withLoading(() => axios.get(`${API}/${id}`))
      const data = unwrap(response)
      setSubjectGroup(data)
      return data
    } catch (error) {
      toast.error('Error', 'No se pudo obtener el grupo de asignatura')
      throw error
    }
  }

  const createSubjectGroup = async (payload) => {
    const data = payload ?? subjectGroup.value
    const { isValid } = validate(subjectGroupSchema, data)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }
    try {
      const response = await withLoading(() => axios.post(API, buildPayload(data)))
      const created = unwrap(response)
      toast.crud.created('Grupo de asignatura')
      return created
    } catch (error) {
      toast.error('Error', 'No se pudo crear el grupo de asignatura')
      throw error
    }
  }

  const updateSubjectGroup = async (payload) => {
    const data = payload ?? subjectGroup.value
    const { isValid } = validate(subjectGroupSchema, data)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }
    try {
      const response = await withLoading(() =>
        axios.put(`${API}/${data.id}`, buildPayload(data))
      )
      const updated = unwrap(response)
      toast.crud.updated('Grupo de asignatura')
      return updated
    } catch (error) {
      toast.error('Error', 'No se pudo actualizar el grupo de asignatura')
      throw error
    }
  }

  const deleteSubjectGroup = async (id) => {
    try {
      await withLoading(() => axios.delete(`${API}/${id}`))
      subjectGroups.value = subjectGroups.value.filter((g) => g.id !== id)
      toast.crud.deleted('Grupo de asignatura')
    } catch (error) {
      const message =
        error?.response?.data?.message || 'No se pudo eliminar el grupo de asignatura'
      toast.error('Error', message)
      throw error
    }
  }

  return {
    subjectGroups,
    mySubjectGroups,
    subjectGroup,
    isLoading,
    errors,
    hasError,
    getError,
    resetSubjectGroup,
    setSubjectGroup,
    upsertSubjectGroupRecord,
    getSubjectGroups,
    getMySubjectGroupsForYear,
    getSubjectGroup,
    createSubjectGroup,
    updateSubjectGroup,
    deleteSubjectGroup
  }
}
