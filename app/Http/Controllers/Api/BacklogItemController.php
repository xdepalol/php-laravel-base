<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BacklogItem\StoreBacklogItemRequest;
use App\Http\Requests\BacklogItem\UpdateBacklogItemRequest;
use App\Http\Resources\BacklogItemResource;
use App\Models\BacklogItem;

class BacklogItemController extends Controller
{
    public function index()
    {
        $this->authorize('backlogitem-list');

        $backlogItems = BacklogItem::with(['activity', 'team', 'tasks'])->get();

        return BacklogItemResource::collection($backlogItems);
    }

    public function store(StoreBacklogItemRequest $request)
    {
        $this->authorize('backlogitem-create');

        $backlogItem = new BacklogItem();
        $backlogItem->activity_id = $request->activity_id;
        $backlogItem->team_id = $request->team_id;
        $backlogItem->title = $request->title;
        $backlogItem->description = $request->description;
        $backlogItem->priority = $request->priority;
        $backlogItem->points = $request->points;
        $backlogItem->status = $request->status;
        $backlogItem->position = $request->position ?? 0;
        if ($backlogItem->save()) {
            $backlogItem->load(['activity', 'team', 'tasks']);

            return new BacklogItemResource($backlogItem);
        }
    }

    public function show(BacklogItem $backlogItem)
    {
        $this->authorize('backlogitem-view');

        $backlogItem->load(['activity', 'team', 'tasks']);

        return new BacklogItemResource($backlogItem);
    }

    public function update(UpdateBacklogItemRequest $request, BacklogItem $backlogItem)
    {
        $this->authorize('backlogitem-edit');

        $backlogItem->activity_id = $request->activity_id;
        $backlogItem->team_id = $request->team_id;
        $backlogItem->title = $request->title;
        $backlogItem->description = $request->description;
        $backlogItem->priority = $request->priority;
        $backlogItem->points = $request->points;
        $backlogItem->status = $request->status;
        $backlogItem->position = $request->position ?? 0;
        if ($backlogItem->save()) {
            $backlogItem->load(['activity', 'team', 'tasks']);

            return new BacklogItemResource($backlogItem);
        }

        return null;
    }

    public function destroy(BacklogItem $backlogItem)
    {
        $this->authorize('backlogitem-delete');

        $backlogItem->load(['activity', 'team', 'tasks']);
        $backlogItem->delete();

        return new BacklogItemResource($backlogItem);
    }
}
