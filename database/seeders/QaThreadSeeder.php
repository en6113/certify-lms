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
 * 2．状態網羅 demoデータ: 公開中の資格に対し、3~5件の質問スレッドを投入　+ 回答数0~2件を投入。
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
        $this->seedForUnpublishedCertifications($demoStudents);
    }

    private function seedForFixedStudentThread(User $fixedStudent, Collection $publishedCertifications): void
    {
        $certificationIds = $publishedCertifications->pluck('id');

        $data = [
            [
                'certification_id' => $certificationIds[0], // 基本情報技術者試験
                'title' => 'ハッシュ探索の衝突処理について',
                'body' => 'オープンアドレス法とチェイン法、それぞれのメリット・デメリットが分かりません。試験対策としてはどちらを優先して覚えるべきでしょうか？',
                'minutes_ago' => 200,
                'reply_body' => 'チェイン法は実装がシンプルで衝突が増えてもリストを伸ばすだけで対応できます。オープンアドレス法はメモリ効率が良い反面、衝突が増えると探索性能が落ちやすい点が違いです。試験ではどちらも図解付きで出るので、両方の探索手順を追えるようにしておくと安心です。',
                'status' => QaThreadStatus::Resolved->value,
                'resolved_minutes_ago' => 150,
            ],
            [
                'certification_id' => $certificationIds[0], // 基本情報技術者試験
                'title' => '関係データベースの外部キー制約について',
                'body' => '親テーブルの行を削除しようとしたときに、子テーブルに参照がある場合はCASCADEとRESTRICTのどちらを選ぶべきか判断基準が分かりません。',
                'minutes_ago' => 180,
            ],
            [
                'certification_id' => $certificationIds[1], // 応用情報技術者試験
                'title' => 'アジャイル開発のベロシティの使い方について',
                'body' => 'スプリントごとのベロシティが安定しないチームの場合、次スプリントの計画にどう反映すればよいでしょうか？',
                'minutes_ago' => 160,
                'reply_body' => '直近3〜4スプリント分の平均値を使うと、単発の異常値に引っ張られにくくなります。ベロシティのブレが大きい場合は、タスクの粒度が揃っていない可能性もあるので、見積もり基準の見直しも合わせて検討してみてください。',
            ],
            [
                'certification_id' => $certificationIds[1], // 応用情報技術者試験
                'title' => '待ち行列理論(M/M/1)の午後問題対策について',
                'body' => '公式は覚えたのですが、文章題からλ(到着率)とμ(サービス率)をどう読み取ればよいのか、いつも迷ってしまいます。',
                'minutes_ago' => 140,
            ],
            [
                'certification_id' => $certificationIds[2], // TOEIC L&R 800点コース
                'title' => 'Part2の応答問題で毎回同じ選択肢に引っかかります',
                'body' => 'WH疑問文なのにYes/Noで返す選択肢を選んでしまうミスが多いです。本番で瞬時に見分けるコツはありますか？',
                'minutes_ago' => 120,
                'reply_body' => '疑問文の最初の1語(What/When/Whereなど)を聞き取れたら、その時点でYes/No系の選択肢は消去できます。文全体を聞き取ろうとせず、最初の1語に集中する練習をすると精度が上がります。',
                'status' => QaThreadStatus::Resolved->value,
                'resolved_minutes_ago' => 70,
            ],
            [
                'certification_id' => $certificationIds[3], // 日商簿記2級
                'title' => '本支店会計の内部利益控除について',
                'body' => '本店から支店へ振替価格で商品を送った場合、決算時に内部利益をどのタイミングで控除すればよいのか整理できていません。',
                'minutes_ago' => 100,
            ],
        ];

        foreach ($data as $row) {
            $createdAt = Carbon::now()->subMinutes($row['minutes_ago']);
            $repliedAt = Carbon::now()->subMinutes($row['minutes_ago'] - 30);
            $status = $row['status'] ?? QaThreadStatus::Open->value;

            $thread = QaThread::create([
                'user_id' => $fixedStudent->id,
                'certification_id' => $row['certification_id'],
                'title' => $row['title'],
                'body' => $row['body'],
                'status' => $status,
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

            if ($status === QaThreadStatus::Resolved->value) {
                $resolvedAt = Carbon::now()->subMinutes($row['resolved_minutes_ago']);

                $thread->forceFill(['resolved_at' => $resolvedAt])->save();
            }
        }
    }

    private function seedForDemoThread(Collection $demoStudents, Collection $publishedCertifications): void
    {
        $qaTemplates = $this->qaTemplates();

        foreach ($publishedCertifications as $certIndex => $certification) {
            $templates = $qaTemplates[$certification->name] ?? null;

            // 各資格に3~5件の質問スレッドを投稿する
            $threadCount = rand(3, 5);
            $coach = $certification->coaches->first();

            for ($i = 0; $i < $threadCount; $i++) {
                $student = $demoStudents->random();

                $createdAt = Carbon::now()->subDays(rand(1, 60));

                $question = $templates
                    ? $templates['questions'][array_rand($templates['questions'])]
                    : ['title' => '○○について', 'body' => fake()->realText(50)];

                $thread = QaThread::create([
                    'user_id' => $student->id,
                    'certification_id' => $certification->id,
                    'title' => $question['title'],
                    'body' => $question['body'],
                    'status' => QaThreadStatus::Open->value,
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

                    $replyBody = $templates
                        ? $templates['replies'][array_rand($templates['replies'])]
                        : fake()->realText(250);

                    $reply = QaReply::create([
                        'qa_thread_id' => $thread->id,
                        'reply_user_id' => $coach->id,
                        'body' => $replyBody,
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

    /**
     * 管理者モデレーション画面で「公開停止中の資格を含む全資格」を横断確認できるよう、
     * draft / archived 資格にも質問と回答を1件ずつ仕込む。
     */
    private function seedForUnpublishedCertifications(Collection $demoStudents): void
    {
        $unpublishedCertifications = Certification::query()
            ->whereIn('status', [CertificationStatus::Draft->value, CertificationStatus::Archived->value])
            ->get();

        $coach = User::query()->where('role', UserRole::Coach->value)->first();

        foreach ($unpublishedCertifications as $certification) {
            $thread = QaThread::create([
                'user_id' => $demoStudents->random()->id,
                'certification_id' => $certification->id,
                'title' => $certification->name.'について('.$certification->status->label().')',
                'body' => '管理者モデレーション画面の動作確認用サンプルです。',
            ]);

            if ($coach !== null) {
                QaReply::create([
                    'qa_thread_id' => $thread->id,
                    'reply_user_id' => $coach->id,
                    'body' => '管理者モデレーション画面の動作確認用サンプル回答です。',
                ]);
            }
        }
    }

    /**
     * @return array<string, array{questions: array<int, array{title: string, body: string}>, replies: array<int, string>}>
     */
    private function qaTemplates(): array
    {
        return [
            '基本情報技術者試験' => [
                'questions' => [
                    ['title' => '二分探索と線形探索の使い分けについて', 'body' => '配列のデータ数が少ない場合でも二分探索を使うべきでしょうか？計算量以外に判断基準があれば教えてください。'],
                    ['title' => 'TCP/IPの3ウェイハンドシェイクについて', 'body' => 'SYN, SYN/ACK, ACKの流れは理解したのですが、3回目のACKを送った直後にデータ送信を開始してよいのか、それとも接続完了の確認を待つ必要があるのか分かりません。'],
                    ['title' => '正規化の第2正規形と第3正規形の違いについて', 'body' => '部分関数従属と推移的関数従属の違いがいまいち掴めていません。具体例で教えていただけると助かります。'],
                ],
                'replies' => [
                    'データ数が少ない場合は事前ソートのコストの方が大きくなることがあるので、件数が少ないうちは線形探索で十分です。件数が増えてきたタイミングで切り替えを検討するとよいと思います。',
                    '3回目のACKを送信した時点でクライアント側は接続確立とみなしてよいので、そのままデータ送信を開始して問題ありません。',
                ],
            ],
            '応用情報技術者試験' => [
                'questions' => [
                    ['title' => '午後試験のプロジェクトマネジメント分野の選び方について', 'body' => 'アルゴリズムとPM分野で悩んでいます。得点が安定しやすいのはどちらでしょうか？'],
                    ['title' => 'システム監査基準の独立性の考え方について', 'body' => '内部監査人が自部門のシステムを監査する場合、どのような点に注意すれば独立性を保てますか？'],
                    ['title' => '経営戦略分野のSWOT分析の出題パターンについて', 'body' => '過去問を見ていると、強み・弱みの分類に迷う選択肢が多いのですが、判断のコツはありますか？'],
                ],
                'replies' => [
                    'アルゴリズムは得意不得意が出やすい一方、PM分野は文章から素直に読み取れる設問が多いので、プログラミングに苦手意識があるならPM分野をおすすめします。',
                    '自部門の関連が薄い担当者を監査チームに立てる、監査結果を第三者がレビューするなど、実施体制の分離を明確にすることがポイントです。',
                ],
            ],
            'TOEIC L&R 800 点コース' => [
                'questions' => [
                    ['title' => 'Part5の語彙問題の勉強法について', 'body' => '文法は理解しているのに語彙問題で時間がかかってしまいます。効率のよい覚え方があれば教えてください。'],
                    ['title' => 'Part3・4のリスニングで先読みが間に合いません', 'body' => '設問と選択肢を先読みする時間が足りず、音声が始まってしまいます。先読みのコツはありますか？'],
                    ['title' => 'Part7のダブルパッセージの時間配分について', 'body' => '1セットに5分以上かかってしまい、最後の設問まで解ききれません。時間短縮のポイントを教えてください。'],
                ],
                'replies' => [
                    '単語帳を1周してから問題を解くより、Part5の過去問を先に解いて間違えた単語だけ覚える方が効率よく語彙力が伸びます。',
                    '最初の設問だけ先読みしてから音声を聞き始め、残りの設問は選択肢の音声中に先読みする、という2段階に分けると間に合いやすくなります。',
                ],
            ],
            '日商簿記 2 級' => [
                'questions' => [
                    ['title' => '工業簿記の仕損費の処理について', 'body' => '正常仕損と異常仕損で処理方法が異なると学んだのですが、仕訳の違いがよく分かりません。'],
                    ['title' => '連結会計の非支配株主持分の考え方について', 'body' => '親会社の持分比率が80%の場合、非支配株主持分はどのタイミングで計算すればよいでしょうか？'],
                    ['title' => '商業簿記の貸倒引当金の差額補充法について', 'body' => '期末残高と設定額の差額だけを追加計上する、という理解で合っていますか？'],
                ],
                'replies' => [
                    '正常仕損費は良品の製造原価に含めて処理しますが、異常仕損費は非原価項目として営業外費用や特別損失に計上する点が大きな違いです。',
                    'その理解で合っています。決算整理前の残高と当期末の設定額との差額のみを繰入額として計上します。',
                ],
            ],
            'PMP' => [
                'questions' => [
                    ['title' => 'クリティカルパス法の所要期間の算出について', 'body' => '複数の経路がある場合、どの経路を基準にプロジェクト全体の期間を見積もればよいですか？'],
                    ['title' => 'リスク登録簿の定量的分析と定性的分析の使い分けについて', 'body' => '小規模プロジェクトでも定量的リスク分析を必ず実施すべきでしょうか？'],
                    ['title' => 'ステークホルダーエンゲージメントの評価マトリクスについて', 'body' => '現在の関与度と望ましい関与度のギャップをどう埋めればよいか、具体的な進め方が分かりません。'],
                ],
                'replies' => [
                    '最も所要日数が長い経路(クリティカルパス)がプロジェクト全体の最短完了期間になります。他の経路に余裕(フロート)があっても、クリティカルパス上の遅延はそのままプロジェクト全体の遅延につながります。',
                    '小規模プロジェクトでは定性的分析(発生確率・影響度のランク付け)だけでも十分なケースが多く、定量的分析は影響が大きいリスクに絞って実施するのが現実的です。',
                ],
            ],
        ];
    }
}
