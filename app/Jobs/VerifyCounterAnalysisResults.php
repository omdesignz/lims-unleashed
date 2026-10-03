<?php

namespace App\Jobs;

class VerifyCounterAnalysisResults extends LaboratoryResultMutation
{
    protected const WORKFLOW = 'counter';

    protected const STAGE = 'verify';

    protected const INDIVIDUAL = false;
}
