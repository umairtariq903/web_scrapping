<?php

namespace App\Console\Commands;

use App\Models\Product;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Symfony\Component\DomCrawler\Crawler;

class fetchDataFromOrganictraveller extends Command
{
    protected $signature = 'app:organic_traveller';
    protected $description = 'Scrape ecommerce data and store in the database';

    public function handle()
    {
        $client = new Client();
        $response = $client->request('GET', 'https://organictraveller.store/collections/all');

        // Check if the request was successful (status code 200)
        if ($response->getStatusCode() === 200) {
            // Get the HTML content from the response
            $html = $response->getBody()->getContents();

            // Use Symfony DomCrawler for parsing the HTML
            $crawler = new Crawler($html);

            // Perform your scraping logic here
            $products = $crawler->filter('.grid__item')->each(function (Crawler $node) {
                $title = $node->filter('.card__heading a')->text();
                $priceNode = $node->filter('.price__regular');
                $price = $this->extractNumericValue($priceNode->text());
                $image = $node->filter('img')->attr('src');
                $link = $node->filter('.card__heading a')->attr('href');
                $image = str_replace('//', '', $image);

                // Save to the database
                if (!Product::where('title', $title)->exists()) {
                $product = new Product();
                $product->title = $title;
                $product->price = $price;
                $product->image = $image;
                $product->link = 'https://organictraveller.store' . $link;
                $product->save();
                }

                return [
                    'title' => $title,
                    'price' => $price,
                    'image' => $image,
                    'link' => 'https://organictraveller.store' . $link,
                ];

            $this->info('Scraping completed successfully!');
        }); 
    }else {
            $this->error('Failed to fetch data');
        }
    }

    private function extractNumericValue($text)
    {
        // Use regular expression to extract numeric part
        preg_match('/[0-9,.]+/', $text, $matches);
        return isset($matches[0]) ? str_replace(',', '', $matches[0]) : null;
    }
}
