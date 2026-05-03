<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class NIFIdentificationService
{

    public function getCustomerData($tax_number)
    {
        // Define API
        $url = 'https://invoice.minfin.gov.ao/commonServer/common/taxpayer/get/' . $tax_number;

        // Grab the response
        $res = Http::get($url)->json();

        // Return the data when request successful, otherwise return empty array
        return $res['success'] == true ? collect($res['data']) : [];
    }
}