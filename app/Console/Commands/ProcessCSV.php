<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProcessCSV extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'csv:process {folder_path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $folder_path = storage_path('app/data-import-extracted/' . $this->argument('folder_path'));

        $files = scandir($folder_path);

        foreach($files as $file) {
            try {
                if ($file == '.' || $file == '..' || $file == '__MACOSX2') {
                    continue;
                }

                // check if dir
                if (!is_dir($file)) {
                    // not dir
                    $file_path = $folder_path . '/' . $file;
                    $path_parts = pathinfo($file_path);
                    $header = true;

                    if(array_key_exists('extension', $path_parts) && array_key_exists('filename', $path_parts) && $path_parts['extension'] == 'csv') {
                        $table_name = 'data__' . $path_parts['filename'];
                        if(Schema::hasTable($table_name)) {
                            continue;
                        }
                        $header_columns = [];
                        $headers = [];

                        $handle = fopen($file_path, "r");
                        while ($csvLine = fgetcsv($handle, 5000, ",")) {
                            if ($header) {
                                $header = false;

                                for($i = 0; $i < count($csvLine); $i++) {
                                    if(array_key_exists($csvLine[$i], $header_columns)) {
                                        $header_columns[$csvLine[$i]] = $i;
                                    }

                                    $headers[] = $csvLine[$i];
                                }



                                Schema::create($table_name, function (Blueprint $table) use ($headers) {

                                    foreach($headers as $header) {
                                        if($header == 'Id') {
                                            $table->string($header)->unique()->primary();
                                        } else {
                                            $table->text($header)->nullable();
                                        }
                                    }

                                    $table->timestamps();
                                    $table->softDeletes();
                                });

                            } else {

                                $temp = [];

                                for($i = 0; $i < count($csvLine); $i++) {
                                    try {
                                        $key = utf8_encode($headers[$i]);
                                        $value = utf8_encode($csvLine[$i]);

                                        $temp[$key] = $value;
                                    } catch(\Exception $e) {
                                        continue;
                                    }
                                }

                                if(!array_key_exists('Id', $temp) || empty($temp['Id'])) {
                                    continue;
                                }

                                try {
                                    if(!DB::connection()->table($table_name)->find($temp['Id'])) {
                                        DB::table($table_name)->insert($temp);
                                    }
                                } catch(\Exception $e) {
                                    print_r($csvLine);
                                    dd($e);
                                }

                                $data[] = $temp;
                            }
                        }


                    }
                }
            } catch(\Exception $e) {
                echo 'Something wen\'t wrong, Please try again later' . PHP_EOL;
                dd($e);
            }

        }

        echo 'Done.';
    }
}
