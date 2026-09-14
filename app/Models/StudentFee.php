<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentFee extends Model
{
    use HasFactory;

    protected $table = 'student_fees';

    // DIRI IPANG-ALLOW ANG MGA FIELDS SA STUDENT FEES
    protected $fillable = [
        'student_id',
        'fee_name',
        'amount',
        'semester',
        'status',
    ];

    // Relasyon balik sa Student model
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }
}