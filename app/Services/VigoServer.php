<?php


namespace App\Services;

use App\Models\Payment;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class VigoServer
{

    private $vigoDB;
    private $today;

    public function  __construct(){
        $this->vigoDB = DB::connection('vigo');
        $this->today = Carbon::now('America/Sao_Paulo');
    }

    public function setAuditPayment()
    {
        $data = [
            'data'   =>   $this->today->format('Y-m-d'),
            'hora'   =>   $this->today->format('H:i:s'),
            'operador'   =>   'API',
            'acao'   =>   "Boleto {$billet->billet_id}/{$billet->reference}, liquidado com valor R$ {$billet->total} no caixa nº {$caixa}.
        Pagamento referente {$payment->reference}, usando o método ".strtoupper($payment_type)." via ".strtoupper($place).".",
            'idcliente'   =>   '34258',
        ];

        return $data;

//        DB::connection('vigo')->table('sistema_auditoria_cliente')
//            ->insert($data);
    }

    public function getTerminalsOld()
    {
//        $list = $this->vigoDB->select('select * from pagamentos_terminais');
        $list = DB::connection('vigo')->table('pagamentos_terminais')
            ->insert([
//                'id'     =>   21,
                'nome'   =>   'Terminal API3',
                'terminalId'   =>   45,
                'pessoaId'   =>   9935,
                'key'   =>   'oaisdhasdhkjahsdakjsdkl',
                'instalacaoId'   =>   '123456',
                'senha'   =>   'abc123',
                'remote_access_id'   =>   '1.1.1.1',
                'data'   =>   $this->today->format('Y-m-d'),
                'hora'   =>   $this->today->format('H:i:s'),
            ]);

        return $list;

//        table('pagamentos_terminais')->where('type', 'Programs')->get();
    }

}
