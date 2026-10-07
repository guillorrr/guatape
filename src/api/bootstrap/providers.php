<?php

use App\Providers\AppServiceProvider;
use App\Providers\TenancyServiceProvider;

// Tenancy first: its queue listener must restore a job's tenant before the
// other listeners (JobRunRecorder) see the job.
return [
    TenancyServiceProvider::class,
    AppServiceProvider::class,
];
