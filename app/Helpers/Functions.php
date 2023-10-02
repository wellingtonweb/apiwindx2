<?php


namespace App\Helpers;


class Functions
{

    public function receiptFormat($receipt)
    {
        if(empty($receipt)){
            return null;
        }

        $newReceipt = explode("\n", $receipt->comprovanteAdquirente);

        $cardEntMode = self::getCardEntMode($receipt->comprovanteAdquirente);

        return [
            'card_number' => trim($newReceipt[9]),
            'flag' => trim($newReceipt[7]),
            'card_ent_mode' => $cardEntMode != false ? "TRANSACAO AUTORIZADA COM SENHA" : "TRANSAÇÃO AUTORIZADA POR APROXIMAÇÃO",
            'receipt_full' => $receipt->comprovanteAdquirente
        ];
    }

    public function getCardEntMode($receipt)
    {
        $resp = strpos($receipt, "TRANSACAO AUTORIZADA COM SENHA");

        if ($resp === false) {
            return false;
        }

        return true;
    }

}
