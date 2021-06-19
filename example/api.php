<?php

require_once dirname(__DIR__) . '/GsmArena/GsmArenaApi.php';
use FulgerX2007\GsmArena\GsmArenaApi;


$gsm = new GsmArenaApi();

if (!empty($_GET['query'])) {
    $data = $gsm->search($_GET['query']);
} elseif (!empty($_GET['slug'])) {
    $data = $gsm->getDeviceDetail($_GET['slug']);
} elseif (!empty($_GET['brands'])) {
    $data = $gsm->getBrands();
} else {
    $data = ['status' => 'error'];
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo json_encode($data, JSON_PRETTY_PRINT, 512);
