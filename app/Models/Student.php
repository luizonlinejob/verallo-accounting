<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'full_name',
        'email',
        'course',
        'year_level',
        'semester',
        'custom_values',
    ];

    // FIX SA "Array to string conversion" ERROR
    protected $casts = [
        'custom_values' => 'array',
    ];

    // Gi-append ang attributes para ma-access sa Vue.js
    protected $appends = [
        'total_fees', 
        'total_paid', 
        'total_balance',
        'has_pending_payment',
        'pending_amount',
        'pending_date',
        'last_paid_date'
    ];

    public function fees()
    {
        return $this->hasMany(StudentFee::class, 'student_id', 'id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'student_id', 'id');
    }

    // Dynamic Getters (Gi-update gamit ang relationLoaded para paspas ug walay crash sa Archive)
    public function getTotalFeesAttribute()
    {
        if ($this->relationLoaded('fees')) {
            return $this->fees->sum('amount');
        }
        return $this->fees()->sum('amount') ?? 0;
    }

    public function getTotalPaidAttribute()
    {
        if ($this->relationLoaded('payments')) {
            return $this->payments->where('status', 'approved')->sum('amount_paid');
        }
        return $this->payments()->where('status', 'approved')->sum('amount_paid') ?? 0;
    }

    public function getTotalBalanceAttribute()
    {
        return max(0, $this->total_fees - $this->total_paid);
    }

    // Check kon naa ba'y pending payment
    public function getHasPendingPaymentAttribute()
    {
        if ($this->relationLoaded('payments')) {
            return $this->payments->where('status', 'pending')->isNotEmpty();
        }
        return $this->payments()->where('status', 'pending')->exists();
    }

    // Kuhaon ang kantidad sa pending nga bayad
    public function getPendingAmountAttribute()
    {
        if ($this->relationLoaded('payments')) {
            return $this->payments->where('status', 'pending')->sum('amount_paid');
        }
        return $this->payments()->where('status', 'pending')->sum('amount_paid') ?? 0;
    }

    // Kuhaon ang petsa sa pinaka-latest nga pending payment
    public function getPendingDateAttribute()
    {
        if ($this->relationLoaded('payments')) {
            $pending = $this->payments->where('status', 'pending')->sortByDesc('created_at')->first();
        } else {
            $pending = $this->payments()->where('status', 'pending')->latest()->first();
        }

        return ($pending && $pending->created_at) ? $pending->created_at->format('M d, Y h:i A') : null;
    }

    // Kuhaon ang petsa sa pinaka-latest nga gi-approve nga bayad
    public function getLastPaidDateAttribute()
    {
        if ($this->relationLoaded('payments')) {
            $lastPaid = $this->payments->where('status', 'approved')->sortByDesc('created_at')->first();
        } else {
            $lastPaid = $this->payments()->where('status', 'approved')->latest()->first();
        }

        return ($lastPaid && $lastPaid->created_at) ? $lastPaid->created_at->format('M d, Y') : null;
    }
}