<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CertificationStatus;
use App\Enums\UserRole;
use App\Http\Requests\QaThread\IndexRequest;
use App\Http\Requests\QaThread\StoreThreadRequest;
use App\Http\Requests\QaThread\UpdateThreadRequest;
use App\Models\Certification;
use App\Models\QaThread;
use App\UseCases\QaThread\DestroyAction;
use App\UseCases\QaThread\IndexAction;
use App\UseCases\QaThread\ResolveAction;
use App\UseCases\QaThread\ShowAction;
use App\UseCases\QaThread\StoreAction;
use App\UseCases\QaThread\UnresolveAction;
use App\UseCases\QaThread\UpdateAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * 質問掲示板 Controller。受講生 / コーチ / admin 共通で利用される。
 * CRUD と解決状態の切り替え（resolve / unresolve）を提供する。
 *
 * `admin/qa-board/*` のモデレーション用ルートも index/show/destroy を共有する
 * （表示の出し分けは Blade 側の `request()->routeIs('admin.*')` で行う）。
 */
class QaThreadController extends Controller
{
    public function index(IndexRequest $request, IndexAction $action): View
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

        $threads = $action(
            $viewer,
            $filters['status'],
            $filters['certification_id'],
            $filters['keyword'],
        );

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

    public function store(StoreThreadRequest $request, StoreAction $action): RedirectResponse
    {
        $this->authorize('create', QaThread::class);

        $validated = $request->validated();
        $validated['user_id'] = auth()->id();

        $action($validated);

        return redirect()
            ->route('qa-board.index')
            ->with('success', '質問を投稿しました。');
    }

    public function show(QaThread $thread, ShowAction $action): View
    {
        $this->authorize('view', $thread);

        return view('qa-thread.show', ['thread' => $action($thread)]);
    }

    public function edit(QaThread $thread): View
    {
        $this->authorize('update', $thread);

        return view('qa-thread.edit', [
            'thread' => $thread,
        ]);
    }

    public function update(UpdateThreadRequest $request, QaThread $thread, UpdateAction $action): RedirectResponse
    {
        $this->authorize('update', $thread);

        $validated = $request->validated();

        $action($thread, $validated);

        return redirect()
            ->route('qa-board.index')
            ->with('success', '質問を更新しました。');
    }

    public function destroy(QaThread $thread, DestroyAction $action): RedirectResponse
    {
        $this->authorize('delete', $thread);

        $action($thread);

        return redirect()
            ->route('qa-board.index')
            ->with('success', '質問を削除しました。');
    }

    public function resolve(QaThread $thread, ResolveAction $action): RedirectResponse
    {
        $this->authorize('resolve', $thread);

        $action($thread);

        return redirect()
            ->route('qa-board.index')
            ->with('success', '質問の解決状況を「解決済」にしました。');
    }

    public function unresolve(QaThread $thread, UnresolveAction $action): RedirectResponse
    {
        $this->authorize('unresolve', $thread);

        $action($thread);

        return redirect()
            ->route('qa-board.index')
            ->with('success', '質問の解決状況を「未解決」に変更しました。');
    }
}
