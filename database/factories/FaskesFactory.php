<?php

namespace Database\Factories;

use App\Models\Faskes;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faskes>
 */
class FaskesFactory extends Factory
{
    protected $model = Faskes::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->bothify('FKS-#####')),
            'name' => 'Faskes '.$this->faker->city().' '.$this->faker->randomNumber(4),
            'city' => $this->faker->city(),
            'address' => $this->faker->address(),
        ];
    }
}
