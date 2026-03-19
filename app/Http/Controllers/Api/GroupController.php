<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Group\StoreGroupRequest;
use App\Http\Requests\Group\UpdateGroupRequest;
use App\Http\Resources\GroupResource;
use App\Models\Group;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    /**
     * Display a listing of the group.
     */
    public function index()
    {
        $this->authorize('group-list');

        $groups = Group::with(['tutor'])->get();
        return GroupResource::collection($groups);    }

    /**
     * Store a newly created group in storage.
     */
    public function store(StoreGroupRequest $request)
    {
        $this->authorize('group-create');
        $group = new Group();
        $group->course_id = $request->course_id;
        $group->academic_year_id = $request->academic_year_id;
        $group->tutor_id = $request->tutor_id;
        $group->course_level = $request->course_level;
        $group->name = $request->name;
        if ($group->save()) {
            return new GroupResource($group);
        }
    }

    /**
     * Display the specified group.
     */
    public function show(Group $group)
    {
        $this->authorize('group-view');
        $group->load('tutor');
        return new GroupResource($group);
    }

    /**
     * Update the specified group in storage.
     */
    public function update(UpdateGroupRequest $request, Group $group)
    {
        $this->authorize('group-edit');

        $group->course_id = $request->course_id;
        $group->academic_year_id = $request->academic_year_id;
        $group->tutor_id = $request->tutor_id;
        $group->course_level = $request->course_level;
        $group->name = $request->name;
        if ($group->save()) {
            return new GroupResource($group);
        }
        return null;
    }

    /**
     * Remove the specified group from storage.
     */
    public function destroy(Group $group)
    {
        $this->authorize('group-delete');

        $group->delete();
        return new GroupResource($group);
    }
}
