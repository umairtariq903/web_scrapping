<?php

namespace App\Console\Commands;

use App\Models\Product;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Exception\ClientException;

class fetchDataFromHighPk extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-data-from-high-pk';

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
        $response = $client->request('GET', 'https://highfy.pk/pages/brand');

        if ($response->getStatusCode() === 200) {
            $html = $response->getBody()->getContents();

            $crawler = new Crawler($html);

            $brands = $crawler->filter('.brand-item a')->each(function (Crawler $node) {
                $brandName = $node->text();
                $brandLink = $node->attr('href');

                return [
                    'brand_name' => $brandName,
                    'brand_link' => $brandLink,
                ];
            });


            foreach ($brands as $brand) {
                try {
                $response = $client->request('GET', 'https://highfy.pk/' . $brand['brand_link']);

                if ($response->getStatusCode() === 200) {
                    $html = $response->getBody()->getContents();

                    $crawler = new Crawler($html);


                    $products = $crawler->filter('.grid-item')->each(function (Crawler $node) {
                        $url = $node->filter('.product-title')->attr('href');
                        $title = $node->filter('.product-title')->text();
                        $onSale = $node->filter('.price-sale')->count() > 0;

                        if ($onSale) {
                            $compareAtPrice = $node->filter('.old-price')->text();
                            $price = $node->filter('.special-price')->text();
                        } else {
                            $price = $node->filter('.price-regular span')->text();
                        }

                        // Extract image details
                        $image = $node->filter('.product-grid-image img')->attr('data-srcset');
                        if (!Product::where('title', $title)->exists()) {
                        $product = new Product();
                        $product->title = $title;
                        $product->price = $price;
                        $product->image = $image;
                        $product->link = 'https://highfy.pk' . $url;
                        $product->save();
                        }

                    });
                    // Output the extracted product data for debugging
                }else {
                    // Log or output a message indicating the non-200 status code
                    echo 'Skipping ' . $brand['brand_link'] . ' due to non-200 status code: ' . $response->getStatusCode() . PHP_EOL;
                }
            } catch (ClientException $e) {
                // Catch GuzzleHttp\ClientException for 404 errors
                // Log or output a message indicating the 404 error
                echo 'Skipping ' . $brand['brand_link'] . ' due to 404 error: ' . $e->getMessage() . PHP_EOL;
            }
            }
        }

        // Handle the case where the request was not successful
        return response()->json(['error' => 'Failed to fetch data'], 500);
    }
}
