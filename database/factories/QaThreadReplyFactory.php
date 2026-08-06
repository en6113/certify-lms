<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\QaThread;
use App\Models\QaThreadReply;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QaThreadReply>
 */
class QaThreadReplyFactory extends Factory
{
    protected $model = QaThreadReply::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'qa_thread_id' => QaThread::factory(),
            'reply_user_id' => User::factory(),
            'body' => fake()->realText(250),
        ];
    }

    public function fromStudent(): static
    {
        return $this->state(fn () => [
            'reply_user_id' => User::factory()->state(['role' => UserRole::Student->value]),
        ]);
    }

    public function fromCoach(): static
    {
        return $this->state(fn () => [
            'reply_user_id' => User::factory()->state(['role' => UserRole::Coach->value]),
        ]);
    }
}
