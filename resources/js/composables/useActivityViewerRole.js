import { computed } from 'vue'
import { authStore } from '@/store/auth'

/**
 * Vista actividad: alumno sin rol docente (misma regla que en grupo de asignatura).
 */
export function useActivityViewerRole() {
  const isStudentOnlyView = computed(() => {
    const roles = authStore().user?.roles?.map((r) => r.name) ?? []
    return roles.includes('student') && !roles.includes('teacher')
  })

  const viewerUserId = computed(() => authStore().user?.id ?? null)

  const viewerDisplayName = computed(() => {
    const u = authStore().user
    if (!u) return ''
    const parts = [u.name, u.surname1, u.surname2].filter(Boolean)
    if (parts.length) return parts.join(' ')
    return u.email ? String(u.email) : ''
  })

  return { isStudentOnlyView, viewerUserId, viewerDisplayName }
}
