<?php

namespace usmanzafar\btcmarkets;

use GuzzleHttp\Client;
use usmanzafar\btcmarkets\Exception\RequestException;

/**
 * Class BTCApi
 *
 * @package usmanzafar\btcmarkets
 */
class BTCApi
{
    /** @var  string $privateKey */
    private $privateKey;
    /** @var  string $publicKey */
    private $publicKey;

    private $httpClient;

    /**
     * Create a new BTC Api Instance
     *
     * @param string $privateKey
     * @param string $publicKey
     * @param string $url
     */
    public function __construct(string $publicKey, string $privateKey, string $url)
    {
        $this->privateKey = base64_decode($privateKey);
        $this->publicKey = $publicKey;
        $this->httpClient = new Client([
            'base_uri' => $url,
            'timeout' => 10.0,

        ]);
    }

    /**
     * Helper to make the get request via Guzzle
     * @param $uri
     * @return mixed
     * @throws RequestException
     */
    private function makeGetRequest($uri)
    {
        try {
            $response = $this->httpClient->get($uri);
            return \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $ex) {
            throw new RequestException('Request could not be processed:'. $ex->getMessage());
        }
    }


    /**
     * Gets the current Market Tick in AUD
     *
     * @return array Returns the phrase passed in
     * @throws RequestException
     */
    public function getMarketRate()
    {
        $uri = 'market/BTC/AUD/tick';
        return $this->makeGetRequest($uri);
    }

    /**
     * Returns the last 50 trades executed in btc markets
     * @return mixed
     * @throws RequestException
     */
    public function getMarketRecentTrades($since = null)
    {
        $uri = 'market/BTC/AUD/trades';
        if ($since) {
            $uri .= '?since='.$since;
        }
        return$this->makeGetRequest($uri);
    }


    /**
     * Gets the order book atm for btc markets.
     *
     * Returns the array if all goes good with following keys
     *  - [currency] currency rates provided in
     *  - [instrument] type of crypto currency
     *  - [timestamp] timestamp on when the request was hit for oderbook
     *  - [asks] Collection of Sell / Asking prices i.e: people willing to buy the currency
     *      - [0] Bid value
     *      - [1] Volume of BTC offered
     *  - [bids] Collection of Buying orders / Bids: i.e: people willing to buy coins at the mention price.
     * Example: if you are a seller with some coins on hand then you will be looking @ the [bids] order book as
     * you will be seller and the bid book contains the list of sellers.
     * @return mixed
     * @throws RequestException
     */
    public function getMarketOrderBook()
    {
        $uri = 'market/BTC/AUD/orderbook';
        return $this->makeGetRequest($uri);
    }


    /**
     * - The current time should be the UTC
     * - Get the timestamp in microseconds
     * - Prepare a body ready for signature
     * - Calculate the SHA512 hmac [ ensure that actual bytes are returned]
     *  - Convert those bytes to string representation by using base64 encode
     * Retrieves the order history
     *
*@return mixed
     */
    public function getAccountBalance()
    {
        ini_set('date.timezone', 'UTC');
        $uri = '/account/balance';
        $timeStamp = \round(\   microtime(true) * 1000);
        $timeStamp = (string) $timeStamp;


        //$requestBody = '{"currency":"AUD","instrument":"BTC","limit":10,"since":373926138}';
        //$string = $uri.'\n'.$timeStamp.'\n'.$requestBody;
        $sBody = $uri.'\n'.$timeStamp.'\n';
        dump('The key after being base64_decode returns bytes');
        dump($this->privateKey);
        // Get the bytes
        $stringHMac = hash_hmac('sha512', $sBody, $this->privateKey, true);
        dump("Byte representation of HMAC using sha512");
        dump($stringHMac);
        // Convert the bytes using base64_encode
        $stringHMac = base64_encode($stringHMac);
        dump("String which is signed and encoded(base64), this will be sent in headers");
        dump($stringHMac);


        $headers = [
            'headers' => [
                'Accept' => 'application/json',
                'Accept-Charset' => 'UTF-8',
                'Content-Type' => 'application/json',
                'apikey' => $this->publicKey,
                'timestamp' => $timeStamp,
                'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.19 (KHTML, like Gecko) Chrome/1.0.154.53 Safari/525.19',
                'signature' => $stringHMac,
            ],

        ];
        dump($headers);
        $response = $this->httpClient->request('GET', $uri, $headers);
        return \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
    }
}
