<?php

/**
 *
 * @file
 * @version
 * @copyright 2017 CN-Consult GmbH
 * @author Patrick Hausmann <privat@patrick-designs.de>
 */


namespace WpKeywordMonitor;


use WpKeywordMonitor\model\Keyword;
use WpKeywordMonitor\Model\KeywordResult;

class RankChecker
{
    private $apiKey;
    private $cx;
    private $domain;

    function __construct($_apiKey, $_cx, $_domain)
    {
        $this->apiKey = $_apiKey;
        $this->cx = $_cx;
        $this->domain = $_domain;
    }

    public function calculateKeywordResultOfKeyword(Keyword $_keyword, $_searchDepth=1)
    {
        if (!$_searchDepth) $_searchDepth = 1;
        for ($i=1; $i<=$_searchDepth; $i++)
        {
            $curl = curl_init();
            $keyword  = urlencode($_keyword->keyword);
            $url = "https://www.googleapis.com/customsearch/v1?key=$this->apiKey&cx=$this->cx&q=$keyword&filter=1&start=$i&num=10&alt=json";
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

            $response = curl_exec($curl);
            $jsonDecoded = json_decode($response);
            $position = null;
            $found = false;

            if ($jsonDecoded) {
                if (isset($jsonDecoded->items))
                {
                    $position = 1;
                    foreach ($jsonDecoded->items as $entry) {
                        if (strpos($entry->formattedUrl, $this->domain) !== false) {
                            $found = true;
                            break;
                        }

                        $position++;
                    }
                }
                else if (isset($jsonDecoded->error) && $jsonDecoded->error->errors[0]->reason == "dailyLimitExceeded")
                {

                    return __("Daily limit exceeded (".date("Y-m-d H:i:s").")", WP_KEYWORD_MONITOR_TEXT_DOMAIN);
                }
                else return __("Unknown error (".date("Y-m-d H:i:s").")", WP_KEYWORD_MONITOR_TEXT_DOMAIN);

                curl_close($curl);

                if ($found===false) $position = 0;

                return new KeywordResult($_keyword->id, $position);
            }
        }

        return null;
    }
}