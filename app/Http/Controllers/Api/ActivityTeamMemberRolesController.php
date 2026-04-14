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
     */
    public function __invoke(Activity $activity)
    {
        $this->authorize('team-edit');

        $query = ActivityRole::with('activityRoleType')->orderBy('position');
        if ($activity->activity_role_type_id) {
            $query->where('activity_role_type_id', $activity->activity_role_type_id);
        }

        return ActivityRoleResource::collection($query->get());
    }
}
