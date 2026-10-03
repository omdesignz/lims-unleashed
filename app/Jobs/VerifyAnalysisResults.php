<?php

namespace App\Jobs;

class VerifyAnalysisResults extends LaboratoryResultMutation
{
    protected const WORKFLOW = 'analysis';

    protected const STAGE = 'verify';

    protected const INDIVIDUAL = false;
}
