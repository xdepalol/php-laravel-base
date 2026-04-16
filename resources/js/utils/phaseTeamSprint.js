/** Etiquetas UI para {@link PhaseTeamSprintStatus} (valor entero API). */

export const SPRINT_STATUS_LABEL = {
  0: 'Asignando tareas',
  1: 'En desarrollo',
  2: 'Revisión',
  3: 'Retrospectiva',
  4: 'Sprint terminado',
}

/** Opciones para un `Select` de paso del sprint (valor API = entero del enum). */
export const SPRINT_STATUS_STEP_OPTIONS = [0, 1, 2, 3, 4].map((value) => ({
  value,
  label: SPRINT_STATUS_LABEL[value],
}))

/** Texto del botón para avanzar un paso (desde `value` actual). */
export const SPRINT_ADVANCE_BUTTON_LABEL = {
  4: 'Iniciar sprint',
  0: 'Pasar a desarrollo',
  1: 'Pasar a revisión',
  2: 'Pasar a retrospectiva',
  3: 'Finalizar sprint',
}

export function sprintStatusLabel(value) {
  const v = Number(value)
  return SPRINT_STATUS_LABEL[v] ?? `Estado #${v}`
}

function sprintStatusValueFromPhaseTeam(pt) {
  const v = pt?.sprint_status?.value
  return v !== undefined && v !== null ? Number(v) : 4
}

/**
 * Etiqueta para la columna «Sprint del equipo»: no confunde FINISHED sin snapshot con «terminado».
 */
export function sprintTeamStatusDisplayLabel(pt) {
  if (sprintNeverStartedForTeam(pt)) {
    return 'No iniciado'
  }
  return sprintStatusLabel(sprintStatusValueFromPhaseTeam(pt))
}

export function sprintAdvanceButtonLabel(value) {
  const v = Number(value)
  return SPRINT_ADVANCE_BUTTON_LABEL[v] ?? 'Siguiente'
}

/**
 * Comprueba si las tres cadenas de retrospectiva están rellenas (trim).
 * Orden UI: Keep doing → `retro_well`, Stop doing → `retro_bad`, Start doing → `retro_improvement`.
 * @param {{ retro_well?: string | null, retro_bad?: string | null, retro_improvement?: string | null }} o
 */
export function retroCompleteForFinish(o) {
  const w = (o?.retro_well ?? '').trim()
  const b = (o?.retro_bad ?? '').trim()
  const i = (o?.retro_improvement ?? '').trim()
  return w.length > 0 && b.length > 0 && i.length > 0
}

/** Valor API del siguiente `sprint_status` permitido (un paso). */
export function nextSprintStatusValue(current) {
  const v = Number(current)
  const map = { 4: 0, 0: 1, 1: 2, 2: 3, 3: 4 }
  return map[v] ?? null
}

/** Sprint cerrado al menos una vez (hay snapshot de cierre). */
export function sprintTeamFinishedOnce(pt) {
  return sprintStatusValueFromPhaseTeam(pt) === 4 && pt?.kanban_snapshot != null
}

/**
 * Equipo aún no ha iniciado este sprint en la fase: FINISHED y sin snapshot (no confundir con «cerrado»).
 */
export function sprintNeverStartedForTeam(pt) {
  return sprintStatusValueFromPhaseTeam(pt) === 4 && pt?.kanban_snapshot == null
}
