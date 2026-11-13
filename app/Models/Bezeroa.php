<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bezeroa extends Model
{
    // Laravel-ek "bezeroas" asmatuko luke; taularen izena guk esaten diogu.
    protected $table = 'bezeroak';

    protected $fillable = ['izena', 'emaila', 'telefonoa', 'odoo_id'];
}
