<?php
/**
 * Created by PhpStorm.
 * User: llf
 * Date: 2018/12/25
 * Time: 16:13
 */
require './vendor/autoload.php';
require './vendor/lib/Spider.php';
include 'E:/www/global';

use Symfony\Component\DomCrawler\Crawler;

$baseUrl = 'https://hh.flexui.win/thread0806.php?fid=22';
$pagePath = './crawFiles/';
$spider = new Spider();
$spider->setUnCheckSsl()
    ->setReturnStream()
    ->setHeader([
        'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
        #'accept-encoding' => 'gzip, deflate, br', // 发送编码之后的数据
        'accept-language' => 'zh-CN,zh;q=0.9',
        'cache-control' => 'no-cache',
        'pragma' => 'no-cache',
        'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8',
        'upgrade-insecure-requests' => '1',
        'user-agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.67 Safari/537.36',
    ]);

for ($i = 1, $cnt = 20; $i <= $cnt; $i ++) {
    if ($i == 2) die;
    $url = $baseUrl . '&page='. $i;
//    echo $url . "\n";
    $htmlPath = '';
    if (!is_file($pagePath . $i . '/F')) {
        $html = $spider->post($url);
        preg_match("/<body>(.*?)<\/body>/s", $html, $m);
        if (!is_dir($pagePath . $i)) {
            mkdir($pagePath. $i, 0777);
        }
        file_put_contents($pagePath . $i . '/F', $m[0]);
    }
    $htmlPath = $pagePath . $i . '/F';
    $htmlStr = file_get_contents($htmlPath);
    $data = [];
    $crawler = new Crawler($htmlStr);
    try {
        $contentTable = $crawler->filterXPath('//div[@class="t"][2]/table');
        $tdDom = $contentTable->filterXPath('//tr[contains(@class,"tr3 t_one tac")]/td')->each(function (Crawler $node, $i) use ($baseUrl, &$data) {
            if ($node->attr('class')) {
                $title = preg_replace('/\s/', '', $node->text());
                $aDom = $node->filterXPath('//a');
                if (strpos($title, '↑') !== false) {
                    return;
                }
                $href = pathinfo($baseUrl);
                $href = $href['dirname'] . '/'. $aDom->attr('href');
                $data[] = [
                    'href' => $href,
                    'title' => $title
                ];
            }
        });
        // then craw next page
        if (!empty($data)) {
            $htmlPath = $htmlStr = '';
            $crawler = null;
            foreach ($data as $key => $row) {
                $itemIndex = $key + 1;
                if (!is_file($pagePath . $i . '/S'. $itemIndex)) {
                    $html = $spider->post($row['href']);
                    file_put_contents($pagePath . $i .'/S'. $itemIndex, $html);
                }
                $htmlPath = $pagePath . $i . '/S'. $itemIndex;
                echo $htmlPath . "\n";
                $htmlStr = file_get_contents($htmlPath);
                $data = [];
                $crawler = new Crawler($htmlStr);
                $sourceTitle = $crawler->filterXPath('//h4')->text();
                $source = $crawler->filterXPath('//div[contains(@class,"tpc_content")]/a[2]')->attr('onclick');
                $source = explode('src=', $source);
                $source = trim($source[1], '\'');
                echo $sourceTitle . " === " . $source . "\n";
            }
        }
    } catch (\Exception $e) {

    }

    sleep(3);
}
























