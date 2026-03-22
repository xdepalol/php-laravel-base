<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Deliverable\StoreDeliverableRequest;
use App\Http\Requests\Deliverable\UpdateDeliverableRequest;
use App\Http\Resources\DeliverableResource;
use App\Models\Deliverable;

class DeliverableController extends Controller
{
    /**
     * Display a listing of the deliverable.
     */
    public function index()
    {
        $this->authorize('deliverable-list');

        $deliverables = Deliverable::with('activity')->get();

        return DeliverableResource::collection($deliverables);
    }

    /**
     * Store a newly created deliverable in storage.
     */
    public function store(StoreDeliverableRequest $request)
    {
        $this->authorize('deliverable-create');

        $deliverable = new Deliverable();
        $deliverable->activity_id = $request->activity_id;
        $deliverable->title = $request->title;
        $deliverable->description = $request->description;
        $deliverable->due_date = $request->due_date;
        $deliverable->status = $request->status;
        $deliverable->is_group_deliverable = $request->boolean('is_group_deliverable');
        if ($deliverable->save()) {
            $deliverable->load('activity');

            return new DeliverableResource($deliverable);
        }
    }

    /**
     * Display the specified deliverable.
     */
    public function show(Deliverable $deliverable)
    {
        $this->authorize('deliverable-view');

        $deliverable->load('activity');

        return new DeliverableResource($deliverable);
    }

    /**
     * Update the specified deliverable in storage.
     */
    public function update(UpdateDeliverableRequest $request, Deliverable $deliverable)
    {
        $this->authorize('deliverable-edit');

        $deliverable->activity_id = $request->activity_id;
        $deliverable->title = $request->title;
        $deliverable->description = $request->description;
        $deliverable->due_date = $request->due_date;
        $deliverable->status = $request->status;
        $deliverable->is_group_deliverable = $request->boolean('is_group_deliverable');
        if ($deliverable->save()) {
            $deliverable->load('activity');

            return new DeliverableResource($deliverable);
        }

        return null;
    }

    /**
     * Remove the specified deliverable from storage.
     */
    public function destroy(Deliverable $deliverable)
    {
        $this->authorize('deliverable-delete');

        $deliverable->load('activity');
        $deliverable->delete();

        return new DeliverableResource($deliverable);
    }
}
