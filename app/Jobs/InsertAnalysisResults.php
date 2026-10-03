<?php

namespace App\Jobs;

class InsertAnalysisResults extends LaboratoryResultMutation
{
    protected const WORKFLOW = 'analysis';

    protected const STAGE = 'analyze';

    protected const INDIVIDUAL = false;
}
