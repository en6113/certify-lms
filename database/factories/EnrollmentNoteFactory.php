<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\EnrollmentNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EnrollmentNote>
 */
class EnrollmentNoteFactory extends Factory
{
    protected $model = EnrollmentNote::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'enrollment_id' => Enrollment::factory(),
            'created_by_user_id' => User::factory(),
            'body' => fake()->realText(200),
        ];
    }

    public function forEnrollment(Enrollment $enrollment): static
    {
        return $this->state(fn () => [
            'enrollment_id' => $enrollment->id,
        ]);
    }

    public function forAuthor(User $author): static
    {
        return $this->state(fn () => [
            'created_by_user_id' => $author->id,
        ]);
    }
}
