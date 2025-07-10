<?php
use React\EventLoop\Factory;
use React\Socket\Server as SocketServer;
use React\Socket\ConnectionInterface;
use App\ScooterService;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/config.php';
require __DIR__ . '/util.php';
require __DIR__ . '/ScooterService.php';

$loop   = Factory::create();
$server = new SocketServer('0.0.0.0:8082', $loop);
$svc    = new ScooterService();

$server->on('connection', function(ConnectionInterface $conn) use ($svc, $loop) {
    $imei = null;

    // Gelen veri
    $conn->on('data', function($data) use ($conn, &$imei, $svc) {
        $msg = trim($data);
        error_log(">> {$msg}");
        // Basit split, '#' karakterini at
        $parts = explode(',', rtrim($msg,'#'));
        if (count($parts) < 4 || $parts[0] !== '*SCOR' || $parts[1] !== 'OM') {
            return;
        }
        $imei = $parts[2];
        $inst = $parts[3];

        // 1) Q0 = register
        if ($inst === 'Q0') {
            // Eğer tabloya yoksa ekle, varsa ignore
            $pdo = getPDO();
            $pdo->prepare(
              "INSERT IGNORE INTO `lock`(lockid) VALUES(:imei)"
            )->execute(['imei'=>$imei]);
            // Default ayar komutlarını scooter’a yolla
            $cmd = "*SCOS,OM,$imei,S5,2,2,10,10#";
            $conn->write(makeCmd($cmd));
            $cmd = "*SCOS,OM,$imei,D1,10#";
            $conn->write(makeCmd($cmd));
        }

        // 2) H0 = heartbeat
        if ($inst === 'H0') {
            // tipik: parts[4]=status, [5]=volt,[6]=net,[7]=power,[8]=charge
            [$status,$volt,$net,$power,$charge] = array_slice($parts,4,5);
            $pdo = getPDO();
            $pdo->prepare("
                UPDATE `lock` SET 
                  locked        = :stat,
                  drivervolt    = :dv,
                  networksignal = :ns,
                  power         = :p
                WHERE lockid = :imei
            ")->execute([
                'stat'=> ($status==='1'?1:0),
                'dv'  => convertVoltage($volt),
                'ns'  => $net,
                'p'   => intval($power),
                'imei'=> $imei
            ]);
        }

        // Diğer inst’leri de benzer şekilde ele alabilirsin (R0, W0, L0, L1…)
    });

    // 3) Her saniye instruction sütununa bak, varsa scooter’a yolla
    $loop->addPeriodicTimer(1.0, function() use ($conn, &$imei, $svc) {
        if (!$imei) return;
        try {
            $row = $svc->getStatus($imei);
        } catch (\Exception $e) {
            return;
        }
        if (!empty($row['instruction'])) {
            // Örn: lock => R0,1,USERID,0,TS#
            $instr = $row['instruction'];
            $ts    = time();
            $cmd   = match($instr) {
              'lock'   => "*SCOS,OM,$imei,R0,1,0,0,$ts#",
              'unlock' => "*SCOS,OM,$imei,R0,0,0,0,$ts#",
              'reserve'=> "*SCOS,OM,$imei,V0,1#",
              'cancel' => "*SCOS,OM,$imei,S1,11#",
              'alarm'  => "*SCOS,OM,$imei,V0,2#",
              default  => ''
            };
            if ($cmd) {
                $conn->write(makeCmd($cmd));
                // instruction sıfırla
                $svc->setInstruction($imei, '');
            }
        }
    });
});

echo "PHP TCP server 0.0.0.0:8082 dinleniyor...\n";
$loop->run();
