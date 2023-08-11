<?php

namespace App\Services;
#require_once('vendor/autoload.php');

use Spatie\Dropbox\Client;
use Carbon\Carbon;

class Dropbox 
{
    private $token;
    private $client;
    private $today;

    public function __construct()
    {
        $this->token = getenv('APP_ACCESS_TOKEN_DROPBOX');
        $this->client = new Client($this->token);
        $this->today = Carbon::now("-03:00");
        //$this->today = str_replace(['-',':',' '], Carbon::now(), '_');
    }

    public function upload(){

//        $str_date = preg_replace("/[^\p{L}\p{N}]+/u", "_", $this->today->toDateTimeString());

        $folder = 'api_db_'.$this->today->format('d-m-Y_H-i-s');
        $file_name = 'API_DB_PROD_';

        //dd(get_debug_type($str_date));

        $this->client->createFolder($folder); //cria a pasta Teste
        /*
        print_r($client->listFolder('Teste')); //lista o conteúdo da pasta Teste
        
        echo '<br/><br/>'; //apenas para quebrar linha
        */

        $this->client->upload($folder."/db.zip", file_get_contents(asset("storage/app/backup/API_DB_PROD_11-08-2023_08-17.zip")), 'overwrite');

        //$this->client->upload($folder, asset("storage/app/backup/API_DB_PROD_11-08-2023_08-17.zip"), 'overwrite');


        //dd($client->listFolder('Coisas'));
        
        //$this->client->copy('Coisas', "{$folder}"); //copia o conteúdo da pasta Coisas para a pasta Teste1
        /*
        
        print_r($client->listFolder('Teste1')); //lista o conteúdo da pasta Teste1
        
        echo '<br/><br/>'; //apenas para quebrar linha
        */
        /*
        $this->client->delete('Teste1/Book.xlsx'); //deleta o arquivo Book.xlsx na pasta Teste1
        
        
        print_r($client->listFolder('Teste1')); //lista o conteúdo da pasta Teste1
        
        echo '<br/><br/>'; //apenas para quebrar linha
        */
    }

}