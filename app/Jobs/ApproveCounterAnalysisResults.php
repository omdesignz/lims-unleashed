<?php

namespace App\Jobs;

class ApproveCounterAnalysisResults extends LaboratoryResultMutation
{
    protected const WORKFLOW = 'counter';

    protected const STAGE = 'approve';

    protected const INDIVIDUAL = false;
}
