<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Submission\StoreSubmissionRequest;
use App\Http\Requests\Submission\UpdateSubmissionRequest;
use App\Http\Resources\SubmissionResource;
use App\Models\Activity;
use App\Models\Submission;

class SubmissionController extends Controller
{
    /**
     * Display a listing of submissions for the activity.
     */
    public function index(Activity $activity)
    {
        $this->authorize('submission-list');

        $submissions = $activity->submissions()
            ->with(['deliverable', 'activity', 'student.user', 'team'])
            ->get();

        return SubmissionResource::collection($submissions);
    }

    /**
     * Store a newly created submission for the activity.
     */
    public function store(StoreSubmissionRequest $request, Activity $activity)
    {
        $this->authorize('submission-create');

        $submission = new Submission();
        $submission->activity_id = $activity->id;
        $submission->deliverable_id = $request->deliverable_id;
        $submission->student_id = $request->student_id;
        $submission->team_id = $request->team_id;
        $submission->content_url = $request->content_url;
        $submission->content_text = $request->content_text;
        $submission->submitted_at = $request->submitted_at;
        $submission->status = $request->status;
        $submission->grade = $request->grade;
        $submission->feedback = $request->feedback;
        if ($submission->save()) {
            $submission->load(['deliverable', 'activity', 'student.user', 'team']);

            return new SubmissionResource($submission);
        }
    }

    /**
     * Display the specified submission.
     */
    public function show(Activity $activity, Submission $submission)
    {
        $this->authorize('submission-view');

        $submission->load(['deliverable', 'activity', 'student.user', 'team']);

        return new SubmissionResource($submission);
    }

    /**
     * Update the specified submission.
     */
    public function update(UpdateSubmissionRequest $request, Activity $activity, Submission $submission)
    {
        $this->authorize('submission-edit');

        $submission->activity_id = $activity->id;
        $submission->deliverable_id = $request->deliverable_id;
        $submission->student_id = $request->student_id;
        $submission->team_id = $request->team_id;
        $submission->content_url = $request->content_url;
        $submission->content_text = $request->content_text;
        $submission->submitted_at = $request->submitted_at;
        $submission->status = $request->status;
        $submission->grade = $request->grade;
        $submission->feedback = $request->feedback;
        if ($submission->save()) {
            $submission->load(['deliverable', 'activity', 'student.user', 'team']);

            return new SubmissionResource($submission);
        }

        return null;
    }

    /**
     * Remove the specified submission.
     */
    public function destroy(Activity $activity, Submission $submission)
    {
        $this->authorize('submission-delete');

        $submission->load(['deliverable', 'activity', 'student.user', 'team']);
        $submission->delete();

        return new SubmissionResource($submission);
    }
}
