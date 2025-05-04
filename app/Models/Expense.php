<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'description',
        'amount',
        'created_at'
    ];

    public $timestamps = false;

    public function user() {
        return $this->belongsTo(User::class);
    }
}
