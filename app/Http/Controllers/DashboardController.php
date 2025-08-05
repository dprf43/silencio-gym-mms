<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $activeMembersCount = Member::count();
        
        return view('dashboard', compact('activeMembersCount'));
    }
}
