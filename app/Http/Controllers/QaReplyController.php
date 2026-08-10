<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\QaThread\ReplyRequest;
use App\Models\QaReply;
use App\Models\QaThread;
use App\UseCases\QaReply\DestroyAction;
use App\UseCases\QaReply\StoreAction;
use App\UseCases\QaReply\UpdateAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * 質問掲示板の回答 Controller。受講生 / コーチ / admin 共通で利用される。
 */
class QaReplyController extends Controller
{
    public function store(ReplyRequest $request, QaThread $thread, StoreAction $action): RedirectResponse
    {
        $this->authorize('create', [QaReply::class, $thread]);

        $validated = $request->validated();
        $validated['qa_thread_id'] = $thread->id;
        $validated['reply_user_id'] = auth()->id();

        $action($validated);

        return redirect()
            ->route('qa-board.show', $thread)
            ->with('success', '回答を投稿しました。');
    }

    public function edit(QaThread $thread, QaReply $reply): View
    {
        $this->authorize('update', $reply);

        return view('qa-thread.reply-edit', [
            'thread' => $thread,
            'reply' => $reply,
        ]);
    }

    public function update(ReplyRequest $request, QaThread $thread, QaReply $reply, UpdateAction $action): RedirectResponse
    {
        $this->authorize('update', $reply);

        $validated = $request->validated();
        $action($reply, $validated);

        return redirect()
            ->route('qa-board.show', $thread)
            ->with('success', '回答を更新しました。');
    }

    public function destroy(QaThread $thread, QaReply $reply, DestroyAction $action): RedirectResponse
    {
        $this->authorize('delete', $reply);

        $action($reply);

        return redirect()
            ->route('qa-board.show', $thread)
            ->with('success', '回答を削除しました。');
    }
}
