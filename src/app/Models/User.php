<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\AttendanceChangeRequest;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function admin()
    {
        return $this->hasOne(Admin::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function requestedChangeRequests()
    {
        return $this->hasMany(AttendanceChangeRequest::class, 'requested_by');
    }

    public function reviewedChangeRequests()
    {
        return $this->hasMany(AttendanceChangeRequest::class, 'reviewed_by');
    }

}