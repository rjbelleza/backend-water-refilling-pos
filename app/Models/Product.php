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
        'user_id',
        'track_stock',
        'category_id',
        'isActive'
    ];

    protected $casts = [
        'price' => 'decimal:2'
    ];

    public function user() 
    {
        return $this->belongsTo(User::class);
    }

    public function category() 
    {
        return $this->belongsTo(Category::class);
    }

    public function saleItems() 
    {
        return $this->hasMany(SaleProduct::class);
    }
    
}
