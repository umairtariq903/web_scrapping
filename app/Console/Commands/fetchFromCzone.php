<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\Product;
use GuzzleHttp\Exception\ClientException;

class fetchFromCzone extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-from-czone';

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

        $categoryUrls =[
        "https://czone.com.pk/laptops-pakistan-ppt.74.aspx",
        "https://czone.com.pk/laptops-used-pakistan-ppt.715.aspx",
        "https://czone.com.pk/laptop-accessories-pakistan-ppt.202.aspx",
        "https://czone.com.pk/cameras-drones-pakistan-ppt.136.aspx",
        "https://czone.com.pk/cartridges-toners-pakistan-ppt.150.aspx",
        "https://czone.com.pk/casing-pakistan-ppt.168.aspx",
        "https://czone.com.pk/cooling-solutions-pakistan-ppt.141.aspx",
        "https://czone.com.pk/desktop-computers-pakistan-ppt.227.aspx",
        "https://czone.com.pk/gaming-consoles-pakistan-ppt.506.aspx",
        "https://czone.com.pk/gaming-products-pakistan-ppt.146.aspx",
        "https://czone.com.pk/graphic-cards-pakistan-ppt.154.aspx",
        "https://czone.com.pk/graphic-tablets-pakistan-ppt.165.aspx",
        "https://czone.com.pk/hard-drives-pakistan-ppt.93.aspx",
        "https://czone.com.pk/headsets-headphones-mic-pakistan-ppt.175.aspx",
        "https://czone.com.pk/keyboard-pakistan-ppt.162.aspx",
        "https://czone.com.pk/lcd-led-monitors-pakistan-ppt.108.aspx",
        "https://czone.com.pk/memory-cards-pakistan-ppt.143.aspx",
        "https://czone.com.pk/memory-module-ram-pakistan-ppt.127.aspx",
        "https://czone.com.pk/motherboards-pakistan-ppt.157.aspx",
        "https://czone.com.pk/mouse-pakistan-ppt.95.aspx",
        "https://czone.com.pk/network-products-pakistan-ppt.192.aspx",
        "https://czone.com.pk/peripherals-misc-pakistan-ppt.244.aspx",
        "https://czone.com.pk/power-supply-pakistan-ppt.183.aspx",
        "https://czone.com.pk/printers-pakistan-ppt.90.aspx",
        "https://czone.com.pk/processors-pakistan-ppt.85.aspx",
        "https://czone.com.pk/projectors-pakistan-ppt.252.aspx",
        "https://czone.com.pk/smart-watches-pakistan-ppt.403.aspx",
        "https://czone.com.pk/softwares-pakistan-ppt.103.aspx",
        "https://czone.com.pk/solid-state-drives-ssd-pakistan-ppt.263.aspx",
        "https://czone.com.pk/speakers-pakistan-ppt.97.aspx",
        "https://czone.com.pk/stabilizer-pakistan-ppt.237.aspx",
        "https://czone.com.pk/mobile-phones-pakistan-ppt.242.aspx",
        "https://czone.com.pk/tablet-pc-pakistan-ppt.278.aspx",
        "https://czone.com.pk/tablet-accessories-pakistan-ppt.318.aspx",
        "https://czone.com.pk/tv-devices-streaming-media-players-pakistan-ppt.180.aspx",
        "https://czone.com.pk/ups-pakistan-ppt.132.aspx",
        "https://czone.com.pk/usb-flash-drives-pakistan-ppt.82.aspx",
        "https://czone.com.pk/used-products-pakistan-ppt.221.aspx"];

        foreach ($categoryUrls as $baseURL) {

        $response = $client->request('GET', $baseURL);
        if ($response->getStatusCode() === 200) {
            $html = $response->getBody()->getContents();
            $crawler = new Crawler($html);

            // Extract total pages from pagination
            $totalPages = 1;
            $pagination = $crawler->filter('.pagination li');
            if ($pagination->count() > 0) {
                $totalPages = $pagination->last()->filter('a')->attr('href');
                preg_match('/page=(\d+)$/', $totalPages, $matches);
                $totalPages = !empty($matches[1]) ? intval($matches[1]) : 1;
            }

            // Loop through all pages and scrape data
            for ($page = 1; $page <= $totalPages; $page++) {
                $url = "{$baseURL}?recs=10&page={$page}";
                $response = $client->request('GET', $url);
                $html = $response->getBody()->getContents();
                $crawler = new Crawler($html);

                // Iterate over each product element
                $crawler->filter('.template')->each(function (Crawler $template) {
                    try {
                        // Extract product data from the current template
                        $product_title = $template->filter('h4 a')->text();
                        $product_link = $template->filter('h4 a')->attr('href');
                        $product_price = $template->filter('.price ')->text(); // Adjust this selector as needed
                        $imageSrc = $template->filter('.image img')->attr('src');
                        $url = 'https://czone.com.pk';
                        if (!Product::where('title', $product_title)->exists()) {
                            Product::create([
                                'title' => $product_title,
                                'price' => $product_price,
                                'image' => $url.$imageSrc,  // Make sure to have the correct image source variable
                                'link' => $url.$product_link,
                            ]);
                        }
                    } catch (\Exception $e) {
                        // Handle exception
                        // Log or display an error message
                        // echo $e->getMessage();
                    }
                });  
            }          
            }
        }
    }
}
