import { ref } from 'vue'
import * as yup from 'yup'
import { useToast } from './useToast'
import { useValidation } from './useValidation'
import axios from 'axios'

export default function useRoles() {
  const roles = ref([])
  const isLoading = ref(false)
  const toast = useToast()

  const initialRole = { 
    id: null, 
    name: '' 
  }

  const role = ref({ ...initialRole })
  const {
    errors,
    validate,
    clearErrors,
    hasError,
    getError
  } = useValidation()

  const roleSchema = yup.object({
    name: yup
      .string()
      .trim()
      .required('El nombre es obligatorio')
      .min(3, 'Debe tener al menos 3 caracteres')
  })

  // Helper para controlar loading
  const withLoading = async (fn) => {
    if (isLoading.value) throw new Error('Operación en curso')
    isLoading.value = true
    try {
      return await fn()
    } finally {
      isLoading.value = false
    }
  }

  const resetRole = () => { role.value = { ...initialRole }; clearErrors() }
  const setRole = (data = {}) => {
    role.value = { 
      id: data.id ?? null, 
      name: data.name ?? '' 
    }; 
    clearErrors() 
  }

  const getRole = async (id) => {
    if (!id) return null
    try {
      const response = await withLoading(() => axios.get(`/api/roles/${id}`))
      const data = response.data?.data ?? response.data
      setRole(data)
      return data
    } catch (error) {
      toast.error('Error', 'No se pudo obtener el rol')
      throw error
    }
  }

  const upsertRoleRecord = (roleRecord) => {
    if (!roleRecord?.id) return
    roles.value = [roleRecord, ...roles.value.filter(r => r.id !== roleRecord.id)]
  }

  const getRoles = (params = {}) => {
    const defaultParams = {
      page: 1,
      search_id: '',
      search_title: '',
      search_global: '',
      sort_field: 'created_at',
      sort_order: 'desc'
    }
    const query = new URLSearchParams({ ...defaultParams, ...params }).toString()
    return axios.get(`/api/roles?${query}`)
      .then(response => {
        roles.value = response.data.data
        return response
      })
  }

  // Crear role
  const createRole = async () => {
    const { isValid } = validate(roleSchema, role.value)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }

    try {
      const response = await withLoading(() => axios.post('/api/roles', { name: role.value.name }))
      const data = response.data?.data ?? response.data
      toast.crud.created('Rol')
      return data
    } catch (error) {
      toast.error('Error', 'No se pudo crear el rol')
      throw error
    }
  }

  // Actualizar role
  const updateRole = async () => {
    const { isValid } = validate(roleSchema, role.value)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }

    try {
      const response = await withLoading(() => axios.put(`/api/roles/${role.value.id}`, { name: role.value.name }))
      const data = response.data?.data ?? response.data
      toast.crud.updated('Rol')
      return data
    } catch (error) {
      toast.error('Error', 'No se pudo actualizar el rol')
      throw error
    }
  }

  // Eliminar role
  const deleteRole = async (id) => {
    try {
      const response = await withLoading(() => axios.delete(`/api/roles/${id}`))
      roles.value = roles.value.filter(roleItem => roleItem.id !== id)
      toast.crud.deleted('Rol')
      return response
    } catch (error) {
      const message = error?.response?.data?.message || 'No se pudo eliminar el rol'
      toast.error('Error', message)
      throw error
    }
  }

  return {
    roles,
    role,
    isLoading,
    errors,
    hasError,
    getError,
    upsertRoleRecord,
    resetRole,
    setRole,
    getRoles,
    getRole,
    createRole,
    updateRole,
    deleteRole,
  }
}
