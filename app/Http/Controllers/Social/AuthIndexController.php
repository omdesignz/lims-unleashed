<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthIndexController extends Controller
{
    public function __invoke()
    {
        return view('social.index');
    }
}