<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Carbon\Carbon;

use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Str;

use Illuminate\Support\Arr;

use App\Models\Provider;

use App\Models\EPG;

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

    function channelEPG__($provider,$channel_name){
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

    function channelEPG($provider,$channel_name){
        Log::debug('Provider: '.$provider);
        Log::debug('Channel: '.$channel_name);
        $provider = Provider::where('name',$provider)->first();
        if(!$provider) return ['epg_data'=>[]];
        return [
            'epg_data' => EPG::select('name','time_from as time','time_to','description as descr')
                        ->where('provider_id',$provider->id)
                        ->where('tvg_id',$channel_name)
                        ->get()
        ];


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

    public function test(){
        $provider = Provider::where('name','pr2pr')->first();
        if(!$provider) abort(404);
        \App\Jobs\processXML::dispatch($provider,'PR2PR.xml');
        
    }
}
