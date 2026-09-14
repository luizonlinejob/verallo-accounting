<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'amount_paid',
        'or_number',
        'payment_method',
        'status',
        'remarks',
        'rejection_reason',
        'rejected_at',
        'rejected_by',
        'approved_at',
        'encoded_by',
        'approved_by',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }

    public function encoder()
    {
        return $this->belongsTo(User::class, 'encoded_by', 'id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }

    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by', 'id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}