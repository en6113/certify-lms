<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CertificationStatus;
use App\Enums\QaThreadStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 質問掲示板の質問スレッドを表す Model。
 *
 * 関連: User(受講生) / Certification
 * scope: certification() / status() / keyword()
 */
class QaThread extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'user_id',
        'certification_id',
        'title',
        'body',
        'status',
        'resolved_at',
    ];

    protected $casts = [
        'status' => QaThreadStatus::class,
        'resolved_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Certification, $this>
     */
    public function certification(): BelongsTo
    {
        return $this->belongsTo(Certification::class);
    }

    /**
     * @return HasMany<QaThreadReply, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(QaThreadReply::class);
    }

    public function scopeCertification(Builder $query, ?string $certificationId): Builder
    {
        if ($certificationId === null || $certificationId === '') {
            return $query;
        }

        return $query->where('certification_id', $certificationId);
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        if ($status === null || $status === '') {
            return $query;
        }

        $statusEnum = QaThreadStatus::tryFrom($status);

        return $statusEnum
            ? $query->where('status', $statusEnum->value)
            : $query;
    }

    public function scopeKeyword(Builder $query, ?string $keyword): Builder
    {
        if ($keyword === null || $keyword === '') {
            return $query;
        }

        return $query->where(function ($q) use ($keyword) {
            $q->where('body', 'LIKE', '%'.$keyword.'%');
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereHas(
            'certification',
            fn (Builder $q) => $q->where('status', CertificationStatus::Published->value),
        );
    }

    public function scopeAssignedTo(Builder $query, User $coach): Builder
    {
        return $query->whereHas(
            'certification.coaches',
            fn (Builder $q) => $q->where('users.id', $coach->id),
        );
    }

    /**
     * 操作者ロールに応じて一覧表示行を絞り込む scope。admin は全件、coach は担当資格のみ、studentは公開済資格すべて。
     * 3ロール共通の一覧画面 (`qa-thread.index`) で利用する。
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            UserRole::Admin => $query,
            UserRole::Coach => $query->assignedTo($user),
            UserRole::Student => $query->published(),
        };
    }
}
