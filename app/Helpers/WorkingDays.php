<?php


namespace App\Helpers;

use Carbon\Traits\Creator;
use DateTime;
use DateInterval;
use Carbon\Carbon;
use function Symfony\Component\String\b;

class WorkingDays
{

    public static function calcFees(String $date, string $val)
    {
//        $now = date_create(date('Y-m-d', strtotime("2023-09-16T00:00:00")));
        $now = date_create(date('Y-m-d'));
        $dueDate = date_create(date('Y-m-d', strtotime($date)));
        $days = 0;
        $vm = 0;
        $vj = 0;
        if ($now > $dueDate)
        {
            $days = $now->diff($dueDate)->format('%a');
            $vm = (($val * 2) / 100);
            $vj = ((($val * 0.2) / 100) * $days);
//            $result = number_format(floatval($vm + $vj), 2, ',', '');
            $result = $vm + $vj;

            return $result;
        } else {
            return $result = intval(0);
        }
    }

    public static function isHoliday($dateInput)
    {
        $currentYear = date('Y');

        $holidays = [
            $currentYear.'-01-01', $currentYear.'-04-07', $currentYear.'-04-21',
            $currentYear.'-05-01', $currentYear.'-09-07', $currentYear.'-10-12',
            $currentYear.'-11-02', $currentYear.'-11-15', $currentYear.'-12-25'];

        $dateInput = date('Y-m-d', strtotime($dateInput));

        if(in_array($dateInput, $holidays)){
            return true;
        }

        return false;
    }

    public static function hasFees($dueDate)
    {
//        $pay = Carbon::parse("2024-01-15T00:00:00");
        $pay = Carbon::parse($dueDate);
        $today = Carbon::now()->startOfDay();
        $isHoliday = self::isHoliday($dueDate);

        if($isHoliday && $pay->isWeekend())
        {
            if (($pay->isSaturday() && $today->isSaturday() && $today <= $pay->addDays(2)) ||
                $pay->isSunday() && $today <= $pay->addDay()) {
//                return 1;
                return true;
            }
        }

        //(Se é feriado e pagamento é sexta) ou (hoje menor ou igual a dt pagamento + 3 dias

        if (($isHoliday && $pay->isFriday()) && $today <= $pay->addDays(3))
        {
//            return 2;
            return true;
        }

        if($pay->isWeekend())
        {
            if (($pay->isSaturday() && $today->isSaturday() && $today <= $pay->addDays(2)) ||
                $pay->isSunday() && $today <= $pay->addDay()) {
//                return 3;
                return true;
            }
        }

        if($pay->isFriday() && $today <= $pay->addDays(3))
        {
//            return 4;
            return true;
        }

        return false;
    }
}
