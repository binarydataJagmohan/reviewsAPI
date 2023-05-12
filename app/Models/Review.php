<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;
    protected $table = 'reviews';
    // protected $fillable = [
    //    'review_by',
    //    'review_to',
    //    'description',
    //    'total_rating',
    //    'avg_rating',
    //    'thumbs_up',
    //    'thumbs_down'
    // ];
}
