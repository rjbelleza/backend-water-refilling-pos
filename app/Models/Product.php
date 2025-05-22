<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'stock_quantity',
        'unit',
        'user_id',
        'isActive'
    ];

    protected $casts = [
        'price' => 'decimal:2'
    ];

    public function user() 
    {
        return $this->belongsTo(User::class);
    }

    public function saleItems() 
    {
        return $this->hasMany(SaleProduct::class);
    }
    
}
