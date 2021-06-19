<?php

namespace FulgerX2007\GsmArena;

/**
 * Class GsmArena
 */
class GsmArenaApi
{
    public $base_url = 'https://www.gsmarena.com/';
    public $simbol = ['&', '+'];
    public $kata = ['_and_', '_plus_'];

    public function __construct()
    {
        require_once 'simple_html_dom.php';
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
    }

    /**
     * @param $url
     * @return bool|string
     */
    protected function myCurl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, 'Googlebot/2.1 (http://www.googlebot.com/bot.html)');
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);

        if (!$site = curl_exec($ch)) {
            return 'offline';
        }

        return $site;
    }

    /**
     * @return array
     */
    public function getBrands(): array
    {
        $result = [];
        $url = 'https://www.gsmarena.com/makers.php3';
        $ngecurl = $this->myCurl($url);
        if ($ngecurl === 'offline') {
            $result['status'] = 'error';
            $result['data'] = [];
        } else {
            $html = str_get_html($ngecurl);
            $div = $html->find('div[class=st-text] table', 0);

            if ($div->find('tr', 0)) {
                $result['status'] = 'success';
                foreach ($div->find('tr') as $tr) {
                    foreach ($tr->find('td') as $td) {
                        $grid = $td->find('a', 0);
                        $href = $grid->href;
                        $str = preg_replace('~<span(.*?)</span>~Usi', '', $grid->innertext);
                        $title = strip_tags($str);
                        $count = $grid->find('span', 0);
                        $count = explode(' ', $count)[0];
                        $result['data'][] = [
                            'title' => $title,
                            'count' => strip_tags($count),
                            'href' => $href,
                        ];
                    }
                }
            } else {
                $result['status'] = 'error';
                $result['data'] = [];
            }
        }
        return $result;
    }

    /**
     * @param string $url
     * @return array
     */
    public function getNavigators(string $url): array
    {
        $result = [];
        $ngecurl = $this->myCurl($url);
        if ($ngecurl === 'offline') {
            $result['status'] = 'error';
            $result['data'] = [];
        } else {
            $html = str_get_html($ngecurl);
            $div = $html->find('div[class=nav-pages]', 0);
            if ($div && $div->find('a', 0)) {
                $result['status'] = 'success';
                foreach ($div->find('a') as $tr) {
                    $href = $this->base_url . $tr->href;
                    $result['data'][] = [
                        'href' => $href,
                    ];
                }
            } else {
                $result['status'] = 'error';
                $result['data'] = [];
            }
        }
        return $result;
    }

    /**
     * @param array $pages
     * @return array
     */
    public function getProducts(array $pages): array
    {
        $products = [
            'data' => [],
            'status' => null,
        ];
        foreach ($pages as $page) {
            $result = [];
            $ngecurl = $this->myCurl($page);
            if ($ngecurl === 'offline') {
                $result['status'] = 'error';
                $result['data'] = [];
            } else {
                $html = str_get_html($ngecurl);
                $div = $html->find('div[class=makers]', 0);
                if ($div->find('li', 0)) {
                    $result['status'] = 'success';
                    foreach ($div->find('li') as $li) {
                        $grid = $li->find('a', 0);
                        $title = $grid->find('span', 0);
                        $slug = str_replace('.php', '', $grid->href);
                        $result['data'][] = array(
                            'title' => str_replace('<br>', ' ', $title->innertext),
                            'slug' => str_replace($this->simbol, $this->kata, $slug)
                        );
                    }
                }
            }
            $products['status'] = $result['status'] === 'error' || $products['status'] === 'error' ? 'error' : 'success';
            $products['data'] = array_merge($products['data'], $result['data']);
        }
        return $products;
    }

    /**
     * @param string $q
     * @return array
     */
    public function search($q = ''): array
    {
        $result = [];
        $brands = $this->getBrands();
        $nameArray = array_column($brands['data'], 'title');
        $nameArray = array_map('strtolower', $nameArray);
        $key = array_search(strtolower($q), $nameArray, true);
        if ($key) {
            $url = $this->base_url . $brands['data'][$key]['href'];
            $pages = $this->getNavigators($url);
            $pages = array_column($pages['data'], 'href');
            array_unshift($pages, $url);
            return $this->getProducts($pages);
        }

        $url = 'https://www.gsmarena.com/results.php3?sQuickSearch=yes&sName=' . urlencode($q);
        $ngecurl = $this->myCurl($url);
        if ($ngecurl === 'offline') {
            $result['status'] = 'error';
            $result['data'] = array();
        } else {
            $html = str_get_html($ngecurl);
            $div = $html->find('div[class=makers]', 0);
            if ($div->find('li', 0)) {
                $result['status'] = 'success';
                foreach ($div->find('li') as $li) {
                    $grid = $li->find('a', 0);
                    $title = $grid->find('span', 0);
                    $slug = str_replace('.php', '', $grid->href);
                    $result['data'][] = [
                        'title' => str_replace('<br>', ' ', $title->innertext),
                        'slug' => str_replace($this->simbol, $this->kata, $slug)
                    ];
                }
            } else {
                $result['status'] = 'error';
                $result['data'] = [];
            }
        }
        return $result;
    }

    /**
     * @param string $slug
     * @return array
     */
    public function getDeviceDetail($slug = ''): array
    {
        $result = [];

        $url = 'https://www.gsmarena.com/' . str_replace($this->kata, $this->simbol, $slug) . '.php';
        $ngecurl = $this->myCurl($url);

        if ($ngecurl === 'offline') {
            $result['status'] = 'error';
            $result['data'] = array();
        } else {
            $html = str_get_html($ngecurl);
            if ($html->find('title', 0)->innertext === '404 Not Found') {
                $result['status'] = 'error';
                $result['data'] = array();
            } else {
                $result['status'] = 'success';
                $result['title'] = $html->find('h1[class=specs-phone-name-title]', 0)->innertext;

                $img_div = $html->find('div[class=specs-photo-main]', 0);
                $result['img'] = $img_div->find('img', 0)->src;

                // Manipulasi DOM menggunakan library simple html dom. Find div dengan nama class specs-list
                $div = $html->find('div[id=specs-list]', 0);

                foreach ($div->find('table') as $table) {
                    $th = $table->find('th', 0);
                    // Membuat array. Find tr from table
                    foreach ($table->find('tr') as $tr) {
                        $tr->find('td', 0) == '&nbsp;' ? $ttl = 'empty' : $ttl = $tr->find('td', 0);
                        $search = ['.', ',', '&', '-', ' '];
                        $replace = ['', '', '', '_', '_'];
                        $ttl = strtolower(str_replace($search, $replace, $ttl));
                        $nfo = $tr->find('td', 1);
                        $result['data'][strtolower($th->innertext)][] = [
                            strip_tags($ttl) => strip_tags($nfo)
                        ];
                    }
                }
                $search = ['},{', '[', ']', '","nbsp;":"', 'nbsp;', ' - '];
                $replace = [',', '', '', '<br>', '', '<br>- '];
                $newjson = str_replace($search, $replace, json_encode($result));
                $result = json_decode($newjson, true, 512);
            }
        }
        return $result;
    }
}
