<?php

namespace App\Jobs;

class ApproveAnalysisResults extends LaboratoryResultMutation
{
    protected const WORKFLOW = 'analysis';

    protected const STAGE = 'approve';

    protected const INDIVIDUAL = false;
}
