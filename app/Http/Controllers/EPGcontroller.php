<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Carbon\Carbon;

use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Str;

use Illuminate\Support\Arr;

class EPGcontroller extends Controller
{
    function index(){
        abort(443);
    }

    function getlist(Request $request){
        $return=[];
        $list = json_decode($request->input('list'),true);
        foreach($list as $id => $info){
            $provider = (isset($info['u'])) ? $info['u'] : config('app.default_provider');
            $channel = (isset($info['e'])) ? $info['e'] : $info['n'];
            $return=Arr::add($return, $id, "$provider/epg/$channel");
        }
        return $return;
    }

    function channelEPG($provider,$channel_name){
        if(Storage::missing(strtoupper($provider).'.xml')) return ['epg_data'=>[]];
        $xml_epg = simplexml_load_file(storage_path('app').DIRECTORY_SEPARATOR.strtoupper($provider).'.xml');
        if ($xml_epg === false) return ['epg_data'=>[]];
        $channel= $xml_epg->xpath("//channel[@id='$channel_name']");
        if($channel){
            $epgs= $xml_epg->xpath("//programme[@channel='$channel_name']");
            $results=[];
            foreach($epgs as $epg){
                $start = Carbon::parse((string) $epg->attributes()['start']);
                $end = Carbon::parse((string) $epg->attributes()['stop']);
                $results[]=[
                    'time' => $start->timestamp,
                    'time_to' => $end->timestamp,
                    'name' =>(string) $epg->title,
                    'descr' => (string) Str::of((string) $epg->desc)->rtrim('(n)')
                ];
            }
            return ['epg_data'=>$results];
        } else{
            return ['epg_data'=>[]];
        }
    }
}
