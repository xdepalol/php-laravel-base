import { ref } from 'vue'
import * as yup from 'yup'
import axios from 'axios'
import { useToast } from './useToast'
import { useValidation } from './useValidation'

const API = '/api/teachers'

export default function useTeachers() {
  const teachers = ref([])
  const isLoading = ref(false)
  const toast = useToast()

  const initialTeacher = {
    id: null,
    user_id: null,
    ss_number: '',
    teacher_number: ''
  }

  const teacher = ref({ ...initialTeacher })

  const { errors, validate, clearErrors, hasError, getError } = useValidation()

  const teacherCreateSchema = yup.object({
    user_id: yup.number().required('El usuario es obligatorio').integer().positive(),
    ss_number: yup.string().trim().required('La seguridad social es obligatoria').max(255),
    teacher_number: yup.string().trim().required('El número de profesor es obligatorio').max(255)
  })

  const teacherUpdateSchema = yup.object({
    ss_number: yup.string().trim().required('La seguridad social es obligatoria').max(255),
    teacher_number: yup.string().trim().required('El número de profesor es obligatorio').max(255)
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

  const resetTeacher = () => {
    teacher.value = { ...initialTeacher }
    clearErrors()
  }

  const setTeacher = (data = {}) => {
    teacher.value = {
      id: data.id ?? data.user_id ?? null,
      user_id: data.user_id ?? null,
      ss_number: data.ss_number ?? '',
      teacher_number: data.teacher_number ?? ''
    }
    clearErrors()
  }

  const upsertTeacherRecord = (record) => {
    const key = record?.user_id ?? record?.id
    if (!key) return
    teachers.value = [record, ...teachers.value.filter((t) => (t.user_id ?? t.id) !== key)]
  }

  const unwrap = (response) => response.data?.data ?? response.data

  const getTeachers = async () => {
    try {
      const response = await withLoading(() => axios.get(API))
      const data = unwrap(response)
      teachers.value = Array.isArray(data) ? data : []
      return response
    } catch (error) {
      toast.error('Error', 'No se pudieron cargar los profesores')
      throw error
    }
  }

  const getTeacher = async (id) => {
    if (!id) return null
    try {
      const response = await withLoading(() => axios.get(`${API}/${id}`))
      const data = unwrap(response)
      setTeacher(data)
      return data
    } catch (error) {
      toast.error('Error', 'No se pudo obtener el profesor')
      throw error
    }
  }

  const createTeacher = async (payload) => {
    const data = payload ?? teacher.value
    const { isValid } = validate(teacherCreateSchema, data)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }
    try {
      const response = await withLoading(() =>
        axios.post(API, {
          user_id: data.user_id,
          ss_number: data.ss_number,
          teacher_number: data.teacher_number
        })
      )
      const created = unwrap(response)
      toast.crud.created('Profesor')
      return created
    } catch (error) {
      const message =
        error?.response?.data?.message || 'No se pudo crear el profesor'
      toast.error('Error', message)
      throw error
    }
  }

  const updateTeacher = async (payload) => {
    const data = payload ?? teacher.value
    const { isValid } = validate(teacherUpdateSchema, data)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }
    const id = data.user_id ?? data.id
    try {
      const response = await withLoading(() =>
        axios.put(`${API}/${id}`, {
          ss_number: data.ss_number,
          teacher_number: data.teacher_number
        })
      )
      const updated = unwrap(response)
      toast.crud.updated('Profesor')
      return updated
    } catch (error) {
      toast.error('Error', 'No se pudo actualizar el profesor')
      throw error
    }
  }

  const deleteTeacher = async (id) => {
    try {
      await withLoading(() => axios.delete(`${API}/${id}`))
      teachers.value = teachers.value.filter((t) => (t.user_id ?? t.id) !== id)
      toast.crud.deleted('Profesor')
    } catch (error) {
      const message =
        error?.response?.data?.message || 'No se pudo eliminar el profesor'
      toast.error('Error', message)
      throw error
    }
  }

  return {
    teachers,
    teacher,
    isLoading,
    errors,
    hasError,
    getError,
    resetTeacher,
    setTeacher,
    upsertTeacherRecord,
    getTeachers,
    getTeacher,
    createTeacher,
    updateTeacher,
    deleteTeacher
  }
}
