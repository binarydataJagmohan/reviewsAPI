<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErrorLog extends Model
{
    use HasFactory;
   
    protected $table = 'error_logs';
    protected $fillable = ['error_message', 'line_number', 'file_name','browser','oprating_system','loggedin_id','ip_adress'];
}
