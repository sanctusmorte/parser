<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use stringEncode\Exception;

class GuzzleService
{
    /**
     * @throws Exception
     */
    public function getRequest(string $url)
    {
        try {
            return Http::withoutVerifying()->send('get', $url, ['allow_redirects' => ['track_redirects' => true]]);
        } catch (\Exception $e) {
            throw new Exception($e);
        }

    }

    public function headRequest(string $url)
    {
        return Http::send('get', $url, ['allow_redirects' => ['track_redirects' => true]]);
    }


}
