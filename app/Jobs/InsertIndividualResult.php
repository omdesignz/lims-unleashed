<?php

namespace App\Jobs;

class InsertIndividualResult extends LaboratoryResultMutation
{
    protected const WORKFLOW = 'analysis';

    protected const STAGE = 'analyze';

    protected const INDIVIDUAL = true;
}
