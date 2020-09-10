<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\Provider;
use Illuminate\Support\Facades\Log;

class downloadEPG implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach(Provider::where('active',1)->wherenotnull('url')->get() as $provider){
            Log::info('provider',$provider->toArray());
            if(Str::startsWith($provider->url, 'http')){ //URL
                $extension = pathinfo($provider->url, PATHINFO_EXTENSION);
                $filename = storage_path('app').DIRECTORY_SEPARATOR.Str::random(24).'.'.$extension; //temp filename
                if(@copy($provider->url, $filename)) {
                    if($extension=='gz'){ //Extract content
                        $buffer_size = 4096;
                        $out_file_name = str_replace('.gz', '.xml', $filename); 
                        $file = gzopen($filename, 'rb');
                        $out_file = fopen($out_file_name, 'wb'); 
                        while(!gzeof($file)) {
                            fwrite($out_file, gzread($file, $buffer_size));
                        }  
                        fclose($out_file);
                        gzclose($file);
                        unlink($filename);
                        $filename = $out_file_name;
                    }
                    rename($filename, storage_path('app').DIRECTORY_SEPARATOR.$provider->id.'.xml');
                    \App\Jobs\processXML::dispatch($provider,$provider->id.'.xml');
                }
            } else { //Local file. Must be in /storage/app folder
                if(Storage::exists($provider->url)){
                    \App\Jobs\processXML::dispatch($provider,$provider->url);
                } else {
                    Log::warning('file not found',['filename'=>$provider->url]);
                }
            }

        }
    }
}
