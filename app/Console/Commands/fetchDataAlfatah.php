<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\Product;
use GuzzleHttp\Exception\ClientException;


class fetchDataAlfatah extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-data-alfatah';

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
        $urls = [
            "https://www.alfatah.com.pk/product-category/online-shop-for-split-air-conditioner-in-pakistan/",
            "https://www.alfatah.com.pk/product-category/led-tv-price-in-pakistan/",
            "https://www.alfatah.com.pk/product-category/online-shop-for-washing-machine-in-pakistan/",
            "https://www.alfatah.com.pk/product-category/online-shop-for-refrigerators-in-pakistan/",
            "https://www.alfatah.com.pk/product-category/air-cooler-water-cooler/",
            "https://www.alfatah.com.pk/product-category/online-shop-for-small-kitchen-items-in-pakistan/",
            "https://www.alfatah.com.pk/product-category/online-shop-for-3-burners-cooking-range-in-pakistan/",
            "https://www.alfatah.com.pk/product-category/online-shop-for-water-dispenser-in-pakistan/",
            "https://www.alfatah.com.pk/product-category/online-shop-for-storage-type-gas-geysers-in-pakistan/",
            "https://www.alfatah.com.pk/product-category/mobiles-tablets/"
        ];
        foreach ($urls as $url) {
            try {
                $response = $client->request('GET', $url . '?view=grid-view&per_page=1000');

                if ($response->getStatusCode() === 200) {
                    $html = $response->getBody()->getContents();
                    $crawler = new Crawler($html);

                    $products = [];

                    $crawler->filter('.product-wrapper')->each(function (Crawler $productNode) use (&$products) {
                        // Extract product details
                        $product = [];
                        $product['title'] = $productNode->filter('.product-title a')->text();
                        $product['link'] = $productNode->filter('.product-title a')->attr('href');
                        $product['image'] = $productNode->filter('.product-image img')->attr('src'); // Fetch image URL

                        // Fetch price with currency symbol
                        $priceNode = $productNode->filter('.product-price .price ins');
                        if ($priceNode->count() > 0) {
                            $product['price'] = $priceNode->text();
                        } else {
                            $originalPrice = $productNode->filter('.product-price .price del');
                            if ($originalPrice->count() > 0) {
                                $product['price'] = $originalPrice->text();
                            } else {
                                $product['price'] = 'Please click here for price'; // Fetch original price with currency symbol
                            }
                        }

                        // Add the product to the products array
                        $products[] = $product;
                    });

                    foreach ($products as $product) {
                        Product::updateOrCreate(
                            ['title' => $product['title']],
                            ['price' => $product['price'], 'image' => $product['image'], 'link' => $product['link']]
                        );
                    }
                }
            } catch (\Exception $e) {
                // Handle any exceptions
                echo "Error: " . $e->getMessage();
            }
        }
    }
}
