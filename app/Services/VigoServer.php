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

    public function setAuditPayment($auditInfo)
    {
        $payment_typeUpdated = "";
        $amount = str_replace('.',',', $auditInfo['billet']->total);

        switch ($auditInfo['payment']->payment_type){
            case 'credit': $payment_typeUpdated = "CRÉDITO"; break;
            case 'debit': $payment_typeUpdated = "DÉBITO"; break;
            case 'pix': $payment_typeUpdated = "PIX"; break;
            case null: $payment_typeUpdated = "PICPAY"; break;
            default: break;
        }

        $auditData = [
            'data'   =>   $this->today->format('Y-m-d'),
            'hora'   =>   $this->today->format('H:i:s'),
            'operador'   =>   'API',
            'acao'   =>   "BOLETO LIQUIDADO: {$auditInfo['billet']->billet_id}/{$auditInfo['billet']->reference} | VALOR: R$ {$amount} | CAIXA: {$auditInfo['caixa']}
            | PAG. REF.: {$auditInfo['payment']->reference} | MÉTODO: ".$payment_typeUpdated." | VIA: ".strtoupper($auditInfo['payment']->place).".",
            'idcliente'   =>   $auditInfo['payment']->customerId,
        ];

        Log::alert(json_encode($auditData));

//        return $auditData;

        sleep(60);

        DB::connection('vigo')->table('sistema_auditoria_cliente')
            ->insert($auditData);
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
