<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\Product;
use GuzzleHttp\Exception\ClientException;

class WebScraperController extends Controller
{
    public function scrapeEcommerceData()
    {
        $client = new Client([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
            ]
        ]);

        $response = $client->request('GET', 'https://heimdall.laam.pk/brands/all-brands');

        if ($response->getStatusCode() === 200) {
            $brands = json_decode($response->getBody()->getContents(), true);
            
            foreach ($brands as $brand) { // Access 'brands' key
                $endCursor = null; // Initialize end cursor

                do {
                    $url = 'https://heimdall.laam.pk/brands/' . $brand['handle'] . '/products?customer_email=&limit=28&logan_id=null';
                    info($url);
                    if ($endCursor) {
                        // Append end cursor to URL for paginated requests
                        $url .= '&end_cursor=' . urlencode($endCursor); // Correct appending
                    }
            
                    $response = $client->request('GET', $url);
            
                    if ($response->getStatusCode() === 200) {
                        $responseData = json_decode($response->getBody()->getContents(), true);
                        
                        // Extract products from the response
                        $products = $responseData['products'];
                        foreach ($products as $product) {
                            Product::updateOrCreate(
                                ['title' => $product['title']],
                                [
                                    'price' => $product['priceRange']['minVariantPrice']['amount'], // Correct access to nested array
                                    'image' => $product['featuredImage']['src'], // Correct access to nested array
                                    'link' => 'https://laam.pk/products/' . $product['handle'] 
                                ]
                            );
                        }
                        // Update end cursor for the next request
                        $endCursor = $responseData['end_cursor'];
                    } else {
                        echo "Failed to retrieve data. Status code: " . $response->getStatusCode() . "\n";
                        break; // Exit loop if request fails
                    }
                } while ($endCursor);
            }
            
        }
    }
}
