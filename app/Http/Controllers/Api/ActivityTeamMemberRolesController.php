<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityRoleResource;
use App\Models\Activity;
use App\Models\ActivityRole;

class ActivityTeamMemberRolesController extends Controller
{
    /**
     * Roles de equipo disponibles para esta actividad (filtrados por el tipo de rol de la actividad, si existe).
     * Lectura con team-view: alumnos necesitan la lista para ver etiquetas en el resumen del equipo; la edición de miembros sigue protegida por team-edit en sync.
     */
    public function __invoke(Activity $activity)
    {
        $this->authorize('team-view');

        $query = ActivityRole::with('activityRoleType')->orderBy('position');
        if ($activity->activity_role_type_id) {
            $query->where('activity_role_type_id', $activity->activity_role_type_id);
        }

        return ActivityRoleResource::collection($query->get());
    }
}
