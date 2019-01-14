<?php

namespace App\Http\Controllers\DataImport;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class ImportDataController extends Controller {

    function index() {
        return view('data-import.dataimport');
    }

    function upload(Request $request) {
        $files = $request->allFiles();


        foreach($files as $file) {
            // check file type
            switch($file->extension()) {
                case 'zip':
                    // if zip, unzip, loop
                    $options = explode('/', $file->hashName($file->path()));
                    $option = $options[count($options) - 1];
                    $file_name = 'data-imports/' . time() . '/';
                    Storage::disk('local')->put($file_name, $file);

                    $uploaded_file_name = $file_name . $option;
                    $uploaded_file = Storage::disk('local')->get($uploaded_file_name);

                    $extract_to = 'data-import-extracted/' . time() .'/';
                    Storage::makeDirectory($extract_to);

                    if ($this->unzip(storage_path('app/' . $uploaded_file_name), storage_path('app/' . $extract_to))) {
                        // unzipped
                        // process new folder
                        return $this->process_extracted_folder($extract_to);
                    } else {
                        // failed to unzip
                        // throw error
                        return response()->json(['error' => 'true', 'message' => 'Could not extract ZIP file.']);
                    }
                    break;
                case 'csv':

                    break;
            }

        }

        return response()->json(['error' => 'true', 'message' => 'Something wen\'t wrong, Please try again later']);
    }

    function process_extracted_folder($dir) {
        $files = scandir(storage_path('app/' . $dir));
        $request_time  = time();
        $has_error = false;
        $message = '';

        foreach($files as $file) {
            try {
                if ($file == '.' || $file == '..' || $file == '__MACOSX2') {
                    continue;
                }

                // check if dir
                if (is_dir($file)) {
                    // is dir, copy to assets
//                dd(storage_path('app/' . $dir . $file));
                    $new_dir = 'uploaded-data/' . $request_time . '/' . $file . '/';
                    Storage::makeDirectory($new_dir);
                    $this->recurse_copy(storage_path('app/' . $dir . $file), storage_path('app/' . $new_dir));
                } else {
//                    $this->dispatch(new ProcessCSV(storage_path('app/' . $dir . $file)));

                }
            } catch(\Exception $e) {
                $has_error = true;
                $message = 'Something wen\'t wrong, Please try again later';
                break;
            }

        }

        if($has_error) {
            return response()->json(['error' => 'false', 'message' => $message]);
        }

        return response()->json(['error' => 'false', 'message' => 'Data import has been queued.']);
    }

    function recurse_copy($src,$dst) {
        $dir = opendir($src);
        @mkdir($dst);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    $this->recurse_copy($src . '/' . $file,$dst . '/' . $file);
                }
                else {
                    copy($src . '/' . $file,$dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    function unzip($location,$new_location){
        $zip = new \ZipArchive();
        $res = $zip->open($location);
        if ($res === TRUE) {

            // Extract file
            $zip->extractTo($new_location);
            $zip->close();

            return true;
        }
        return false;
    }

}
