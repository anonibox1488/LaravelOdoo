<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
    protected $primaryKey = 'id';
    protected $table ="uploads";
    protected $fillable = [
        //mapeo de columnas de la base de datos
        'id', 'file_name', 'date', 'hour', 'path','ip','location','os','browser','loan','borrower_name','borrower_cedula','borrower_passaporte','loan_amount','term','contract_date','last_payment_date','principal_balance','final_payment_date','current_date','next_due_amount','last_payment_amt'
    ];
}