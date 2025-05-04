<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'discount',
        'total_price',
        'created_at',
    ];

    public $timestamps = false;

    public function sale() 
    {
        return $this->belongsTo(Sale::class);
    }
    
    public function product() 
    {
        return $this->belongsTo(Product::class);
    }
    
}
