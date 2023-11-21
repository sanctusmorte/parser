<?php

namespace App\Http\Controllers;

use App\Models\Pornstar;
use App\Models\Site;
use App\Models\Tag;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function pornstars()
    {
        $handle = fopen("/var/www/parser/app/Http/Controllers/pornstars.csv", "r");

        $insertData = [];

        if ($handle) {
            while (($line = fgets($handle)) !== false) {

                $line = trim(preg_replace('/\s\s+/', ' ', $line));
                $data = explode('|', $line);
                $externalId = $data[0];
                $externalFullName = $data[1];
                $externalFullName = substr($externalFullName, 0, 255);
                $names = explode(' ', $externalFullName);
                $firstName = $names[0] ?? '';
                $lastName = $names[1] ?? '';

                $insertData[] = [
                    'external_id' => $externalId,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'external_full_name' => $externalFullName,
                    'status' => 0,
                    'is_changeable' => 1,
                ];

                if (count($insertData) > 1000) {
                    Pornstar::upsert($insertData, ['external_id'], ['first_name', 'last_name', 'external_full_name']);
                    $insertData = [];
                }
            }

            fclose($handle);
        }
    }

    public function tags()
    {
        $handle = fopen("/var/www/parser/app/Http/Controllers/tags.csv", "r");

        $insertData = [];

        if ($handle) {
            while (($line = fgets($handle)) !== false) {

                $line = trim(preg_replace('/\s\s+/', ' ', $line));
                $data = explode('|', $line);
                $externalId = $data[0];
                $name = $data[1];
                $name = substr($name, 0, 255);

                $insertData[] = [
                    'external_id' => $externalId,
                    'name' => $name,
                    'status' => 0,
                    'is_changeable' => 1,
                ];

                if (count($insertData) > 1000) {
                    Tag::upsert($insertData, ['external_id'], ['name']);
                    $insertData = [];
                }
            }

            fclose($handle);
        }
    }

    public function categories()
    {
        $handle = fopen("/var/www/parser/app/Http/Controllers/categories.csv", "r");

        $insertData = [];

        if ($handle) {
            while (($line = fgets($handle)) !== false) {

                $line = trim(preg_replace('/\s\s+/', ' ', $line));
                $data = explode('|', $line);
                $externalId = $data[0];
                $name = $data[1];
                $name = substr($name, 0, 255);

                $insertData[] = [
                    'external_id' => $externalId,
                    'name' => $name,
                    'status' => 0,
                    'is_changeable' => 1,
                ];

                if (count($insertData) > 1000) {
                    Tag::upsert($insertData, ['external_id'], ['name']);
                    $insertData = [];
                }
            }

            fclose($handle);
        }
    }

    public function sites()
    {
        $handle = fopen("/var/www/parser/app/Http/Controllers/sites.txt", "r");

        $insertData = [];

        if ($handle) {
            while (($line = fgets($handle)) !== false) {

                $line = trim(preg_replace('/\s\s+/', ' ', $line));
                $domain = substr($line, 0, 255);
                $fullDomain = 'https://' . $domain;

                $insertData[] = [
                    'domain' => $domain,
                    'full_domain' => $fullDomain,
                ];

                if (count($insertData) > 1000) {
                    Site::upsert($insertData, ['domain'], ['domain', 'full_domain']);
                    $insertData = [];
                }
            }

            fclose($handle);
        }
    }
}
