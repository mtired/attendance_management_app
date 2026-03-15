<?php

namespace App\Http\Controllers;

use App\Models\AttendanceChangeRequest;
use Illuminate\Http\Request;

class AdminChangeRequestController extends Controller
{
    public function index(Request $request)
    {
        $activeTab = $request->query('tab', 'pending');

        $pendingRequests = AttendanceChangeRequest::with('requestedBy')
            ->where('status', 0)
            ->latest()
            ->get();

        $approvedRequests = AttendanceChangeRequest::with('requestedBy')
            ->where('status', 1)
            ->latest()
            ->get();

        return view('admin.request_list', compact(
            'activeTab',
            'pendingRequests',
            'approvedRequests'
        ));
    }
}
