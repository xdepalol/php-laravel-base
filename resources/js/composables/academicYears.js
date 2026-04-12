import { ref } from 'vue'
import * as yup from 'yup'
import axios from 'axios'
import { useToast } from './useToast'
import { useValidation } from './useValidation'

const API = '/api/academic-years'

export default function useAcademicYears() {
  const academicYears = ref([])
  const isLoading = ref(false)
  const toast = useToast()

  const initialAcademicYear = {
    id: null,
    year_code: '',
    description: '',
    is_active: true
  }

  const academicYear = ref({ ...initialAcademicYear })

  const {
    errors,
    validate,
    clearErrors,
    hasError,
    getError
  } = useValidation()

  const academicYearSchema = yup.object({
    year_code: yup
      .string()
      .trim()
      .required('El código del curso académico es obligatorio')
      .max(10)
      .matches(/^\d{2,4}-\d{2,4}$/, 'El formato debe ser tipo "2024-2025" o "24-25"'),
    description: yup
      .string()
      .trim()
      .required('La descripción es obligatoria')
      .max(255),
    is_active: yup.boolean()
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

  const resetAcademicYear = () => {
    academicYear.value = { ...initialAcademicYear }
    clearErrors()
  }

  const setAcademicYear = (data = {}) => {
    academicYear.value = {
      id: data.id ?? null,
      year_code: data.year_code ?? '',
      description: data.description ?? '',
      is_active: data.is_active !== undefined ? !!data.is_active : true
    }
    clearErrors()
  }

  const upsertAcademicYearRecord = (record) => {
    if (!record?.id) return
    academicYears.value = [
      record,
      ...academicYears.value.filter((y) => y.id !== record.id)
    ]
  }

  const unwrap = (response) => response.data?.data ?? response.data

  const getAcademicYears = async () => {
    try {
      const response = await withLoading(() => axios.get(API))
      const data = unwrap(response)
      academicYears.value = Array.isArray(data) ? data : []
      return response
    } catch (error) {
      toast.error('Error', 'No se pudieron cargar los cursos académicos')
      throw error
    }
  }

  const getAcademicYear = async (id) => {
    if (!id) return null
    try {
      const response = await withLoading(() => axios.get(`${API}/${id}`))
      const data = unwrap(response)
      setAcademicYear(data)
      return data
    } catch (error) {
      toast.error('Error', 'No se pudo obtener el curso académico')
      throw error
    }
  }

  const createAcademicYear = async (payload) => {
    const data = payload ?? academicYear.value
    const { isValid } = validate(academicYearSchema, data)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }

    try {
      const response = await withLoading(() =>
        axios.post(API, {
          year_code: data.year_code,
          description: data.description,
          is_active: !!data.is_active
        })
      )
      const created = unwrap(response)
      toast.crud.created('Curso académico')
      return created
    } catch (error) {
      toast.error('Error', 'No se pudo crear el curso académico')
      throw error
    }
  }

  const updateAcademicYear = async (payload) => {
    const data = payload ?? academicYear.value
    const { isValid } = validate(academicYearSchema, data)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }

    try {
      const response = await withLoading(() =>
        axios.put(`${API}/${data.id}`, {
          year_code: data.year_code,
          description: data.description,
          is_active: !!data.is_active
        })
      )
      const updated = unwrap(response)
      toast.crud.updated('Curso académico')
      return updated
    } catch (error) {
      toast.error('Error', 'No se pudo actualizar el curso académico')
      throw error
    }
  }

  const deleteAcademicYear = async (id) => {
    try {
      await withLoading(() => axios.delete(`${API}/${id}`))
      academicYears.value = academicYears.value.filter((y) => y.id !== id)
      toast.crud.deleted('Curso académico')
    } catch (error) {
      const message = error?.response?.data?.message || 'No se pudo eliminar el curso académico'
      toast.error('Error', message)
      throw error
    }
  }

  return {
    academicYears,
    academicYear,
    isLoading,
    errors,
    hasError,
    getError,
    resetAcademicYear,
    setAcademicYear,
    upsertAcademicYearRecord,
    getAcademicYears,
    getAcademicYear,
    createAcademicYear,
    updateAcademicYear,
    deleteAcademicYear
  }
}
