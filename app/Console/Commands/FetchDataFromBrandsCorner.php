<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\Product;
use GuzzleHttp\Exception\ClientException;

class FetchDataFromBrandsCorner extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-data-from-brands-corner';

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
        $response = $client->request('GET', 'https://brandscorner.pk/shop/');

        if ($response->getStatusCode() === 200) {

            $html = $response->getBody()->getContents();
            $crawler = new Crawler($html);

            $products = $crawler->filter('.shop_categories_list li a')->each(function ($node) {
                $name = $node->filter('.caption')->text();
                $link = $node->attr('href');

                return [
                    'name' => $name,
                    'link' => $link,
                ];

            });

            foreach ($products as $product) {
                try {
                    $response = $client->request('GET', $product['link']);

                    if ($response->getStatusCode() === 200) {
                        $html = $response->getBody()->getContents();
                        $crawler = new Crawler($html);

                        $products_details = $crawler->filter('.product-inner')->each(function (Crawler $node) {
                            $productName = $node->filter('.product-title h2 a')->text();
                            $productLink = $node->filter('.product-title h2 a')->attr('href');
                            $imageSrc = $node->filter('.main-image')->attr('src');
                            $priceElement = $node->filter('.product_after_shop_loop_price .price');
                            $originalPrice = $priceElement->filter('del bdi')->count() > 0 ? $priceElement->filter('del bdi')->text() : null;
                            $discountedPrice = $priceElement->filter('ins bdi')->count() > 0 ? $priceElement->filter('ins bdi')->text() : null;

                            if (!Product::where('title', $productName)->exists()) {
                            $product = new Product();
                            $product->title = $productName;
                            $product->price = $discountedPrice ?: $originalPrice;
                            $product->image = $imageSrc;
                            $product->link  = $productLink;
                            $product->save();
                            }
                        });

                    } else {
                        // Log or output a message indicating the non-200 status code
                        echo 'Skipping ' . $product['link'] . ' due to non-200 status code: ' . $response->getStatusCode() . PHP_EOL;
                    }
                } catch (ClientException $e) {
                    // Catch GuzzleHttp\ClientException for 404 errors
                    // Log or output a message indicating the 404 error
                    echo 'Skipping ' . $product['link'] . ' due to 404 error: ' . $e->getMessage() . PHP_EOL;
                }
            }
        }else{ 
            echo 'Skipping   $response->getStatusCode()' . PHP_EOL;
        }
    }
}
