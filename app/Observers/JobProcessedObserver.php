<?php

namespace App\Observers;

use Illuminate\Support\Facades\Log;

class JobProcessedObserver
{
    public function jobProcessed($event)
    {
        $job = $event->job;

        if ($job->hasFailed()) {
            Log::error('O job falhou: ' . $job->getJobId());
        } else {
            Log::info('O job foi executado com sucesso: ' . $job->getJobId());
        }
    }
}
