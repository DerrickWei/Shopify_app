<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

use App\Models\ShopifyAuth;

class AppController extends Controller
{
    private $site = 'test-store-for-99h1.myshopify.com';

    // To handle install URL
    public function install(Request $request)
    {
        $shop_name = $request->shop;
        $scopes = 'read_products,write_products';

        // Redirect to the install page
        $url = 'https://' . $shop_name . '/admin/oauth/authorize?client_id='.env('SHOPIFY_KEY') . '&scope=' . $scopes . '&redirect_uri=' . env('APP_URL') . '/auth';
        return redirect($url);
    }

    // To handle auth URL
    public function auth(Request $request)
    {
        $shared_secret = env('SHOPIFY_SECRET');
        $params = $_GET; 
        $hmac = $request->hmac; 
        $shop_name = $request->shop;
        $code = $request->code;
        $params = array_diff_key($params, array('hmac' => ''));
        ksort($params); 

        $computed_hmac = hash_hmac('sha256', http_build_query($params), $shared_secret);

        // Valicate Shopify's request
        if (hash_equals($hmac, $computed_hmac)) {
            if (! ShopifyAuth::where('site', '=', $shop_name)->exists()) {

                // Request access token
                $response = Http::withHeaders([
                    'Content-type' => 'application/json',
                ])->post('https://' . $shop_name . '/admin/oauth/access_token', [
                    'client_id'     => env('SHOPIFY_KEY'),
                    'client_secret' => env('SHOPIFY_SECRET'),
                    'code'          => $code
                ]);

                $shopify_response = json_decode($response->body()); //echo "<pre>"; print_r($shopify_response); die("==");
                
                // Store a Shopify Auth                 
                $shopify_auth = new ShopifyAuth;
                $shopify_auth->site = $shop_name;
                $shopify_auth->access_token = $shopify_response->access_token;
                $shopify_auth->save();
            }
            
            die("Success");

        } else {
            abort(401, 'Unauthorized action.');
        }
    }

    // To get products from Shopify Store
    public function products(Request $request) 
    {
        // Get current Shopify Auth Credential
        $shopify_auth = new ShopifyAuth;
        
        $results = $shopify_auth->access_token($this->site); 
        $access_token = $results[0]->access_token;

        // Request products
        $response = Http::withHeaders([
            'Content-type'           => 'application/json',
            'X-Shopify-Access-Token' => $access_token
        ])->get('https://' . $this->site . '/admin/api/2020-10/products.json');

        echo "<pre>"; print_r(json_decode($response->body())); die("==");
    }
}
