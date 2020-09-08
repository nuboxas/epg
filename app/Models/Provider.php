<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\DB;

class Provider extends Model
{
    function getChannels(){
        return DB::table('epg')
                ->select('tvg_id')
                ->where('provider_id',$this->id)
                ->groupBy('tvg_id')
                ->orderBy('tvg_id')
                ->get('tvg_id');
    }
}
