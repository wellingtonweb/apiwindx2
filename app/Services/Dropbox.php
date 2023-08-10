<?php

namespace App\Services;
#require_once('vendor/autoload.php');

use Spatie\Dropbox\Client;

class Dropbox 
{
    private $token;
    private $client;

    public function __construct()
    {
        $this->token = getenv('APP_ACCESS_TOKEN_DROPBOX');
        $this->client = new Client($this->token);
    }

    public function upload(){

        $this->client->createFolder('Teste'); //cria a pasta Teste
        /*
        print_r($client->listFolder('Teste')); //lista o conteúdo da pasta Teste
        
        echo '<br/><br/>'; //apenas para quebrar linha
        */
        
        $this->client->copy('Coisas', 'Teste1'); //copia o conteúdo da pasta Coisas para a pasta Teste1
        /*
        
        print_r($client->listFolder('Teste1')); //lista o conteúdo da pasta Teste1
        
        echo '<br/><br/>'; //apenas para quebrar linha
        */
        
        $this->client->delete('Teste1/Book.xlsx'); //deleta o arquivo Book.xlsx na pasta Teste1
        /*
        
        print_r($client->listFolder('Teste1')); //lista o conteúdo da pasta Teste1
        
        echo '<br/><br/>'; //apenas para quebrar linha
        */
    }

}