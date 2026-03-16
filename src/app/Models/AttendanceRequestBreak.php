<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\AttendanceChangeRequest;
use App\Models\BreakTime;

class AttendanceRequestBreak extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'request_id',
        'target_break_id',
        'status',
        'proposed_break_start_at',
        'proposed_break_end_at',
    ];

    protected $casts = [
        'proposed_break_start_at' => 'datetime',
        'proposed_break_end_at'   => 'datetime',
    ];

    // action: 0=追加, 1=更新
    public const ACTION_ADD    = 0;
    public const ACTION_UPDATE = 1;

    public function request()
    {
        return $this->belongsTo(AttendanceChangeRequest::class, 'request_id');
    }

    public function targetBreak()
    {
        return $this->belongsTo(BreakTime::class, 'target_break_id');
    }
}
