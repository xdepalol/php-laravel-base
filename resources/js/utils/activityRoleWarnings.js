/**
 * Avisos no bloqueantes cuando no se cumplen is_mandatory / max_per_team.
 *
 * @param {Array<{ activity_role_id?: number | null, activity_role?: { id?: number } }>} assignments
 * @param {Array<{ id: number, name?: string, is_mandatory?: boolean, max_per_team?: number }>} roleDefinitions
 * @returns {string[]}
 */
export function activityRoleAssignmentWarnings(assignments, roleDefinitions) {
  const warnings = []
  const defs = (roleDefinitions || []).filter((r) => r && r.id != null)
  if (!defs.length) return warnings

  const countsByRoleId = new Map()
  for (const row of assignments || []) {
    const rid = row.activity_role_id ?? row.activity_role?.id
    if (rid == null || Number.isNaN(Number(rid))) continue
    const n = Number(rid)
    countsByRoleId.set(n, (countsByRoleId.get(n) || 0) + 1)
  }

  for (const def of defs) {
    const id = Number(def.id)
    const count = countsByRoleId.get(id) || 0
    const label = def.name || `Rol #${id}`

    if (def.is_mandatory && count < 1) {
      warnings.push(`Falta al menos un miembro con el rol «${label}» (marcado como obligatorio).`)
    }

    const max = def.max_per_team
    if (max != null && Number(max) > 0 && count > Number(max)) {
      warnings.push(
        `El rol «${label}» tiene ${count} asignación(es); el máximo recomendado es ${max}.`
      )
    }
  }

  return warnings
}
