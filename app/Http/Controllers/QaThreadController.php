<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CertificationStatus;
use App\Enums\QaThreadStatus;
use App\Enums\UserRole;
use App\Http\Requests\QaThread\IndexRequest;
use App\Http\Requests\QaThread\StoreThreadRequest;
use App\Http\Requests\QaThread\UpdateThreadRequest;
use App\Models\Certification;
use App\Models\QaThread;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * 質問掲示板 Controller。受講生 / コーチ / admin 共通で利用される。
 *
 * - index: 資格・解決状況・キーワードによる絞り込み,ロールによって閲覧できる資格スレッドが異なる。
 * - resolve/unresolve: 解決状況の変更
 */
class QaThreadController extends Controller
{
    public function index(IndexRequest $request): View
    {
        $this->authorize('viewAny', QaThread::class);

        $viewer = $request->user();
        $filters = $request->filters();

        $certifications = (match ($viewer->role) {
            UserRole::Admin => Certification::query(),
            UserRole::Coach => Certification::assignedTo($viewer),
            UserRole::Student => Certification::published(),
        })->get();

        $publishedStatus = CertificationStatus::Published;

        $threads = QaThread::with('certification')
            ->withCount('replies')
            ->forUser($viewer)
            ->status($filters['status'])
            ->certification($filters['certification_id'])
            ->keyword($filters['keyword'])
            ->latest()
            ->paginate(15);

        return view('qa-thread.index', [
            'filters' => $filters,
            'certifications' => $certifications,
            'publishedStatus' => $publishedStatus,
            'threads' => $threads,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', QaThread::class);

        $certifications = Certification::where('status', 'published')->get();

        return view('qa-thread.create', [
            'certifications' => $certifications,
        ]);
    }

    public function store(StoreThreadRequest $request): RedirectResponse
    {
        $this->authorize('create', QaThread::class);

        $validated = $request->validated();
        $validated['user_id'] = auth()->id();

        QaThread::create($validated);

        return redirect()
            ->route('qa-board.index')
            ->with('success', '質問を投稿しました。');
    }

    public function show(QaThread $thread): View
    {
        $this->authorize('view', $thread);

        $replies = $thread->replies()->with('user')->oldest()->get();

        return view('qa-thread.show', [
            'thread' => $thread,
            'replies' => $replies,
        ]);
    }

    public function edit(QaThread $thread): View
    {
        $this->authorize('update', $thread);

        return view('qa-thread.edit', [
            'thread' => $thread,
        ]);
    }

    public function update(UpdateThreadRequest $request, QaThread $thread): RedirectResponse
    {
        $this->authorize('update', $thread);

        $validated = $request->validated();

        $thread->update($validated);

        return redirect()
            ->route('qa-board.index')
            ->with('success', '質問を更新しました。');
    }

    public function destroy(QaThread $thread): RedirectResponse
    {
        $this->authorize('delete', $thread);

        $thread->delete();

        return redirect()
            ->route('qa-board.index')
            ->with('success', '質問を削除しました。');

    }

    public function resolve(QaThread $thread): RedirectResponse
    {
        $this->authorize('resolve', $thread);

        $thread->update([
            'status' => QaThreadStatus::Resolved,
            'resolved_at' => now(),
        ]);

        return redirect()
            ->route('qa-board.index')
            ->with('success', '質問の解決状況を「解決済」にしました。');
    }

    public function unresolve(QaThread $thread): RedirectResponse
    {
        $this->authorize('unresolve', $thread);

        $thread->update([
            'status' => QaThreadStatus::UnResolved,
            'resolved_at' => null,
        ]);

        return redirect()
            ->route('qa-board.index')
            ->with('success', '質問の解決状況を「未解決」に変更しました。');
    }
}
