<?php

namespace Database\Factories;

use App\Models\RedisTest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Carbon\Carbon;
class RedisTestFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = RedisTest::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        $id = 1;
        $airports = ['FRA', 'LHR', 'BRU', 'CKL', 'FPP', 'KCC'];
        $airports2 = ['CLK', 'ABR', 'NOS', 'BAS', 'KEM', 'XIS'];
        $hotel_id = 2;
        return [
            'id'    => rand(10, 9000000),
            'name'  => $this->faker->name,
            'hotel_id' => rand(1, 99999999),
            's_room_total' => rand(10, 200),
            's_room_remain' => rand(1, 100),
            'sd_room_total' => rand(10, 200),
            'sd_room_remain' => rand(1, 100),
            't_room_total' => rand(10, 200),
            't_room_remain' => rand(1, 100),
            'q_room_total' => rand(10, 200),
            'q_room_remain' => rand(1, 100),
            's_price'   => rand(1, 200),
            'sd_price'  => rand(10, 200),
            't_price'  => rand(30, 200),
            'q_price'  => rand(40, 200),
            'distance'  => [
                $airports[rand(0,5)] => rand(1, 400),
                $airports2[rand(0,5)] => rand(1, 400)
            ],
            'date'  => Carbon::now()->addDay(rand(0, 1))->format('y-m-d'),
        ];
    }
    
}