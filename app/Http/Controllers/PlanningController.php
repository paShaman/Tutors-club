<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Model\Lesson;
use App\Model\StudentTopic;
use App\Model\Topic;
use App\Model\TopicReview;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

final class PlanningController extends Controller
{
    /**
     * Planning page — topic/subtopic tree by subject.
     */
    public function index(): Response
    {
        $subjects = Lesson::LESSON_SUBJECTS;
        $selectedSubject = (string) request()->query('subject', $subjects[0] ?? '');

        if (!in_array($selectedSubject, $subjects, true)) {
            $selectedSubject = $subjects[0] ?? '';
        }

        return Inertia::render('Planning', [
            'subjects'        => $subjects,
            'selectedSubject' => $selectedSubject,
            'topicTree'       => Topic::treeForSubject((int) Auth::id(), $selectedSubject),
        ]);
    }

    /**
     * Add or rename a topic/subtopic.
     */
    public function edit(): RedirectResponse
    {
        $post = request()->post();

        $validator = Validator::make($post, [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $userId = (int) Auth::id();
        $name = trim((string) $post['name']);
        $topicId = !empty($post['topic_id']) ? (int) $post['topic_id'] : null;

        if ($topicId !== null) {
            $topic = Topic::where('user_id', $userId)
                ->where('is_deleted', 0)
                ->find($topicId);

            if (!$topic) {
                return back()->with('error', lng('error.edit_topic'));
            }

            $topic->name = $name;
            $result = $topic->save();
            $str = 'edit_topic';
        } else {
            $subject = (string) ($post['subject'] ?? '');
            $parentId = !empty($post['parent_id']) ? (int) $post['parent_id'] : null;

            if ($parentId !== null) {
                $parent = Topic::where('user_id', $userId)
                    ->where('is_deleted', 0)
                    ->find($parentId);

                // Поддерживаем два уровня: у подтемы не может быть своих подтем.
                if (!$parent || $parent->parent_id !== null) {
                    return back()->with('error', lng('error.add_topic'));
                }

                $subject = (string) $parent->subject;
            }

            if (!in_array($subject, Lesson::LESSON_SUBJECTS, true)) {
                return back()->with('error', lng('error.add_topic'));
            }

            $maxPosition = (int) Topic::where('user_id', $userId)
                ->where('subject', $subject)
                ->where('parent_id', $parentId)
                ->where('is_deleted', 0)
                ->max('position');

            $topic = new Topic([
                'user_id'    => $userId,
                'subject'    => $subject,
                'parent_id'  => $parentId,
                'name'       => $name,
                'position'   => $maxPosition + 1,
                'is_deleted' => 0,
            ]);

            $result = $topic->save();
            $str = 'add_topic';
        }

        if (empty($result)) {
            return back()->with('error', lng('error.' . $str));
        }

        return back()->with('success', lng('success.' . $str));
    }

    /**
     * Soft-delete a topic together with its subtopics.
     */
    public function delete(): RedirectResponse
    {
        $post = request()->post();

        $validator = Validator::make($post, [
            'topic_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $userId = (int) Auth::id();

        $topic = Topic::where('user_id', $userId)
            ->where('is_deleted', 0)
            ->find((int) $post['topic_id']);

        if (!$topic) {
            return back()->with('error', lng('error.delete_topic'));
        }

        Topic::where('user_id', $userId)
            ->where('parent_id', $topic->id)
            ->update(['is_deleted' => 1, 'updated_at' => Carbon::now()]);

        $topic->is_deleted = 1;
        $result = $topic->save();

        if (empty($result)) {
            return back()->with('error', lng('error.delete_topic'));
        }

        return back()->with('success', lng('success.delete_topic'));
    }

    /**
     * Persist the order of a sibling list after drag & drop.
     */
    public function reorder(): RedirectResponse
    {
        $post = request()->post();

        $validator = Validator::make($post, [
            'items'            => 'present|array',
            'items.*.id'       => 'required|integer',
            'items.*.position' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $userId = (int) Auth::id();

        foreach ($post['items'] as $item) {
            Topic::where('user_id', $userId)
                ->where('id', (int) $item['id'])
                ->update([
                    'position'   => (int) $item['position'],
                    'updated_at' => Carbon::now(),
                ]);
        }

        return back()->with('success', lng('success.reorder_topics'));
    }

    /**
     * Set the status of a topic for a student.
     */
    public function setStatus(): RedirectResponse
    {
        $post = request()->post();

        $validator = Validator::make($post, [
            'student_id' => 'required|integer',
            'topic_id'   => 'required|integer',
            'status'     => 'required|in:' . implode(',', StudentTopic::STATUSES),
            'mastered_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $studentId = (int) $post['student_id'];
        $topicId = (int) $post['topic_id'];

        if (!$this->ownsStudent($studentId) || !$this->ownsTopic($topicId)) {
            return back()->with('error', lng('error.no_access'));
        }

        $studentTopic = StudentTopic::firstOrNew([
            'student_id' => $studentId,
            'topic_id'   => $topicId,
        ]);

        $status = (string) $post['status'];
        $studentTopic->status = $status;

        if ($status === StudentTopic::STATUS_MASTERED) {
            if (!empty($post['mastered_at'])) {
                $studentTopic->mastered_at = $post['mastered_at'];
            } elseif (!$studentTopic->mastered_at) {
                $studentTopic->mastered_at = Carbon::now()->toDateString();
            }
        } elseif ($status === StudentTopic::STATUS_NOT_STARTED) {
            $studentTopic->mastered_at = null;
        }

        $result = $studentTopic->save();

        if (empty($result)) {
            return back()->with('error', lng('error.set_topic_status'));
        }

        return back()->with('success', lng('success.set_topic_status'));
    }

    /**
     * Log a topic review for a student.
     */
    public function review(): RedirectResponse
    {
        $post = request()->post();

        $validator = Validator::make($post, [
            'student_id'  => 'required|integer',
            'topic_id'    => 'required|integer',
            'reviewed_on' => 'required|date',
            'comment'     => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $studentId = (int) $post['student_id'];
        $topicId = (int) $post['topic_id'];
        $reviewedOn = (string) $post['reviewed_on'];

        if (!$this->ownsStudent($studentId) || !$this->ownsTopic($topicId)) {
            return back()->with('error', lng('error.no_access'));
        }

        TopicReview::create([
            'student_id'  => $studentId,
            'topic_id'    => $topicId,
            'reviewed_on' => $reviewedOn,
            'comment'     => $post['comment'] ?? null,
        ]);

        $studentTopic = StudentTopic::firstOrNew([
            'student_id' => $studentId,
            'topic_id'   => $topicId,
        ]);

        if (!$studentTopic->exists) {
            $studentTopic->status = StudentTopic::STATUS_NOT_STARTED;
        }

        $current = $studentTopic->last_reviewed_at
            ? $studentTopic->last_reviewed_at->toDateString()
            : null;

        if ($current === null || $reviewedOn > $current) {
            $studentTopic->last_reviewed_at = $reviewedOn;
        }

        $studentTopic->save();

        return back()->with('success', lng('success.review_topic'));
    }

    private function ownsStudent(int $studentId): bool
    {
        return Auth::user()->students()->where('students.id', $studentId)->exists();
    }

    private function ownsTopic(int $topicId): bool
    {
        return Topic::where('user_id', Auth::id())
            ->where('is_deleted', 0)
            ->where('id', $topicId)
            ->exists();
    }
}
