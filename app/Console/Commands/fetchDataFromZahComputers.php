<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\Product;
use GuzzleHttp\Exception\ClientException;

class fetchDataFromZahComputers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-data-from-zah-computers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $client = new Client();

        $productData = [];

        for ($page = 1; $page <= 360; $page++) {
            $url = 'https://zahcomputers.pk/shop/page/' . $page . '/';

            $response = $client->request('GET', $url);

            if ($response->getStatusCode() === 200) {
                $html = $response->getBody()->getContents();
                $crawler = new Crawler($html);

                // Filter the product elements
                $products = $crawler->filter('.wd-product');

                if ($products->count() > 0) {
                    
                    $products->each(function (Crawler $product) use ($url) {
                    try {
                        // Extract product name
                        $productName = $product->filter('h3.wd-entities-title a')->text();
        
                        // Extract product price
                        $productPrice = $product->filter('.wrap-price .price')->text();
        
                        // Extract product image URL
                        $imageUrl = $product->filter('.wd-product-grid-slide')->first()->attr('data-image-url');
        
                        // Extract product link
                        $productLink = $product->filter('.wd-entities-title a')->attr('href');
        
                        // Check if any required field is empty
                        
        
                        // Save the data into the 'products' table
                        Product::create([
                            'title' => $productName,
                            'price' => $productPrice,
                            'image' => $imageUrl,
                            'link' =>  $productLink, // Combine with the base URL
                        ]);
                        
                    } catch (\Exception $e) {
                        // Handle the exception (skip the current iteration)
                        echo $e->getMessage() . PHP_EOL;
                    }
                
                });
            }else {
                $this->info('No products found on ' . $url);
            }
            }
        }
    }
}
