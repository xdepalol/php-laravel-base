/**
 * Etiqueta legible para un estudiante (API Student + user, o solo user).
 *
 * @param {{ user?: { name?: string, surname1?: string, surname2?: string, email?: string }, student_number?: string|null, user_id?: number } | null | undefined} student
 * @param {number|string|null} [fallbackStudentId] student_id / user_id
 * @returns {string}
 */
export function formatStudentDisplayName(student, fallbackStudentId = null) {
  const st = student && typeof student === 'object' ? student : null
  const u = st?.user
  if (u && typeof u === 'object') {
    const parts = [u.name, u.surname1, u.surname2].filter(Boolean)
    if (parts.length) return parts.join(' ')
    if (u.email) return String(u.email)
  }
  if (st?.student_number) return `N.º ${st.student_number}`
  const sid = st?.user_id ?? fallbackStudentId
  if (sid != null && sid !== '') return `Estudiante #${sid}`
  return '—'
}
