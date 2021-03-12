<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\DB;

class ShopifyAuth extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'site', 
        'access_token'
    ];

    // Get access token from database
    public function access_token($store_url) 
    {   
        return DB::table('shopify_auths')->select('access_token')->where('site', $store_url)->get();
    }
}
