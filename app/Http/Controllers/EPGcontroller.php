<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Carbon\Carbon;

use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Str;

use Illuminate\Support\Arr;

use Illuminate\Support\Facades\DB;

use App\Models\Provider;

use App\Models\EPG;

use Illuminate\Support\Facades\Redis;

class EPGcontroller extends Controller
{
    function getlist(Request $request){
        Log::debug($request->all());
        $return=[];
        $list = json_decode($request->input('list'),true);
        foreach($list as $id => $info){
            $provider = null;
            if(isset($info['u'])) $provider = Provider::where('name',$info['u'])->first();
            if(!$provider) $provider = Provider::where('name',config('app.default_provider'))->first();
            if(!$provider) break;
            $channel = (isset($info['e'])) ? $info['e'] : $info['n'];
            $return=Arr::add($return, $id, $provider->name."/epg/$channel");
        }
        return $return;
    }

    function channelEPG($provider,$channel_name){
        $channel_name = pathinfo($channel_name, PATHINFO_FILENAME);
        $provider = Provider::where('name',$provider)->first();
        if(!$provider) return ['epg_data'=>[]];
        $epg = Redis::get('provider'.$provider->id.':channel:'.$channel_name);
        if(!$epg) {
            $epg = EPG::select('name','time_from as time','time_to','description as descr')
            ->where('provider_id',$provider->id)
            ->where('tvg_id',$channel_name)
            ->get();
            Redis::set('provider'.$provider->id.':channel:'.$channel_name, $epg);
        } else {
            $epg = json_decode($epg);
        }
        return [
            'epg_data' => $epg
        ];
    }

    function providersChannels($provider){
        $provider = Provider::where('name',$provider)->first();
        if(!$provider) abort(404);
        $results = [];
        foreach ($provider->getChannels() as $channel){
            $results[]=[
                'tvg_id'   =>$channel->tvg_id,
                'channels' => route('providers.epg',['provider'=>$provider->name,'channel_name'=>$channel->tvg_id]).'.json'
            ];
        }
        return $results;
    }

    function providers(){
        $results = [];
        foreach(Provider::where('active',1)->get() as $provider){
            $results[] = [
                'provider' => $provider->name,
                'channels' => route('channels',$provider->name)
            ];
        }
        return $results;
    }
}
