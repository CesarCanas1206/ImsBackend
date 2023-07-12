<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordReset extends Model
{
    const UPDATED_AT = null;
    public $incrementing = false;
    public $primaryKey = 'email';

    protected $fillable = [
        'email', 'token',
    ];
}
