<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Submission\StoreSubmissionRequest;
use App\Http\Requests\Submission\UpdateSubmissionRequest;
use App\Http\Resources\SubmissionResource;
use App\Enums\SubmissionStatus;
use App\Models\Deliverable;
use App\Models\Submission;

class SubmissionController extends Controller
{
    /**
     * Submissions for this deliverable.
     */
    public function index(Deliverable $deliverable)
    {
        $this->authorize('submission-list');

        $submissions = $deliverable->submissions()
            ->with(['deliverable', 'activity', 'student.user', 'team'])
            ->get();
        $submissions->each(fn (Submission $s) => $s->setRelation('deliverable', $deliverable));

        return SubmissionResource::collection($submissions);
    }

    /**
     * Store a submission for this deliverable.
     */
    public function store(StoreSubmissionRequest $request, Deliverable $deliverable)
    {
        $this->authorize('submission-create');

        $submission = new Submission();
        $submission->deliverable_id = $deliverable->id;
        $submission->activity_id = $deliverable->activity_id;
        $submission->student_id = $request->student_id;
        $submission->team_id = $request->team_id;
        $submission->content_url = $request->content_url;
        $submission->content_text = $request->content_text;
        $submission->submitted_at = $request->submitted_at;
        $submission->status = $request->input('status') ?? SubmissionStatus::PENDING;
        $submission->grade = $request->grade;
        $submission->feedback = $request->feedback;
        if ($submission->save()) {
            $submission->load(['deliverable', 'activity', 'student.user', 'team']);
            $submission->setRelation('deliverable', $deliverable);

            return new SubmissionResource($submission);
        }
    }

    public function show(Deliverable $deliverable, Submission $submission)
    {
        $this->authorize('submission-view');

        $submission->load(['deliverable', 'activity', 'student.user', 'team']);
        $submission->setRelation('deliverable', $deliverable);

        return new SubmissionResource($submission);
    }

    public function update(UpdateSubmissionRequest $request, Deliverable $deliverable, Submission $submission)
    {
        $this->authorize('submission-edit');

        $submission->deliverable_id = $deliverable->id;
        $submission->activity_id = $deliverable->activity_id;
        $submission->student_id = $request->student_id;
        $submission->team_id = $request->team_id;
        $submission->content_url = $request->content_url;
        $submission->content_text = $request->content_text;
        $submission->submitted_at = $request->submitted_at;
        if ($request->exists('status')) {
            $submission->status = $request->input('status') ?? SubmissionStatus::PENDING;
        }
        $submission->grade = $request->grade;
        $submission->feedback = $request->feedback;
        if ($submission->save()) {
            $submission->load(['deliverable', 'activity', 'student.user', 'team']);
            $submission->setRelation('deliverable', $deliverable);

            return new SubmissionResource($submission);
        }

        return null;
    }

    public function destroy(Deliverable $deliverable, Submission $submission)
    {
        $this->authorize('submission-delete');

        $submission->load(['deliverable', 'activity', 'student.user', 'team']);
        $submission->setRelation('deliverable', $deliverable);
        $submission->delete();

        return new SubmissionResource($submission);
    }
}
