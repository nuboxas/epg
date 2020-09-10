<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Arr;

use Illuminate\Support\Facades\DB;

use App\Models\Provider;

use App\Models\EPG;

use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Redis;

class EPGcontroller extends Controller
{
    function getlist(Request $request){
        $return=[];
        $list = json_decode($request->input('list'),true);
        foreach($list as $id => $info){
            $provider = (isset($info['u'])) ? $info['u'] : config('app.default_provider');
            $channel = (isset($info['e'])) ? $info['e'] : $info['n'];
            $return=Arr::add($return, $id, $provider."/epg/$channel");
        }
        return $return;
    }

    function channelEPG($provider,$channel_name){
        $providers_names = array_filter(explode(";", $provider));
        if(!$providers_names || count($providers_names)==0) return ['epg_data'=>[]];
        $channel_name = pathinfo($channel_name, PATHINFO_FILENAME);
        $provider = self::getProviderId(Arr::flatten($providers_names),$channel_name);
        if(!$provider || !isset($provider->id)) return ['epg_data'=>[]];
        $epg = (config('app.redis_enabled')) ? Redis::get('provider'.$provider->id.':channel:'.$channel_name) : null;
        if(!$epg) {
            $epg = EPG::select('name','time_from as time','time_to','description as descr')
            ->where('provider_id',$provider->id)
            ->where('tvg_id',$channel_name)
            ->get();
            if(config('app.redis_enabled')) Redis::set('provider'.$provider->id.':channel:'.$channel_name, $epg);
        } else {
            $epg = json_decode($epg);
        }
        return ['epg_data' => $epg];
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

    private static function getProviderId($providers_names,$channel){
        return DB::table('providers')
                ->select('providers.id')
                ->join('epg','epg.provider_id','=','providers.id')
                ->wherein('providers.name',$providers_names)
                ->where('epg.tvg_id',$channel)
                ->groupby(['provider_id','tvg_id'])
                ->orderby(DB::raw('max(epg.time_to)'),'desc')
                ->first('id');
    }
}
