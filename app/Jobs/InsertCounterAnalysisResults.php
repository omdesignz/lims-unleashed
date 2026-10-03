<?php

namespace App\Jobs;

class InsertCounterAnalysisResults extends LaboratoryResultMutation
{
    protected const WORKFLOW = 'counter';

    protected const STAGE = 'analyze';

    protected const INDIVIDUAL = false;
}
