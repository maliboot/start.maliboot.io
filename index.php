<?php

use Hyperf\Nano\ContainerProxy;
use Hyperf\Nano\Factory\AppFactory;

require_once __DIR__ . '/vendor/autoload.php';
$app = AppFactory::create();

$app->get('/', function () {

    $user = $this->request->input('user', 'maliboot');
    $method = $this->request->getMethod();

    return [
        'message' => "hello {$user}",
        'method' => $method,
    ];
});

$app->get('/starter.zip', function () {
    /** @var ContainerProxy $this */
    $qry = [
        'version' => $this->request->input('version', '1.x'),
        'vendorName' => $this->request->input('vendorName', 'maliboot'),
        'packageName' => $this->request->input('packageName', 'maliboot-skeleton'),
        'phpVersion' => $this->request->input('phpVersion', '8.0'),
        'desc' => urldecode($this->request->input('desc', 'maliboot脚手架')),
        'packagistUrl' => urldecode($this->request->input('packagistUrl', 'https://mirrors.aliyun.com/composer/')),
        'requireDev' => urldecode($this->request->input('requireDev', '')),
        'require' => urldecode($this->request->input('require', '')),
    ];var_dump($qry);
    $tplDir = sprintf("%s/storage/maliboot-template/%s", BASE_PATH, $qry['version']);
    $qryBuildStr = md5(http_build_query($qry));
    $cacheComposerFile = sprintf("%s/%s.%s", $tplDir, $qryBuildStr, 'json');

    // 配置composer.json
    if (file_exists($cacheComposerFile)) {
        $composerTxt = file_get_contents($cacheComposerFile);
    } else {
        $composer = json_decode(file_get_contents($tplDir . '/composer.json'), true);
        $composer['name'] = sprintf("%s/%s", $qry['vendorName'], $qry['packageName']);
        $composer['description'] = $qry['desc'];
        $composer['require']['php'] = ">=" . $qry['phpVersion'];
        $composer['repositories']['packagist']['url'] = $qry['packagistUrl'];
        $qry['require'] && $composer['require'] = array_merge(
            $composer['require'],
            ...array_reduce(explode(',', $qry['require']), function ($carry, $item) {
                $offset = strpos($item, ':');
                if (!empty($item) && $offset !== false) {
                    $carry[][substr($item, 0, $offset++)] = substr($item, $offset);
                }
                return $carry;
            }, [])
        );
        $qry['requireDev'] && $composer['require-dev'] = array_merge(
            $composer['require-dev'],
            ...array_reduce(explode(',', $qry['requireDev']), function ($carry, $item) {
                $offset = strpos($item, ':');
                if (!empty($item) && $offset !== false) {
                    $carry[][substr($item, 0, $offset++)] = substr($item, $offset);
                }
                return $carry;
            }, [])
        );
        $composerTxt = json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    // 打压缩包输出到浏览器
    $zip = new ZipArchive();
    $zip->open($tplDir . '/demo.zip');
    $zip->addFromString("composer.json", $composerTxt);
    $zip->close();
    return $this->response->download($tplDir . '/demo.zip');
});

$app->run();