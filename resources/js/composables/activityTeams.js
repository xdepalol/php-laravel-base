import { ref } from 'vue'
import * as yup from 'yup'
import axios from 'axios'
import { useToast } from './useToast'
import { useValidation } from './useValidation'

const teamsBase = (activityId) => `/api/activities/${activityId}/teams`
const studentsUrl = (activityId, teamId) =>
  `/api/activities/${activityId}/teams/${teamId}/students`
const teamMemberRolesUrl = (activityId) =>
  `/api/activities/${activityId}/team-member-roles`
const studentsAvailableForTeamsUrl = (activityId) =>
  `/api/activities/${activityId}/students-available-for-teams`

const teamSchema = yup.object({
  name: yup.string().trim().required('El nombre es obligatorio').max(255)
})

const syncStudentsSchema = yup.object({
  students: yup
    .array()
    .of(
      yup.object({
        student_id: yup.number().required().integer().positive(),
        activity_role_id: yup.number().nullable().integer().positive()
      })
    )
    .required()
})

export default function useActivityTeams() {
  const teams = ref([])
  const teamStudents = ref([])
  const isLoading = ref(false)
  const toast = useToast()

  const initialTeam = { id: null, name: '', students: [] }
  const team = ref({ ...initialTeam })

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

  const resetTeam = () => {
    team.value = { ...initialTeam }
    clearErrors()
  }

  const setTeam = (data = {}) => {
    team.value = {
      ...initialTeam,
      ...data,
      id: data.id ?? null,
      name: data.name ?? '',
    }
    clearErrors()
  }

  const upsertTeamRecord = (record) => {
    if (!record?.id) return
    teams.value = [record, ...teams.value.filter((t) => t.id !== record.id)]
  }

  const unwrap = (response) => response.data?.data ?? response.data

  const getTeams = async (activityId) => {
    if (!activityId) throw new Error('activityId requerido')
    try {
      const response = await withLoading(() => axios.get(teamsBase(activityId)))
      const data = unwrap(response)
      teams.value = Array.isArray(data) ? data : []
      return response
    } catch (error) {
      toast.error('Error', 'No se pudieron cargar los equipos')
      throw error
    }
  }

  /** Listado sin toast ni loading global (matrices, tablas auxiliares). */
  const fetchTeamsList = async (activityId) => {
    if (!activityId) return []
    try {
      const response = await axios.get(teamsBase(activityId))
      const data = unwrap(response)
      return Array.isArray(data) ? data : []
    } catch {
      return []
    }
  }

  /** Miembros del equipo sin toast ni loading global. */
  const fetchTeamStudentsList = async (activityId, teamId) => {
    if (!activityId || !teamId) return []
    try {
      const response = await axios.get(studentsUrl(activityId, teamId))
      const data = unwrap(response)
      return Array.isArray(data) ? data : []
    } catch {
      return []
    }
  }

  const getTeam = async (activityId, teamId) => {
    if (!activityId || !teamId) return null
    try {
      const response = await withLoading(() =>
        axios.get(`${teamsBase(activityId)}/${teamId}`)
      )
      const data = unwrap(response)
      setTeam(data)
      return data
    } catch (error) {
      toast.error('Error', 'No se pudo obtener el equipo')
      throw error
    }
  }

  const createTeam = async (activityId, payload) => {
    if (!activityId) throw new Error('activityId requerido')
    const data = payload ?? team.value
    const { isValid } = validate(teamSchema, data)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }
    try {
      const response = await withLoading(() =>
        axios.post(teamsBase(activityId), { name: data.name })
      )
      const created = unwrap(response)
      toast.crud.created('Equipo')
      return created
    } catch (error) {
      toast.error('Error', 'No se pudo crear el equipo')
      throw error
    }
  }

  const updateTeam = async (activityId, teamId, payload) => {
    if (!activityId || !teamId) throw new Error('IDs requeridos')
    const data = payload ?? team.value
    const { isValid } = validate(teamSchema, data)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa los campos resaltados.')
      throw new Error('Validación')
    }
    try {
      const response = await withLoading(() =>
        axios.put(`${teamsBase(activityId)}/${teamId}`, { name: data.name })
      )
      const updated = unwrap(response)
      toast.crud.updated('Equipo')
      return updated
    } catch (error) {
      toast.error('Error', 'No se pudo actualizar el equipo')
      throw error
    }
  }

  const deleteTeam = async (activityId, teamId) => {
    if (!activityId || !teamId) throw new Error('IDs requeridos')
    try {
      await withLoading(() => axios.delete(`${teamsBase(activityId)}/${teamId}`))
      teams.value = teams.value.filter((t) => t.id !== teamId)
      toast.crud.deleted('Equipo')
    } catch (error) {
      toast.error('Error', 'No se pudo eliminar el equipo')
      throw error
    }
  }

  const getTeamStudents = async (activityId, teamId) => {
    if (!activityId || !teamId) throw new Error('IDs requeridos')
    try {
      const response = await withLoading(() =>
        axios.get(studentsUrl(activityId, teamId))
      )
      const data = unwrap(response)
      teamStudents.value = Array.isArray(data) ? data : []
      return response
    } catch (error) {
      toast.error('Error', 'No se pudieron cargar los miembros del equipo')
      throw error
    }
  }

  /**
   * Roles de equipo para la actividad (sin loading global; permite varias llamadas en paralelo).
   */
  const getTeamMemberRoles = async (activityId) => {
    if (!activityId) throw new Error('activityId requerido')
    try {
      const response = await axios.get(teamMemberRolesUrl(activityId))
      const data = unwrap(response)
      return Array.isArray(data) ? data : []
    } catch (error) {
      toast.error('Error', 'No se pudieron cargar los roles de equipo')
      throw error
    }
  }

  /**
   * Estudiantes matriculados en la actividad que no están en ningún equipo (libres).
   */
  const getStudentsAvailableForTeams = async (activityId) => {
    if (!activityId) throw new Error('activityId requerido')
    try {
      const response = await axios.get(studentsAvailableForTeamsUrl(activityId))
      const data = unwrap(response)
      return Array.isArray(data) ? data : []
    } catch (error) {
      toast.error('Error', 'No se pudieron cargar los estudiantes disponibles')
      throw error
    }
  }

  /**
   * Miembros actuales del equipo (solo datos; no actualiza teamStudents ni loading global).
   */
  const getTeamStudentsList = async (activityId, teamId) => {
    if (!activityId || !teamId) throw new Error('IDs requeridos')
    try {
      const response = await axios.get(studentsUrl(activityId, teamId))
      const data = unwrap(response)
      return Array.isArray(data) ? data : []
    } catch (error) {
      toast.error('Error', 'No se pudieron cargar los miembros del equipo')
      throw error
    }
  }

  /** @param {{ student_id: number, activity_role_id?: number | null }[]} rows */
  const syncTeamStudents = async (activityId, teamId, rows) => {
    if (!activityId || !teamId) throw new Error('IDs requeridos')
    const payload = { students: rows ?? [] }
    const { isValid } = validate(syncStudentsSchema, payload)
    if (!isValid) {
      toast.error('Error de validación', 'Revisa estudiantes y roles.')
      throw new Error('Validación')
    }
    try {
      const response = await withLoading(() =>
        axios.put(studentsUrl(activityId, teamId), payload)
      )
      const data = unwrap(response)
      teamStudents.value = Array.isArray(data) ? data : []
      toast.crud.saved('Miembros del equipo')
      return response
    } catch (error) {
      toast.error('Error', 'No se pudo sincronizar los miembros del equipo')
      throw error
    }
  }

  return {
    teams,
    team,
    teamStudents,
    isLoading,
    errors,
    hasError,
    getError,
    resetTeam,
    setTeam,
    upsertTeamRecord,
    getTeams,
    fetchTeamsList,
    fetchTeamStudentsList,
    getTeam,
    createTeam,
    updateTeam,
    deleteTeam,
    getTeamStudents,
    getTeamMemberRoles,
    getStudentsAvailableForTeams,
    getTeamStudentsList,
    syncTeamStudents
  }
}
