<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PhaseStudentRole\StorePhaseStudentRoleRequest;
use App\Http\Requests\PhaseStudentRole\UpdatePhaseStudentRoleRequest;
use App\Http\Resources\PhaseStudentRoleResource;
use App\Models\PhaseStudentRole;

class PhaseStudentRoleController extends Controller
{
    public function index()
    {
        $this->authorize('phasestudentrole-list');

        $rows = PhaseStudentRole::with(['phase', 'student.user', 'team', 'activityRole'])->get();

        return PhaseStudentRoleResource::collection($rows);
    }

    public function store(StorePhaseStudentRoleRequest $request)
    {
        $this->authorize('phasestudentrole-create');

        $row = new PhaseStudentRole();
        $row->phase_id = $request->phase_id;
        $row->student_id = $request->student_id;
        $row->team_id = $request->team_id;
        $row->activity_role_id = $request->activity_role_id;
        if ($row->save()) {
            $row->load(['phase', 'student.user', 'team', 'activityRole']);

            return new PhaseStudentRoleResource($row);
        }
    }

    public function show(PhaseStudentRole $phaseStudentRole)
    {
        $this->authorize('phasestudentrole-view');

        $phaseStudentRole->load(['phase', 'student.user', 'team', 'activityRole']);

        return new PhaseStudentRoleResource($phaseStudentRole);
    }

    public function update(UpdatePhaseStudentRoleRequest $request, PhaseStudentRole $phaseStudentRole)
    {
        $this->authorize('phasestudentrole-edit');

        $phaseStudentRole->phase_id = $request->phase_id;
        $phaseStudentRole->student_id = $request->student_id;
        $phaseStudentRole->team_id = $request->team_id;
        $phaseStudentRole->activity_role_id = $request->activity_role_id;
        if ($phaseStudentRole->save()) {
            $phaseStudentRole->load(['phase', 'student.user', 'team', 'activityRole']);

            return new PhaseStudentRoleResource($phaseStudentRole);
        }

        return null;
    }

    public function destroy(PhaseStudentRole $phaseStudentRole)
    {
        $this->authorize('phasestudentrole-delete');

        $phaseStudentRole->load(['phase', 'student.user', 'team', 'activityRole']);
        $phaseStudentRole->delete();

        return new PhaseStudentRoleResource($phaseStudentRole);
    }
}
