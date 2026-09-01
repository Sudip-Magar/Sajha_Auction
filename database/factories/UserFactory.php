<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'google_id' => null,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'username' => fake()->unique()->userName(),
            'phone' => '98'.fake()->numerify('########'),
            'date_of_birth_en' => fake()->date('Y-m-d', '-18 years'),
            'gender' => fake()->randomElement(['MALE', 'FEMALE', 'OTHER']),
            'is_seller' => false,
            'seller_application_pending' => false,
            'password' => static::$password ??= Hash::make('password'),
            'is_verified' => true,
            'avatar' => null,
            'bio' => fake()->sentence(),
            'status' => 'active',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
