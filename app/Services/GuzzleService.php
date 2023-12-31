<?php

namespace App\Services;

use App\Models\Proxy;
use GuzzleHttp\Client;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Http;
use stringEncode\Exception;

class GuzzleService
{
    /**
     * @throws HttpClientException
     */
    public function getRequest(string $url)
    {
        try {
            return Http::withOptions(['connect_timeout' => 15])->send('get', $url, [
                'allow_redirects' => [
                    'track_redirects' => true
                ]
            ]);
        } catch (Exception $e) {
            throw new HttpClientException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function headRequest(string $url)
    {
        return Http::send('get', $url, ['allow_redirects' => ['track_redirects' => true]]);
    }


}
