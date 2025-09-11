<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubaccountDataBackup extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];
}
