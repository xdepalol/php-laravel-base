<?php

namespace App\Http\Controllers\Api;

use App\Enums\DeliverableStatus;
use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Submission\StoreSubmissionRequest;
use App\Http\Requests\Submission\UpdateSubmissionRequest;
use App\Http\Resources\SubmissionResource;
use App\Models\Deliverable;
use App\Models\Submission;
use App\Models\Team;
use Illuminate\Http\Request;

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
     * Docentes: permiso submission-create y cuerpo completo.
     * Alumnos (sin rol docente): solo entregables publicados; deben pertenecer al equipo; estado entregada.
     */
    public function store(StoreSubmissionRequest $request, Deliverable $deliverable)
    {
        $deliverable->loadMissing('activity');

        if ($request->user()->can('submission-create')) {
            $this->authorize('submission-create');

            $submission = new Submission;
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
        } else {
            if (! $this->studentMayStoreSubmission($request, $deliverable)) {
                abort(403);
            }

            $submission = new Submission;
            $submission->deliverable_id = $deliverable->id;
            $submission->activity_id = $deliverable->activity_id;
            $submission->student_id = $request->user()->id;
            $submission->team_id = (int) $request->input('team_id');
            $submission->content_url = $request->filled('content_url') ? trim((string) $request->input('content_url')) : null;
            $submission->content_text = $request->filled('content_text') ? trim((string) $request->input('content_text')) : null;
            $submission->submitted_at = now();
            $submission->status = SubmissionStatus::SUBMITTED;
            $submission->grade = null;
            $submission->feedback = null;
        }

        if ($submission->save()) {
            $submission->load(['deliverable', 'activity', 'student.user', 'team']);
            $submission->setRelation('deliverable', $deliverable);

            return new SubmissionResource($submission);
        }
    }

    /**
     * Alumno sin rol docente: entregable publicado y pertenencia al equipo en la actividad.
     */
    private function studentMayStoreSubmission(Request $request, Deliverable $deliverable): bool
    {
        if ($deliverable->status !== DeliverableStatus::PUBLISHED) {
            return false;
        }

        $teamId = (int) $request->input('team_id');

        return Team::query()
            ->whereKey($teamId)
            ->where('activity_id', $deliverable->activity_id)
            ->whereHas('students', function ($q) use ($request) {
                $q->where('students.user_id', $request->user()->id);
            })
            ->exists();
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
        $deliverable->loadMissing('activity');

        if ($request->user()->can('submission-edit')) {
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
        } else {
            if (! $this->studentMayUpdateSubmission($request, $deliverable, $submission)) {
                abort(403);
            }

            $submission->content_url = $request->filled('content_url') ? trim((string) $request->input('content_url')) : null;
            $submission->content_text = $request->filled('content_text') ? trim((string) $request->input('content_text')) : null;
            $submission->submitted_at = now();
            if ($submission->status === SubmissionStatus::GRADED) {
                $submission->status = SubmissionStatus::SUBMITTED;
                $submission->grade = null;
                $submission->feedback = null;
            }
        }

        if ($submission->save()) {
            $submission->load(['deliverable', 'activity', 'student.user', 'team']);
            $submission->setRelation('deliverable', $deliverable);

            return new SubmissionResource($submission);
        }

        return null;
    }

    /**
     * Alumno: entregable no cerrado; la entrega pertenece al usuario o al equipo del usuario.
     */
    private function studentMayUpdateSubmission(Request $request, Deliverable $deliverable, Submission $submission): bool
    {
        if ((int) $submission->deliverable_id !== (int) $deliverable->id) {
            return false;
        }

        if ($deliverable->status === DeliverableStatus::CLOSED) {
            return false;
        }

        $userId = $request->user()->id;

        if ($deliverable->is_group_deliverable) {
            $teamId = (int) ($submission->team_id ?? 0);
            if ($teamId < 1) {
                return false;
            }

            return Team::query()
                ->whereKey($teamId)
                ->where('activity_id', $deliverable->activity_id)
                ->whereHas('students', function ($q) use ($userId) {
                    $q->where('students.user_id', $userId);
                })
                ->exists();
        }

        return (int) $submission->student_id === (int) $userId;
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
