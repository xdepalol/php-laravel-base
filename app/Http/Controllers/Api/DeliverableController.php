<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Deliverable\StoreDeliverableRequest;
use App\Http\Requests\Deliverable\UpdateDeliverableRequest;
use App\Http\Resources\DeliverableResource;
use App\Models\Activity;
use App\Models\Deliverable;

class DeliverableController extends Controller
{
    /**
     * Display a listing of deliverables for the activity.
     */
    public function index(Activity $activity)
    {
        $this->authorize('deliverable-list');

        $deliverables = $activity->deliverables()->get();
        $deliverables->each(fn (Deliverable $d) => $d->setRelation('activity', $activity));

        return DeliverableResource::collection($deliverables);
    }

    /**
     * Store a newly created deliverable for the activity.
     */
    public function store(StoreDeliverableRequest $request, Activity $activity)
    {
        $this->authorize('deliverable-create');

        $deliverable = new Deliverable;
        $deliverable->activity_id = $activity->id;
        $deliverable->title = $request->title;
        $deliverable->short_code = $request->short_code;
        $deliverable->description = $request->description;
        $deliverable->due_date = $request->due_date;
        $deliverable->status = $request->status;
        $deliverable->is_group_deliverable = $request->boolean('is_group_deliverable');
        if ($deliverable->save()) {
            $deliverable->setRelation('activity', $activity);

            return new DeliverableResource($deliverable);
        }
    }

    /**
     * Display the specified deliverable.
     */
    public function show(Activity $activity, Deliverable $deliverable)
    {
        $this->authorize('deliverable-view');

        $deliverable->setRelation('activity', $activity);

        return new DeliverableResource($deliverable);
    }

    /**
     * Update the specified deliverable.
     */
    public function update(UpdateDeliverableRequest $request, Activity $activity, Deliverable $deliverable)
    {
        $this->authorize('deliverable-edit');

        $deliverable->activity_id = $activity->id;
        $deliverable->title = $request->title;
        $deliverable->short_code = $request->short_code;
        $deliverable->description = $request->description;
        $deliverable->due_date = $request->due_date;
        $deliverable->status = $request->status;
        $deliverable->is_group_deliverable = $request->boolean('is_group_deliverable');
        if ($deliverable->save()) {
            $deliverable->setRelation('activity', $activity);

            return new DeliverableResource($deliverable);
        }

        return null;
    }

    /**
     * Remove the specified deliverable.
     */
    public function destroy(Activity $activity, Deliverable $deliverable)
    {
        $this->authorize('deliverable-delete');

        $deliverable->setRelation('activity', $activity);
        $deliverable->delete();

        return new DeliverableResource($deliverable);
    }
}
