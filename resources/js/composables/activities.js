import { ref } from 'vue'
import * as yup from 'yup'
import axios from 'axios'
import { useToast } from './useToast'
import { useValidation } from './useValidation'

const API = '/api/activities'

const ACTIVITY_TYPES = [0, 1, 2, 3, 4]
const ACTIVITY_STATUSES = [0, 1, 2]

function normalizeActivityFromApi(data) {
  if (!data) return null
  const typeVal = data.type?.value ?? data.type
  const statusVal = data.status?.value ?? data.status
  const sg = data.subject_groups
  const subjectGroupIds = Array.isArray(sg)
    ? sg.map((x) => (typeof x === 'object' && x !== null ? x.id : x))
    : []

  return {
    id: data.id ?? null,
    academic_year_id: data.academic_year_id ?? null,
    title: data.title ?? '',
    description: data.description ?? '',
    activity_role_type_id: data.activity_role_type_id ?? null,
    type: typeVal ?? 0,
    status: statusVal ?? 0,
    has_sprints: data.config?.has_sprints ?? data.has_sprints ?? false,
    has_backlog: data.config?.has_backlog ?? data.has_backlog ?? false,
    is_intermodular: data.config?.is_intermodular ?? data.is_intermodular ?? false,
    subject_groups: subjectGroupIds,
    start_date: data.dates?.start ?? data.start_date ?? null,
    end_date: data.dates?.end ?? data.end_date ?? null
  }
}

export default function useActivities() {
  const activities = ref([])
  const isLoading = ref(false)
  const toast = useToast()

  const initialActivity = {
    id: null,
    academic_year_id: null,
    title: '',
    description: '',
    activity_role_type_id: null,
    type: 0,
    status: 0,
    has_sprints: false,
    has_backlog: false,
    is_intermodular: false,
    subject_groups: [],
    start_date: null,
    end_date: null
  }

  const activity = ref({ ...initialActivity })

  const {
    errors,
    validate,
    clearErrors,
    hasError,
    getError
  } = useValidation()

  const activitySchema = yup.object({
    academic_year_id: yup.number().required('El curso académico es obligatorio').integer().positive(),
    title: yup.string().trim().required('El título es obligatorio').max(255),
    description: yup.string().nullable(),
    activity_role_type_id: yup.number().nullable().integer().positive(),
    type: yup
      .number()
      .required()
      .integer()
      .oneOf(ACTIVITY_TYPES, 'Tipo de actividad no válido'),
    status: yup
      .number()
      .required()
      .integer()
      .oneOf(ACTIVITY_STATUSES, 'Estado no válido'),
    has_sprints: yup.boolean(),
    has_backlog: yup.boolean(),
    is_intermodular: yup.boolean(),
    subject_groups: yup
      .array()
      .of(yup.number().integer().positive())
      .min(1, 'Selecciona al menos un grupo de asignatura'),
    start_date: yup.string().nullable(),
    end_date: yup
      .string()
      .nullable()
      .test(
        'after-or-equal-start',
        'La fecha de fin debe ser posterior o igual a la de inicio',
        function endAfterStart(v) {
          const start = this.parent.start_date
          if (!v || !start) return true
          return new Date(v) >= new Date(start)
        }
      )
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

  const resetActivity = () => {
    activity.value = { ...initialActivity }
    clearErrors()
  }

  const setActivity = (data = {}) => {
    const normalized = normalizeActivityFromApi(data) ?? { ...initialActivity }
    activity.value = normalized
    clearErrors()
  }

  const upsertActivityRecord = (record) => {
    if (!record?.id) return
    activities.value = [record, ...activities.value.filter((a) => a.id !== record.id)]
  }

  const unwrap = (response) => response.data?.data ?? response.data

  const buildPayload = (data) => ({
    academic_year_id: data.academic_year_id,
    title: data.title,
    description: data.description ?? '',
    activity_role_type_id: data.activity_role_type_id || null,
    type: data.type,
    status: data.status,
    has_sprints: !!data.has_sprints,
    has_backlog: !!data.has_backlog,
    is_intermodular: !!data.is_intermodular,
    subject_groups: Array.isArray(data.subject_groups)
      ? data.subject_groups.map((x) => (typeof x === 'object' && x !== null ? x.id : x))
      : [],
    start_date: data.start_date || null,
    end_date: data.end_date || null
  })

  /**
   * @param {object} [params]
   * @param {number} [params.academic_year_id]
   * @param {number} [params.status] ActivityStatus enum value
   * @param {number[]} [params.subject_group_ids]
   */
  const buildActivitiesQuery = (params = {}) => {
    const search = new URLSearchParams()
    if (params.academic_year_id != null && params.academic_year_id !== '') {
      search.append('academic_year_id', String(params.academic_year_id))
    }
    if (params.status != null && params.status !== '') {
      search.append('status', String(params.status))
    }
    if (Array.isArray(params.subject_group_ids) && params.subject_group_ids.length) {
      params.subject_group_ids.forEach((id) => {
        search.append('subject_group_ids[]', String(id))
      })
    }
    const qs = search.toString()
    return qs ? `${API}?${qs}` : API
  }

  const getActivities = async (params = {}) => {
    try {
      const url = buildActivitiesQuery(params)
      const response = await withLoading(() => axios.get(url))
      const data = unwrap(response)
      activities.value = Array.isArray(data) ? data : []
      return response
    } catch (error) {
      toast.error('Error', 'No se pudieron cargar las actividades')
      throw error
    }
  }

  /** Nested resource: activities for one subject group (server enforces access). */
  const getActivitiesForSubjectGroup = async (subjectGroupId) => {
    if (!subjectGroupId) {
      activities.value = []
      return null
    }
    try {
      const response = await withLoading(() =>
        axios.get(`/api/subject-groups/${subjectGroupId}/activities`)
      )
      const data = unwrap(response)
      activities.value = Array.isArray(data) ? data : []
      return response
    } catch (error) {
      toast.error('Error', 'No se pudieron cargar las actividades del grupo')
      throw error
    }
  }

  const getActivity = async (id) => {
    if (!id) return null
    try {
      const response = await withLoading(() => axios.get(`${API}/${id}`))
      const raw = unwrap(response)
      const normalized = normalizeActivityFromApi(raw)
      activity.value = normalized
      return normalized
    } catch (error) {
      toast.error('Error', 'No se pudo obtener la actividad')
      throw error
    }
  }

  const createActivity = async (payload) => {
    const data = payload ?? activity.value
    const { isValid } = validate(activitySchema, data)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }

    try {
      const response = await withLoading(() => axios.post(API, buildPayload(data)))
      const created = unwrap(response)
      toast.crud.created('Actividad')
      return created
    } catch (error) {
      toast.error('Error', 'No se pudo crear la actividad')
      throw error
    }
  }

  const updateActivity = async (payload) => {
    const data = payload ?? activity.value
    const { isValid } = validate(activitySchema, data)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }

    try {
      const response = await withLoading(() =>
        axios.put(`${API}/${data.id}`, buildPayload(data))
      )
      const updated = unwrap(response)
      toast.crud.updated('Actividad')
      return updated
    } catch (error) {
      toast.error('Error', 'No se pudo actualizar la actividad')
      throw error
    }
  }

  const deleteActivity = async (id) => {
    try {
      await withLoading(() => axios.delete(`${API}/${id}`))
      activities.value = activities.value.filter((a) => a.id !== id)
      toast.crud.deleted('Actividad')
    } catch (error) {
      const message = error?.response?.data?.message || 'No se pudo eliminar la actividad'
      toast.error('Error', message)
      throw error
    }
  }

  return {
    activities,
    activity,
    isLoading,
    errors,
    clearErrors,
    hasError,
    getError,
    resetActivity,
    setActivity,
    upsertActivityRecord,
    getActivities,
    getActivitiesForSubjectGroup,
    buildActivitiesQuery,
    getActivity,
    createActivity,
    updateActivity,
    deleteActivity,
    ACTIVITY_TYPES,
    ACTIVITY_STATUSES
  }
}
