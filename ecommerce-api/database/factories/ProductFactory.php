<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        $name = $this->faker->words(2, true);
        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(10),
            'price' => $this->faker->randomFloat(2, 10, 2000),
            'stock' => $this->faker->numberBetween(0, 100),
            'sku' => strtoupper(Str::random(8)),
            'is_active' => $this->faker->boolean(90),
            'image' => null,
        ];
    }
}
