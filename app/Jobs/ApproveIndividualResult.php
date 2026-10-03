<?php

namespace App\Jobs;

class ApproveIndividualResult extends LaboratoryResultMutation
{
    protected const WORKFLOW = 'analysis';

    protected const STAGE = 'approve';

    protected const INDIVIDUAL = true;
}
