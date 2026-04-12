import { ref } from 'vue'
import * as yup from 'yup'
import axios from 'axios'
import { useToast } from './useToast'
import { useValidation } from './useValidation'

const API = '/api/courses'

export default function useCourses() {
  const courses = ref([])
  const isLoading = ref(false)
  const toast = useToast()

  const initialCourse = {
    id: null,
    name: '',
    acronym: ''
  }

  const course = ref({ ...initialCourse })

  const {
    errors,
    validate,
    clearErrors,
    hasError,
    getError
  } = useValidation()

  const courseSchema = yup.object({
    name: yup
      .string()
      .trim()
      .required('El nombre del ciclo es obligatorio')
      .min(10, 'El nombre debe tener al menos 10 caracteres')
      .max(100),
    acronym: yup
      .string()
      .trim()
      .required('El acrónimo es obligatorio')
      .min(2, 'El acrónimo debe tener al menos 2 caracteres')
      .max(20)
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

  const resetCourse = () => {
    course.value = { ...initialCourse }
    clearErrors()
  }

  const setCourse = (data = {}) => {
    course.value = {
      id: data.id ?? null,
      name: data.name ?? '',
      acronym: data.acronym ?? ''
    }
    clearErrors()
  }

  const upsertCourseRecord = (record) => {
    if (!record?.id) return
    courses.value = [record, ...courses.value.filter((c) => c.id !== record.id)]
  }

  const unwrap = (response) => response.data?.data ?? response.data

  const getCourses = async () => {
    try {
      const response = await withLoading(() => axios.get(API))
      const data = unwrap(response)
      courses.value = Array.isArray(data) ? data : []
      return response
    } catch (error) {
      toast.error('Error', 'No se pudieron cargar los ciclos')
      throw error
    }
  }

  const getCourse = async (id) => {
    if (!id) return null
    try {
      const response = await withLoading(() => axios.get(`${API}/${id}`))
      const data = unwrap(response)
      setCourse(data)
      return data
    } catch (error) {
      toast.error('Error', 'No se pudo obtener el ciclo')
      throw error
    }
  }

  const createCourse = async (payload) => {
    const data = payload ?? course.value
    const { isValid } = validate(courseSchema, data)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }

    try {
      const response = await withLoading(() =>
        axios.post(API, { name: data.name, acronym: data.acronym })
      )
      const created = unwrap(response)
      toast.crud.created('Ciclo')
      return created
    } catch (error) {
      toast.error('Error', 'No se pudo crear el ciclo')
      throw error
    }
  }

  const updateCourse = async (payload) => {
    const data = payload ?? course.value
    const { isValid } = validate(courseSchema, data)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }

    try {
      const response = await withLoading(() =>
        axios.put(`${API}/${data.id}`, { name: data.name, acronym: data.acronym })
      )
      const updated = unwrap(response)
      toast.crud.updated('Ciclo')
      return updated
    } catch (error) {
      toast.error('Error', 'No se pudo actualizar el ciclo')
      throw error
    }
  }

  const deleteCourse = async (id) => {
    try {
      await withLoading(() => axios.delete(`${API}/${id}`))
      courses.value = courses.value.filter((c) => c.id !== id)
      toast.crud.deleted('Ciclo')
    } catch (error) {
      const message = error?.response?.data?.message || 'No se pudo eliminar el ciclo'
      toast.error('Error', message)
      throw error
    }
  }

  return {
    courses,
    course,
    isLoading,
    errors,
    hasError,
    getError,
    resetCourse,
    setCourse,
    upsertCourseRecord,
    getCourses,
    getCourse,
    createCourse,
    updateCourse,
    deleteCourse
  }
}
