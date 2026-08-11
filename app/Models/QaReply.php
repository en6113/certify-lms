<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 質問掲示板の質問スレッドへの回答を表す Model。
 *
 * 関連: QaThread(親、必須) / User(受講生、コーチ)
 * SoftDelete は採用しない(物理削除前提)。
 */
class QaReply extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'qa_thread_id',
        'reply_user_id',
        'body',
    ];

    /**
     * @return BelongsTo<QaThread, $this>
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(QaThread::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reply_user_id');
    }
}
