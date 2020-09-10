<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redis;
use App\Models\EPG;
use Carbon\Carbon;

class processXML implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    private $file_name;

    private $provider;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($provider,$file_name)
    {
        $this->file_name = $file_name;
        $this->provider = $provider;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if(!Storage::exists($this->file_name)) return;
        EPG::where('provider_id',$this->provider->id)->delete();
        $z = new \XMLReader;
        $z->open(storage_path('app').DIRECTORY_SEPARATOR.$this->file_name);
        while ($z->read() && $z->name !== 'programme');
        while ($z->name === 'programme')
        {
            
            $node = new \SimpleXMLElement($z->readOuterXML());
            $time_from = Carbon::parse(@$node->attributes()->start);
            $time_to = Carbon::parse(@$node->attributes()->stop);
            $epg = new EPG;
            $epg->provider_id = $this->provider->id;
            $epg->tvg_id = @$node->attributes()->channel;
            $epg->time_from = $time_from->timestamp;
            $epg->time_to = $time_to->timestamp;
            $epg->name = @$node->title;
            $epg->description = (isset($node->desc)) ? $node->desc : null;
            $epg->save();
            $z->next('programme');
        }
        Storage::delete($this->file_name);
        if(config('redis_enabled')) Redis::flushDB();
    }
}
