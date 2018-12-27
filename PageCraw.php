<?php
/**
 * Created by PhpStorm.
 * User: llf
 * Date: 2018/12/25
 * Time: 16:13
 */
if (isset($argv[1]) && $argv[1] == 'rename') reExtName();
require './vendor/autoload.php';
require './vendor/lib/Spider.php';
include 'E:/www/global';


use Symfony\Component\DomCrawler\Crawler;

function reExtName() {
    $dir = './crawFiles/dict';
    $od = dir($dir);
    while ($file = readdir($od->handle)) {
        if ($file == '.' || $file == '..') continue;
        $cmd = 'rename '. $file . ' '. $file . '.html';
        @exec($cmd);
    }
    die;
}

function logWrite($msg) {
    $time = date('Y-m-d H:i:s');
    echo $time . " $msg \n";
}

$baseUrl = 'https://hh.flexui.win/thread0806.php?fid=22';
$pagePath = './crawFiles/';
$tmplPath = './vendor/tmpl/';
$runTime = time();
$tplFile = file_get_contents($tmplPath . 'view.tpl');
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

for ($i = 1, $cnt = 2; $i <= $cnt; $i ++) {
    $currDictFile = $pagePath. 'dict/'. $i. '.html';
    $currDictFileCreateTime = filectime($currDictFile);
    if (is_file($currDictFile) && $runTime-$currDictFileCreateTime < 86400) { // continue dict files which createTime less then one day
        #logWrite("the ". $i . " page has parsed");
        #continue;
    }
    $url = $baseUrl . '&page='. $i;
    $htmlPath = '';
    if (!is_file($pagePath . $i . '/F') || $runTime-filectime($pagePath . $i . '/F') > 86400) { // reCraw sourceFiles which catchTime more then one day
        logWrite('begin catching the '. $i . ' page');
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
        logWrite('begin parse the '. $i . ' page');
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
        $totalItem = count($data);
        logWrite('parse the '. $i . ' page success count '. $totalItem. ' item');
        // then craw next page
        if (!empty($data)) {
            $htmlPath = $htmlStr = '';
            $crawler = null;
            $dictStr = '';
            foreach ($data as $key => $row) {
                $itemIndex = $key + 1;
                #$itemIndex = 84; // debug
                if (!is_file($pagePath . $i . '/S'. $itemIndex) || $runTime-filectime($pagePath . $i . '/S'. $itemIndex) > 86400) {
                    $html = $spider->post($row['href']);
                    preg_match("/<body>(.*?)<\/body>/s", $html, $m);
                    file_put_contents($pagePath . $i .'/S'. $itemIndex, $m[0]);
                }
                $htmlPath = $pagePath . $i . '/S'. $itemIndex;
                $htmlStr = file_get_contents($htmlPath);
                $data = [];
                $crawler = new Crawler($htmlStr);
                $sourceTitle = $crawler->filterXPath('//h4')->text();
                $sourceLink = 'src=http://www.baidu.com';
                $sourceLink = $crawler->filterXPath('//div[contains(@class,"tpc_content")]/a[2]')->attr('onclick');
                $sourceLink = explode('src=', $sourceLink);
                $sourceLink = trim($sourceLink[1], '\'');
                $dictStr .= '<tr>';
                $dictStr .= '<td class="td-id"><span class="num">'. $itemIndex. '</span></td>';
                $dictStr .= '<td class="td-title"><a href="'. $sourceLink. '" target="_bank">'.$sourceTitle.'</a></td>';
                $dictStr .= '</tr>';
            }
            $dictStr = preg_replace('/\<\{CONTENT\}\>/s', $dictStr, $tplFile);
            file_put_contents($pagePath. 'dict/'. $i. '.html', $dictStr, FILE_APPEND);
            logWrite('save the '. $i . ' dict success');
        }
    } catch (\Exception $e) {
        logWrite('catch Exception '. $e->getMessage());
    }
    logWrite("sleep three seconds please wait...");
    sleep(3);
}
























