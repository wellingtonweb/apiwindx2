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

    public function setAuditPayment($auditInfo, $status)
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

        sleep(60);

        if($status === 200){
            DB::connection('vigo')->table('sistema_auditoria_cliente')
                ->insert([
                    'data'   =>   $this->today->format('Y-m-d'),
                    'hora'   =>   $this->today->format('H:i:s'),
                    'operador'   =>   'API',
                    'acao'   =>   "BOLETO LIQUIDADO: {$auditInfo['billet']->billet_id}/{$auditInfo['billet']->reference} | VALOR: R$ {$amount} | CAIXA: {$auditInfo['caixa']}
            | PAG. REF.: {$auditInfo['payment']->reference} | MÉTODO: ".$payment_typeUpdated." | VIA: ".strtoupper($auditInfo['payment']->place).".",
                    'idcliente'   =>   $auditInfo['payment']->customerId,
            ]);
        }else{
            DB::connection('vigo')->table('sistema_auditoria_cliente')
                ->insert([
                    'data'   =>   $this->today->format('Y-m-d'),
                    'hora'   =>   $this->today->format('H:i:s'),
                    'operador'   =>   'API',
                    'acao'   =>   "PAGAMENTO DUPLICADO DO BOLETO : {$auditInfo['billet']->billet_id}/{$auditInfo['billet']->reference} | VALOR: R$ {$amount}
            | PAG. REF.: {$auditInfo['payment']->reference} | MÉTODO: ".$payment_typeUpdated." | VIA: ".strtoupper($auditInfo['payment']->place).".",
                    'idcliente'   =>   $auditInfo['payment']->customerId,
                ]);
        }

    }

    public function setAuditCustomer($customerId, $action)
    {
        if($customerId){
            DB::connection('vigo')->table('sistema_auditoria_cliente')
                ->insert([
                    'data'   =>   $this->today->format('Y-m-d'),
                    'hora'   =>   $this->today->format('H:i:s'),
                    'operador'   =>   'API',
                    'acao'   =>   $action,
                    'idcliente'   =>   $customerId,
                ]);
        }
    }

    public function setNewPasswordCustomer($customerId)
    {
        if($customerId){
            return true;

//            $action = "A senha do usuário foi alterada via Central do Assinante.";
//            self::setAuditCustomer($customerId, $action);


        }
        return false;
    }


    public function checkLoginCustomer($login)
    {
        if($login){
            $search = $this->vigoDB->select("select * from cadastro_clientes where login = ?",[$login]);
//            if(!$search){
//                return false;
//            }

            return collect($search);

//            $action = "A senha do usuário foi alterada via Central do Assinante.";
//            self::setAuditCustomer($customerId, $action);


        }
        return false;
    }

    public function getPaymentsCieloOld()
    {
        return collect($this->vigoDB->select('select * from pagamentos_cielo'));
    }

    public function getPaymentsPaygoOld()
    {
        return collect($this->vigoDB->select('select * from pagamentos_paygo'));
    }

    public function getPaymentsPicpayOld()
    {
        return collect($this->vigoDB->select('select * from pagamentos_picpay'));
    }

    public function getPaymentsTerminalsOld()
    {
        return collect($this->vigoDB->select('select * from pagamentos_terminais'));
    }

}
