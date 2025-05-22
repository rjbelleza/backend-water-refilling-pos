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
        'additional_fee',
        'amount_paid',
        'created_at',
    ];

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function saleProducts()
    {
        return $this->hasMany(SaleProduct::class);
    }

    public function customers() 
    {
        return $this->belongsTo(Customer::class);
    }
    
}
