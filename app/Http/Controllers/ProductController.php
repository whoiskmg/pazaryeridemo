<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function create()
    {
        return view('products.create');
    }

    public function store(Request $request)
    {
        $product = Product::create($request->only(['name', 'description']));

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $product->images()->create([
                    'url' => $image->store('images', 'public')
                ]);
            }
        }

        return redirect()->route('products.show', $product);

    }

    public function show(Product $product)
    {
        return view('products.show', compact('product'));
    }

    public static function sendPostRequest($data)
    {

        $array = $data->attributesToArray();

        $itemBody = [
            'items' => [
                [
                    "barcode" => $array["barcode"],
                    "title" => $array["title"],
                    "productMainId" => $array['mainId'],
                    "brandId" => $array['brandId'],
                    "categoryId" => $array['categoryId'],
                    "quantity" => $array['quantity'],
                    "stockCode" => $array['stockCode'],
                    "dimensionalWeight" => $array['dimensionalWeight'],
                    "description" => $array['description'],
                    "currencyType" => $array['currencyType'],
                    "listPrice" => $array['listPrice'],
                    "salePrice" => $array['salePrice'],
                    "vatRate" => $array['vatRate'],
                    "cargoCompanyId" => $array['cargoCompanyId'],
                    "images" => $array['images'],
                    "attributes" => [],
                ]
            ]
        ];

        // Define the request parameters
        $url = 'https://api.trendyol.com/sapigw/suppliers/' . $array['brandId'] . '/v2/products';

        $auth = Brand::where('brandSellerId', $array['brandId'])->value('brandAuth');

        $token = "Basic $auth";

        try {
            // Log the request details
            Log::info('Sending POST request', [
                'url' => $url,
                'body' => $itemBody,
                'auth' => $auth // Be careful not to log sensitive auth information in production
            ]);

            $response = Http::withHeaders([
                'Authorization' => $token,
                'Content-Type' => 'application/json',
            ])->asJson()->post($url, $itemBody);

            // Log the response
            Log::info('Received response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            // Check if the request was successful
            if ($response->successful()) {
                return response()->json($response->json(), $response->status());
            }

            // If not successful, throw an exception
            throw new Exception('HTTP Error: ' . $response->status() . $response->body());

        } catch (\Illuminate\Http\Client\RequestException $e) {
            // Handle request exceptions (network issues, etc.)
            Log::error('Request Exception', [
                'message' => $e->getMessage(),
                'response' => $e->response?->body(),
            ]);
            return response()->json(['error' => 'Request failed: ' . $e->getMessage()], 500);

        } catch (Exception $e) {
            // Handle other exceptions
            Log::error('Exception', [
                'message' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public static function sendUpdateRequest($data, $brandId)
    {

        $array = $data->attributesToArray();

        $url = 'https://api.trendyol.com/sapigw/suppliers/' . $array['brandId'] . '/v2/products';

        $auth = Brand::where('brandSellerId', $array['brandId'])->value('brandAuth');

        $token = "Basic $auth";

        $itemBody = [
            'items' => [
                [
                    "barcode" => $array["productBarcode"],
                    "title" => $array["productTitle"],
                    "productMainId" => $array['productMainId'],
                    "brandId" => $array['brandId'],
                    "categoryId" => $array['categoryId'],
                    "quantity" => $array['quantity'],
                    "stockCode" => $array['stockCode'],
                    "dimensionalWeight" => $array['dimensionalWeight'],
                    "description" => $array['productDescription'],
                    "currencyType" => $array['productCurrencyType'],
                    "listPrice" => $array['productListPrice'],
                    "salePrice" => $array['productListPrice'],
                    "vatRate" => $array['productVatRate'],
                    "cargoCompanyId" => $array['cargoCompanyId'],
                    "images" => $array['productImages'],
                    "attributes" => $array['productAttributes'],
                ]
            ]
        ];

        try {
            // Log the request details
            Log::info('Sending POST request', [
                'url' => $url,
                'body' => $itemBody,
                'auth' => $auth // Be careful not to log sensitive auth information in production
            ]);

            $response = Http::withHeaders([
                'Authorization' => $token,
                'Content-Type' => 'application/json',
            ])->asJson()->put($url, $itemBody);

            // Log the response
            Log::info('Received response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            // Check if the request was successful
            if ($response->successful()) {
                return response()->json($response->json(), $response->status());
            }

            // If not successful, throw an exception
            throw new Exception('HTTP Error: ' . $response->status() . $response->body());

        } catch (\Illuminate\Http\Client\RequestException $e) {
            // Handle request exceptions (network issues, etc.)
            Log::error('Request Exception', [
                'message' => $e->getMessage(),
                'response' => $e->response?->body(),
            ]);
            return response()->json(['error' => 'Request failed: ' . $e->getMessage()], 500);

        } catch (Exception $e) {
            // Handle other exceptions
            Log::error('Exception', [
                'message' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }


}
