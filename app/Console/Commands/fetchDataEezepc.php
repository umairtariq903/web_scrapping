<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\Product;
use GuzzleHttp\Exception\ClientException;

class fetchDataEezepc extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-data-eezepc';

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
            "https://eezepc.com/product-category/smartphones-tablets/",
            "https://eezepc.com/product-category/computer-components/",
            "https://eezepc.com/product-category/computers-office/",
            "https://eezepc.com/product-category/consoles-gaming/",
            "https://eezepc.com/product-category/wearable-gadgets/",
            "https://eezepc.com/product-category/home-appliances/",
            "https://eezepc.com/product-category/tv-audio-video/",
        ];

        foreach ($categoryUrls as $url) { {
                $response = $client->request('GET', $url);

                if ($response->getStatusCode() === 200) {
                    $html = $response->getBody()->getContents();
                    $crawler = new Crawler($html);

                    $links = [];
                    $names = [];

                    $subMenus = $crawler->filter('.wd-sub-menu');

                    $subMenus->each(function (Crawler $subMenu) use (&$links, &$names) {
                        // Find links within the submenu
                        $subMenu->filter('li > a')->each(function (Crawler $link) use (&$links, &$names) {
                            // Get the href attribute of the link
                            $href = $link->attr('href');
                            // Get the text of the link
                            $name = $link->text();

                            // Append href and name to the arrays
                            $links[] = $href;
                            $names[] = $name;
                        });
                    });

                    foreach ($links as $baseUrl) {

                        if ($baseUrl) {
                            $page = 1;
                            try {
                                do {
                                    // Construct the URL for the current page
                                    $url = $baseUrl . 'page/' . $page;

                                    // Send a GET request to the URL
                                    $response = $client->request('GET', $url);

                                    // Check if the request was successful (status code 200)
                                    if ($response->getStatusCode() === 200) {
                                        // Get the HTML content of the page
                                        $html = $response->getBody()->getContents();

                                        // Create a new crawler instance
                                        $crawler = new Crawler($html);

                                        // Loop through each product element
                                        $crawler->filter('.wd-product')->each(function (Crawler $productNode) {
                                            try {
                                                // Extract product information
                                                $title = $productNode->filter('.wd-entities-title a')->text();
                                                $link = $productNode->filter('.wd-entities-title a')->attr('href');
                                                $price = $productNode->filter('.price')->text();
                                                $image = $productNode->filter('.product-image-link img')->attr('src');

                                                // Create or update product in the database
                                                Product::updateOrCreate(
                                                    ['title' => $title],
                                                    ['price' => $price, 'image' => $image, 'link' => $link]
                                                );
                                            } catch (\Exception $e) {
                                                echo $e->getMessage();
                                            }
                                        });

                                        // Move to the next page
                                        $page++;

                                        $nextLink = $crawler->filter('.next.page-numbers')->first();
                                    } else {
                                        // Stop pagination if the request fails or the status code is not 200
                                        break;
                                    }
                                } while ($nextLink->count() > 0);
                            } catch (\Exception $e) {
                                echo $e->getMessage();
                            }
                        }
                    }
                }
            }
        }
    }
}
