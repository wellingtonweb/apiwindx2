<?php


namespace App\Helpers;

use Carbon\Traits\Creator;
use DateTime;
use DateInterval;
use Carbon\Carbon;
use function Symfony\Component\String\b;


class WorkingDays
{
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

    public static function isHolidayOrWeekend($dateInput)
    {
        $holiday = self::isHoliday($dateInput);

        $pay = Carbon::parse($dateInput);

//        $today = new Carbon();
        $today = Carbon::parse('2023-11-17T00:00:00');

        if($holiday){
            if($today >= $pay){
                //Considera final de semana para não cobrar juros
                if($today <= $pay->addDay()->nextWeekday()){
//                if($today <= $pay->addDay()){
                    return true;
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }elseif($pay->isWeekend()) {
            if($today <= $pay->nextWeekday()) {
                return true;
            }else{
                return false;
            }
        } else {
            return false;
        }
    }
}
