<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subscription extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'plan_id', 'initial_investment', 'status'];

    public function user() {
        return $this->belongsTo(User::class);
    }
    public function plan() {
        return $this->belongsTo(Plan::class);
    }
    public function payments() {
        return $this->hasMany(Payment::class);
    }
}
