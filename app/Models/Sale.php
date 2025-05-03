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
        'discount',
        'total_amount',
        'created_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function saleProducts()
    {
        return $this->hasMany(SaleProduct::class);
    }
    
}
