<?php

namespace App\Jobs;

class VerifyIndividualResult extends LaboratoryResultMutation
{
    protected const WORKFLOW = 'analysis';

    protected const STAGE = 'verify';

    protected const INDIVIDUAL = true;
}
