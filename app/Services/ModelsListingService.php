<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ModelsListingService
{

    public function getModels()
    {
        $modelList = [];
        // $path = app_path() . "/Models";
        // $results = scandir($path);
 
        // foreach ($results as $result) {
        //     if ($result === '.' or $result === '..') continue;
        //     $filename = $result;
  
        //     if (is_dir($filename)) {
        //         $modelList = array_merge($modelList, getModels($filename));
        //     }else{
        //         $modelList[] = substr($filename,0,-4);
        //     }
        // }

        $paths = [app_path() . "/Models", app_path() . "/Settings"];

        // dd($paths);

        foreach($paths as $path) {

            $results = scandir($path);
 
        foreach ($results as $result) {
            if ($result === '.' or $result === '..') continue;
            $filename = $result;
  
            if (is_dir($filename)) {
                $modelList = array_merge($modelList, getModels($filename));
            }else{
                $modelList[] = substr($filename,0,-4);
            }
        }

        }
        
  
        return $modelList;
    }
}