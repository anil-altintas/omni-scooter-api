<?php
declare(strict_types=1);
require __DIR__.'/../src/config.php';
require __DIR__.'/../src/ScooterService.php';

use App\ScooterService;

header('Content-Type: application/json; charset=utf-8');
$svc  = new ScooterService();
$path = explode('/', trim($_SERVER['REQUEST_URI'],'/'));

try {
    switch($path[0]) {
        case 'status':
            if (empty($path[1])) throw new Exception('IMEI yok');
            $data = $svc->getStatus($path[1]);
            echo json_encode(['success'=>true,'data'=>$data]);
            break;
        case 'lock': case 'unlock':
        case 'reserve': case 'cancel': case 'alarm':
            if ($_SERVER['REQUEST_METHOD']!=='POST') throw new Exception('POST bekleniyor');
            $body = json_decode(file_get_contents('php://input'),true);
            if (empty($body['imei'])) throw new Exception('IMEI eksik');
            $svc->setInstruction($body['imei'],$path[0]);
            echo json_encode(['success'=>true,'msg'=>"$path[0] komutu kaydedildi"]);
            break;
        default:
            throw new Exception('Bilinmeyen endpoint');
    }
} catch(\Throwable $e) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
