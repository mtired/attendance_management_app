<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceRequestBreak;

class AttendanceChangeRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'requested_by',
        'attendance_id',
        'proposed_clock_in_at',
        'proposed_clock_out_at',
        'remarks',
        'status',
    ];

    protected $casts = [
        'proposed_clock_in_at'  => 'datetime',
        'proposed_clock_out_at' => 'datetime',
    ];

    // status: 0=承認待ち, 1=承認済み
    public const STATUS_PENDING  = 0;
    public const STATUS_APPROVED = 1;

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function requestBreaks()
    {
        return $this->hasMany(AttendanceRequestBreak::class, 'request_id');
    }
}
