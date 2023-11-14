<?php


namespace App\Helpers;


use DateTime;

class Functions
{



    //    public function getCardEntMode($receipt)
//    {
//        $resp = strpos($receipt, "TRANSACAO AUTORIZADA COM SENHA");
//
//        if ($resp === false) {
//            return false;
//        }
//
//        return true;
//    }


    public function getDateTimeFull()
    {
        setlocale(LC_ALL, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
        date_default_timezone_set('America/Sao_Paulo');

        return strftime('%A, %d de %B de %Y às %H:%M:%S', (new DateTime())->getTimestamp());
    }

    public function getDateFull()
    {
        setlocale(LC_ALL, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
        date_default_timezone_set('America/Sao_Paulo');

        return strftime('%A, %d de %B de %Y', (new DateTime())->getTimestamp());
    }

    public function convertDateTime($date)
    {
        return date("d/m/Y H:i:s", strtotime($date) - 3 * 60 * 60);
    }

}
