<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BacklogItem\ReorderTeamBacklogItemsRequest;
use App\Http\Requests\BacklogItem\StoreActivityBacklogItemRequest;
use App\Http\Requests\BacklogItem\UpdateActivityBacklogItemRequest;
use App\Http\Resources\BacklogItemResource;
use App\Models\Activity;
use App\Models\BacklogItem;
use App\Support\ContentSanitizer;
use Illuminate\Support\Facades\DB;

class ActivityBacklogController extends Controller
{
    public function index(Activity $activity)
    {
        $this->authorize('backlogitem-list');

        $backlogItems = $activity->backlogItems()->with(['team', 'tasks'])->get();
        $backlogItems->each(fn (BacklogItem $b) => $b->setRelation('activity', $activity));

        return BacklogItemResource::collection($backlogItems);
    }

    /**
     * Reassign positions for team-owned backlog items (order = ids array order).
     */
    public function reorder(ReorderTeamBacklogItemsRequest $request, Activity $activity)
    {
        $this->authorize('backlogitem-edit');

        $teamId = (int) $request->validated('team_id');
        $ids = $request->validated('ids');

        $count = BacklogItem::query()
            ->where('activity_id', $activity->id)
            ->where('team_id', $teamId)
            ->whereIn('id', $ids)
            ->count();

        if ($count !== count($ids)) {
            abort(422, 'Los ítems no coinciden con el equipo o la actividad.');
        }

        DB::transaction(function () use ($activity, $teamId, $ids) {
            foreach ($ids as $index => $id) {
                BacklogItem::query()
                    ->where('activity_id', $activity->id)
                    ->where('team_id', $teamId)
                    ->whereKey($id)
                    ->update(['position' => $index]);
            }
        });

        return response()->json(['message' => 'OK']);
    }

    public function store(StoreActivityBacklogItemRequest $request, Activity $activity)
    {
        $this->authorize('backlogitem-create');

        $backlogItem = new BacklogItem;
        $backlogItem->activity_id = $activity->id;
        $backlogItem->team_id = $request->team_id;
        $backlogItem->title = $request->title;
        $backlogItem->description = ContentSanitizer::sanitize($request->input('description'));
        $backlogItem->priority = $request->priority;
        $backlogItem->points = $request->points;
        $backlogItem->status = $request->status;
        $backlogItem->position = $request->position ?? 0;
        if ($backlogItem->save()) {
            $backlogItem->load(['team', 'tasks']);
            $backlogItem->setRelation('activity', $activity);

            return new BacklogItemResource($backlogItem);
        }
    }

    public function show(Activity $activity, BacklogItem $backlogItem)
    {
        $this->authorize('backlogitem-view');

        $backlogItem->load(['team', 'tasks']);
        $backlogItem->setRelation('activity', $activity);

        return new BacklogItemResource($backlogItem);
    }

    public function update(UpdateActivityBacklogItemRequest $request, Activity $activity, BacklogItem $backlogItem)
    {
        $this->authorize('backlogitem-edit');

        $backlogItem->activity_id = $activity->id;
        $backlogItem->team_id = $request->team_id;
        $backlogItem->title = $request->title;
        $backlogItem->description = ContentSanitizer::sanitize($request->input('description'));
        $backlogItem->priority = $request->priority;
        $backlogItem->points = $request->points;
        $backlogItem->status = $request->status;
        $backlogItem->position = $request->position ?? 0;
        if ($backlogItem->save()) {
            $backlogItem->load(['team', 'tasks']);
            $backlogItem->setRelation('activity', $activity);

            return new BacklogItemResource($backlogItem);
        }

        return null;
    }

    public function destroy(Activity $activity, BacklogItem $backlogItem)
    {
        $this->authorize('backlogitem-delete');

        $backlogItem->load(['team', 'tasks']);
        $backlogItem->setRelation('activity', $activity);
        $backlogItem->delete();

        return new BacklogItemResource($backlogItem);
    }
}
