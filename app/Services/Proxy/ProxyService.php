<?php

namespace App\Services\Proxy;

use App\Models\Proxy;
use stringEncode\Exception;

class ProxyService
{
    /**
     * @throws Exception
     */
    public function getAvailableProxy(): Proxy
    {
        $proxy = Proxy::where('status', 0)->first();

        if (is_null($proxy)) {
            throw new Exception();
        }

       // $proxy->status = 1;
       // $proxy->save();

        return $proxy;
    }
}
