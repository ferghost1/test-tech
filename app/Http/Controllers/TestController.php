<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use RedisManager;
use App\Models\RedisTest;
use Carbon\Carbon;

class TestController extends Controller
{
    // function __invoke
    public function createRedisData() {
        $redis = RedisManager::connection();
        // $redis->flushAll();
        $dummyInv = RedisTest::factory()->count(100000)->make();
        // dd($dummyInv->toArray());
        $redis->pipeline(function($redis) use ($dummyInv) {
            $dummyInv->each(function($inv) use ($redis) {
                foreach($inv->distance as $ap => $distance) {
                    $redis->zAdd("inv:{$ap}:{$inv->date}", $distance, "inv:{$inv->id}");
                    $inv->{"{$ap}_distance"} = $distance;
                } 
                $redis->hMSet("inv:{$inv->id}", $inv->toArray());
            });
        });
    }

    public function getRedisData(Request $req) {
        $redis = RedisManager::connection();
        $params = $req->all();
        $ring = $req->ring ?? 0;
        $airport = $req->airport ?? 'FRA';
        $apRing = $req->ap_ring ?? [0, 10, 20, 40, 80];
        $paginate = $req->paginate ?? ['per_page' => 20 , 'current_page' => 1];
        $offset = $paginate['per_page'] * $paginate['current_page'];
        $date = $req->date ?? Carbon::now()->addDay(rand(0, 1))->format('y-m-d');
        $orderBy = "laravel_database_*->{$airport}_distance";
        if (!empty($req->orderBy[0])) {
            $orderBy = $req->orderBy[0] == 'price' ? "laravel_database_*->sd_price" : $orderBy;
        }

        $get = [
            'id' => "laravel_database_*->id",// 0 id
            'name' => "laravel_database_*->name", // 1 name
            'hotel_id' => "laravel_database_*->hotel_id", // 2 hotel id
            's_room_total' => "laravel_database_*->s_room_total", // 3 s_room_total
            'sd_room_total' => "laravel_database_*->sd_room_total", 
            't_room_total' => "laravel_database_*->t_room_total",
            'q_room_total' => "laravel_database_*->q_room_total",
            "{$airport}_distance" => "laravel_database_*->{$airport}_distance", //7
            'date' => "laravel_database_*->date",
            's_price' => "laravel_database_*->s_price",
            'sd_price' => "laravel_database_*->sd_price", // 10
            't_price' => "laravel_database_*->t_price",
            'q_price' => "laravel_database_*->q_price",
            's_room_remain' => "laravel_database_*->s_room_remain",
            'sd_room_remain' => "laravel_database_*->sd_room_remain",
            't_room_remain' => "laravel_database_*->t_room_remain",
            'q_room_remain' => "laravel_database_*->q_room_remain" // 16
        ];

        $conditions = [
            'ring'  => $ring ? [$apRing[$ring - 1], $apRing[$ring]] : null,
            's_room_remain' => $req->s_room_remain ?? 0,
            'sd_room_remain' => $req->sd_room_remain ?? 0,
            't_room_remain' => $req->t_room_remain ?? 0,
            'q_room_remain' => $req->q_room_remain ?? 0,
            'min_price'         => $req->min_price ?? 0
        ];

        $params['keys'] = array_keys($get);
        $params['per_page'] = $paginate['per_page'];

        $sortedData = $redis->sort("inv:{$airport}:{$date}", [
                'by' => $orderBy,
                'sort'  => $req->orderBy[1] ?? 'asc',
                'get'   => $get
            ]
        );
        
        $sortedData = array_chunk($sortedData, 17);
        return $this->checkCondition($sortedData, $conditions, $params);
    }

    private function checkCondition($data, $conditions, $params = []) {
        $res = [];
        foreach($data as $key => $item) {
            if ($conditions['ring']) {
                if ($item[7] < $conditions['ring'][0] || $item[7] > $conditions['ring'][1]) {
                    unset($data[$key]);
                    continue;
                }
            }
            
            if ($conditions['s_room_remain'] > $item[13]) {
                unset($data[$key]);
                    continue;
            }

            if ($conditions['sd_room_remain'] > $item[14]) {
                unset($data[$key]);
                    continue;
            }
            
            if ($conditions['t_room_remain'] > $item[15]) {
                unset($data[$key]);
                    continue;
            }

            if ($conditions['q_room_remain'] > $item[16]) {
                unset($data[$key]);
                    continue;
            }

            if ($conditions['min_price'] > $item[10]) {
                unset($data[$key]);
                    continue;
            }
            
            array_push($res, array_combine($params['keys'], $item));
            if (count($res) >= $params['per_page']) {
                break;
            }
        }
        return $res;
    }
}
