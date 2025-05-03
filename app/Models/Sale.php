<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'customer',
        'total_amount',
        'created_at'
    ];

    public function items() 
    {
        return $this->hasMany(SaleProduct::class);
    }
    
    public function user() 
    {
        return $this->belongsTo(User::class);
    }
    
}
