<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class downloadEPG implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        foreach(config('app.sources') as $source){
            if(!$source['url']) return;
            $extension = pathinfo($source['url'], PATHINFO_EXTENSION);
            $filename = storage_path('app').DIRECTORY_SEPARATOR.Str::random(24).'.'.$extension; //temp filename
            if(@copy($source['url'], $filename)) {
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
                rename($filename, storage_path('app').DIRECTORY_SEPARATOR.strtoupper($source['name']).'.xml');
            }
        }
    }
}
