<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'fname',
        'lname',
        'username',
        'password',
        'isActive',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
    
    public function isAdmin()
    {
        return $this->role === 'admin';
    }
    
    public function isStaff()
    {
        return $this->role === 'staff';
    }

    public function product()
    {
        return $this->hasMany(Product::class);
    }

    public function expenses() {
        return $this->hasMany(Expense::class);
    }
}
