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
    $queryParams = [
        'version' => $this->request->input('version', '1.x'),
        'vendorName' => $this->request->input('vendorName', 'maliboot'),
        'packageName' => $this->request->input('packageName', 'maliboot-skeleton'),
        'phpVersion' => $this->request->input('phpVersion', '8.0'),
        'desc' => $this->request->input('desc', 'maliboot脚手架'),
        'packagistUrl' => $this->request->input('packagistUrl', 'https://mirrors.aliyun.com/composer/'),
        'requireDev' => $this->request->input('requireDev', ''),
        'require' => $this->request->input('require', ''),
    ];
    $tplDir = sprintf("%s/storage/maliboot-template/%s", BASE_PATH, $queryParams['version']);
    $queryParamsBuildStr = md5(http_build_query($queryParams));var_dump($queryParamsBuildStr);
    $cacheComposerFile = sprintf("%s/%s.%s", $tplDir, $queryParamsBuildStr, 'json');
    if (file_exists($cacheComposerFile)) {
        $tplComposerDataTxt = file_get_contents($cacheComposerFile);
    } else {
        $tplComposerData = json_decode(file_get_contents($tplDir . '/composer.json'), true);
        $tplComposerData['name'] = sprintf("%s/%s", $queryParams['vendorName'], $queryParams['packageName']);
        $tplComposerData['description'] = $queryParams['desc'];
        $tplComposerData['require']['php'] = ">=" . $queryParams['phpVersion'];
        $tplComposerData['repositories']['packagist']['url'] = $queryParams['packagistUrl'];
        $queryParams['require'] && $tplComposerData['require'] = array_merge($tplComposerData['require'], array_map(function ($item) {
            return explode(":", $item);
        }, explode(',', $queryParams['require'])));
        $queryParams['requireDev'] && $tplComposerData['require-dev'] = array_merge($tplComposerData['require-dev'], array_map(function ($item) {
            return explode(":", $item);
        }, explode(',', $queryParams['requireDev'])));

        $tplComposerDataTxt = json_encode($tplComposerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    $zip = new ZipArchive();
    $zip->open($tplDir . '/demo.zip');
    $zip->addFromString("composer.json", $tplComposerDataTxt);
    $zip->close();
    return $this->response->download($tplDir . '/demo.zip');
});

$app->run();