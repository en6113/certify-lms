<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CertificationStatus;
use App\Enums\QaThreadStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Certification;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * 質問掲示板の demoデータ シーダー。
 *
 * 状態網羅 + 固定アカウントの 2 軸で投入する:
 *
 * 1. 固定アカウント `student@certify-lms.test`:
 *    公開中の資格に質問スレッドを1~2件投入し、回答あり/回答なし、未解決/解決済を混在させる。
 *    「自分の質問」一覧や解決マークの動線確認用素材とする。
 *
 * 2．状態網羅 demoデータ: 公開中の資格に対し、2~5件の質問スレッドを投入　+ 回答数0~2件を投入。
 *    スレッド一覧画面の絞り込み・並び順・ページネーション・削除の動作を確認するため、未解決・解決済を混在させ、作成日時をばらつかせる。
 *
 * 依存順序: `UserSeeder` → `CertificationSeeder`(担当コーチ割当含む)→ 本 Seeder。
 */
class QaThreadSeeder extends Seeder
{
    public function run(): void
    {
        $publishedCertifications = Certification::query()
            ->where('status', CertificationStatus::Published->value)
            ->orderBy('created_at')
            ->with('coaches')
            ->get();

        if ($publishedCertifications->isEmpty()) {
            $this->command?->warn('QaThreadSeeder: 公開済資格がありません。先に CertificationSeeder を実行してください。');

            return;
        }

        $fixedStudent = User::query()->where('email', 'student@certify-lms.test')->first();
        // UserSeeder で投入される in_progress 受講生 demo の件数(8 件)に合わせて取得。
        // 固定 student は別ハンドリングするので whereNotIn で除外し、残り 8 件を循環パターンに割当てる。
        $demoStudents = User::query()
            ->where('role', UserRole::Student->value)
            ->where('status', UserStatus::InProgress->value)
            ->whereNotIn('email', ['student@certify-lms.test'])
            ->limit(8)
            ->get();

        if ($fixedStudent === null || $demoStudents->isEmpty()) {
            $this->command?->warn('QaThreadSeeder: 受講生が存在しません。先に UserSeeder を実行してください。');

            return;
        }

        if ($fixedStudent !== null) {
            $this->seedForFixedStudentThread($fixedStudent, $publishedCertifications);
        }
        $this->seedForDemoThread($demoStudents, $publishedCertifications);
    }

    private function seedForFixedStudentThread(User $fixedStudent, Collection $publishedCertifications): void
    {
        $certificationIds = $publishedCertifications->pluck('id');

        $data = [
            [
                'certification_id' => $certificationIds[0],
                'title' => '○○についての質問',
                'body' => '○○は○○という理解で問題ないでしょうか？',
                'minutes_ago' => 200,
                'reply_body' => 'ほぼ問題ないですが、○○も考慮できるとよいと思います。○○が参考になります。',
                'status' => QaThreadStatus::Resolved->value,
                'resolved_minutes_ago' => 150,
            ],
            [
                'certification_id' => $certificationIds[0],
                'title' => '××についての質問',
                'body' => '××は××というのがわかりません。',
                'minutes_ago' => 180,
            ],
            [
                'certification_id' => $certificationIds[1],
                'title' => '△△についての質問',
                'body' => '○○は○○とはどういう意味でしょうか？',
                'minutes_ago' => 160,
                'reply_body' => '○○は○○なので、○○という意味です。',
            ],
            [
                'certification_id' => $certificationIds[1],
                'title' => '××について',
                'body' => '××は（キーワード）になるのはどうしてでしょうか。',
                'minutes_ago' => 140,
            ],
            [
                'certification_id' => $certificationIds[2],
                'title' => '○○について',
                'body' => '○○は○○という理解で問題ないでしょうか？',
                'minutes_ago' => 120,
                'reply_body' => 'ほぼ問題ないですが、○○も考慮できるとよいと思います。○○が参考になります。',
                'status' => QaThreadStatus::Resolved->value,
                'resolved_minutes_ago' => 70,
            ],
            [
                'certification_id' => $certificationIds[3],
                'title' => '△△について',
                'body' => '××は（キーワード）というのがわかりません。',
                'minutes_ago' => 100,
            ],
        ];

        foreach ($data as $row) {
            $createdAt = Carbon::now()->subMinutes($row['minutes_ago']);
            $repliedAt = Carbon::now()->subMinutes($row['minutes_ago'] - 30);

            $thread = QaThread::create([
                'user_id' => $fixedStudent->id,
                'certification_id' => $row['certification_id'],
                'title' => $row['title'],
                'body' => $row['body'],
                'status' => $row['status'] ?? QaThreadStatus::UnResolved->value,
            ]);
            $thread->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();

            if (isset($row['reply_body'])) {
                $certification = $publishedCertifications->firstWhere('id', $row['certification_id']);
                $coach = $certification?->coaches->first();
                if ($coach === null) {
                    continue;
                }

                $reply = QaReply::create([
                    'qa_thread_id' => $thread->id,
                    'reply_user_id' => $coach->id,
                    'body' => $row['reply_body'],
                ]);
                $reply->forceFill(['created_at' => $repliedAt, 'updated_at' => $repliedAt])->save();
            }

            if ($row['status'] ?? QaThreadStatus::UnResolved->value === QaThreadStatus::Resolved->value) {
                $resolvedAt = Carbon::now()->subMinutes($row['resolved_minutes_ago']);

                $thread->forceFill(['resolved_at' => $resolvedAt])->save();
            }
        }
    }

    private function seedForDemoThread(Collection $demoStudents, Collection $publishedCertifications): void
    {
        foreach ($publishedCertifications as $certIndex => $certification) {
            // 各資格に2~5件の質問スレッドを投稿する
            $threadCount = rand(2, 5);
            $coach = $certification->coaches->first();

            for ($i = 0; $i < $threadCount; $i++) {
                $student = $demoStudents->random();

                $createdAt = Carbon::now()->subDays(rand(1, 60));

                $thread = QaThread::create([
                    'user_id' => $student->id,
                    'certification_id' => $certification->id,
                    'title' => '○○について',
                    'body' => fake()->realText(50),
                    'status' => QaThreadStatus::UnResolved->value,
                ]);
                $thread->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();

                // 各質問スレッドに0~2件の回答を作成する
                $replyCount = rand(0, 2);
                $lastReplyAt = null;

                for ($j = 0; $j < $replyCount; $j++) {
                    $replyAt = $createdAt->copy()->addMinutes(rand(30, 600));
                    if ($coach === null) {
                        continue;
                    }

                    $reply = QaReply::create([
                        'qa_thread_id' => $thread->id,
                        'reply_user_id' => $coach->id,
                        'body' => fake()->realText(250),
                    ]);
                    $reply->forceFill(['created_at' => $replyAt, 'updated_at' => $replyAt])->save();

                    $lastReplyAt = $replyAt;
                }

                // 回答つきのスレッドのうち、90パーセントを解決済にする
                $shouldResolve = rand(1, 100) > 10;
                if ($lastReplyAt !== null && $shouldResolve) {
                    $thread->forceFill([
                        'status' => QaThreadStatus::Resolved->value,
                        'resolved_at' => $lastReplyAt,
                    ])->save();
                }
            }
        }
    }
}
