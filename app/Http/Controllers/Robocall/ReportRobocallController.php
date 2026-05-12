<?php

namespace App\Http\Controllers\Robocall;

use Inertia\Inertia;
use App\Http\Controllers\Controller;

class ReportRobocallController extends Controller
{

    public function report()
    {
        return Inertia::render("ReportRobocall/Index");
    }
}
