<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\AttendanceChangeRequest;
use App\Models\Attendance;
use App\Http\Requests\AttendanceChangeRequestStoreRequest;
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
