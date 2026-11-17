<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class DashboardController extends Controller
{
    public function dashboard()
    {

        return view('admin.index');

    }

}