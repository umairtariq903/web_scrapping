<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\Product;
use GuzzleHttp\Exception\ClientException;

class fetchFromRebeltech extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-from-rebeltech';

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

        $categoryUrls = [
            'https://rebeltech.pk/product-category/digital-electronic/game-console/?product_per_page=-1',
            'https://rebeltech.pk/product-category/digital-electronic/home-audio/?product_per_page=-1',
            'https://rebeltech.pk/product-category/digital-electronic/printer-supplies/?product_per_page=-1',
            'https://rebeltech.pk/product-category/digital-electronic/tv-video/?product_per_page=-1',
            'https://rebeltech.pk/product-category/audio-headphones/?product_per_page=-1',
            'https://rebeltech.pk/product-category/audio-headphones/headphone/?product_per_page=-1',
            'https://rebeltech.pk/product-category/camera-accessories/?product_per_page=-1',
            'https://rebeltech.pk/product-category/laptop-computer/computer/?product_per_page=-1',
            'https://rebeltech.pk/product-category/laptop-computer/keyboard/?product_per_page=-1',
            'https://rebeltech.pk/product-category/audio-headphones/?product_per_page=-1',
            'https://rebeltech.pk/product-category/laptop-computer/moniter/?product_per_page=-1',
            'https://rebeltech.pk/product-category/laptop-computer/mouse/?product_per_page=-1',
            'https://rebeltech.pk/product-category/digital-electronic/printer-supplies/?product_per_page=-1',
            'https://rebeltech.pk/product-category/laptop-computer/laptop/?product_per_page=-1',
            'https://rebeltech.pk/product-category/laptop-computer/storage/?product_per_page=-1',
            'https://rebeltech.pk/product-category/camera-accessories/cameras/?product_per_page=-1',
            'https://rebeltech.pk/product-category/camera-accessories/lens-frames/?product_per_page=-1',
            'https://rebeltech.pk/product-category/camera-accessories/accessories/?product_per_page=-1',
            'https://rebeltech.pk/product-category/digital-electronic/tv-video/?product_per_page=-1',
            'https://rebeltech.pk/product-category/laptop-computer/storage/?product_per_page=-1',
        ];

        foreach ($categoryUrls as $url) {

            $response = $client->request('GET', $url);

            if ($response->getStatusCode() === 200) {
                $html = $response->getBody()->getContents();
                $crawler = new Crawler($html);

                // Extract product name
                // Iterate over each product element
                $crawler->filter('li.product-item')->each(function (Crawler $product) {
                    try {
                        // Extract product data

                        $product_title = $product->filter('.product-title a')->text();
                        $product_link = $product->filter('.product-title a')->attr('href');
                        $product_price = $product->filter('.woocommerce-Price-amount')->text();
                        $imageSrc =     $product->filter('.primary-thumb img')->attr('src');

                        if (!Product::where('title', $product_title)->exists()) {
                            Product::create([
                            'title' => $product_title,
                            'price' => $product_price,
                            'image' => $imageSrc,  // Make sure to have the correct image source variable
                            'link' => $product_link,
                        ]);
                    }
                    } catch (\Exception $e) {
                        // Handle the exception (skip the current iteration)
                        echo $e->getMessage() . PHP_EOL;
                    }
                });
            };
        }
    }
}
