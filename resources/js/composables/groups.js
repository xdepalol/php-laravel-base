import { ref } from 'vue'
import * as yup from 'yup'
import axios from 'axios'
import { useToast } from './useToast'
import { useValidation } from './useValidation'

const API = '/api/groups'

export default function useGroups() {
  const groups = ref([])
  const isLoading = ref(false)
  const toast = useToast()

  const initialGroup = {
    id: null,
    course_id: null,
    academic_year_id: null,
    tutor_id: null,
    course_level: 1,
    name: ''
  }

  const group = ref({ ...initialGroup })

  const {
    errors,
    validate,
    clearErrors,
    hasError,
    getError
  } = useValidation()

  const groupSchema = yup.object({
    course_id: yup.number().required('El ciclo es obligatorio').integer().positive(),
    academic_year_id: yup.number().required('El curso académico es obligatorio').integer().positive(),
    tutor_id: yup
      .mixed()
      .nullable()
      .test(
        'tutor-id',
        'El tutor no es válido',
        (v) =>
          v === null ||
          v === undefined ||
          v === '' ||
          (Number.isInteger(Number(v)) && Number(v) > 0)
      ),
    course_level: yup.number().required().integer().min(1, 'El nivel debe ser al menos 1'),
    name: yup.string().trim().required('El nombre es obligatorio').max(50)
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

  const resetGroup = () => {
    group.value = { ...initialGroup }
    clearErrors()
  }

  const setGroup = (data = {}) => {
    group.value = {
      id: data.id ?? null,
      course_id: data.course_id ?? null,
      academic_year_id: data.academic_year_id ?? null,
      tutor_id: data.tutor_id ?? null,
      course_level: data.course_level ?? 1,
      name: data.name ?? ''
    }
    clearErrors()
  }

  const upsertGroupRecord = (record) => {
    if (!record?.id) return
    groups.value = [record, ...groups.value.filter((g) => g.id !== record.id)]
  }

  const unwrap = (response) => response.data?.data ?? response.data

  const getGroups = async () => {
    try {
      const response = await withLoading(() => axios.get(API))
      const data = unwrap(response)
      groups.value = Array.isArray(data) ? data : []
      return response
    } catch (error) {
      toast.error('Error', 'No se pudieron cargar los grupos')
      throw error
    }
  }

  const getGroup = async (id) => {
    if (!id) return null
    try {
      const response = await withLoading(() => axios.get(`${API}/${id}`))
      const data = unwrap(response)
      setGroup(data)
      return data
    } catch (error) {
      toast.error('Error', 'No se pudo obtener el grupo')
      throw error
    }
  }

  const buildPayload = (data) => ({
    course_id: data.course_id,
    academic_year_id: data.academic_year_id,
    tutor_id: data.tutor_id || null,
    course_level: data.course_level,
    name: data.name
  })

  const createGroup = async (payload) => {
    const data = payload ?? group.value
    const { isValid } = validate(groupSchema, data)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }

    try {
      const response = await withLoading(() => axios.post(API, buildPayload(data)))
      const created = unwrap(response)
      toast.crud.created('Grupo')
      return created
    } catch (error) {
      toast.error('Error', 'No se pudo crear el grupo')
      throw error
    }
  }

  const updateGroup = async (payload) => {
    const data = payload ?? group.value
    const { isValid } = validate(groupSchema, data)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }

    try {
      const response = await withLoading(() =>
        axios.put(`${API}/${data.id}`, buildPayload(data))
      )
      const updated = unwrap(response)
      toast.crud.updated('Grupo')
      return updated
    } catch (error) {
      toast.error('Error', 'No se pudo actualizar el grupo')
      throw error
    }
  }

  const deleteGroup = async (id) => {
    try {
      await withLoading(() => axios.delete(`${API}/${id}`))
      groups.value = groups.value.filter((g) => g.id !== id)
      toast.crud.deleted('Grupo')
    } catch (error) {
      const message = error?.response?.data?.message || 'No se pudo eliminar el grupo'
      toast.error('Error', message)
      throw error
    }
  }

  return {
    groups,
    group,
    isLoading,
    errors,
    hasError,
    getError,
    resetGroup,
    setGroup,
    upsertGroupRecord,
    getGroups,
    getGroup,
    createGroup,
    updateGroup,
    deleteGroup
  }
}
