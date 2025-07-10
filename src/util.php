<?php
/**
 * Verilen komut string'ini scooter'ın anlayacağı byte dizisine çevirir.
 * Örn: "*SCOS,OM,imei,R0,1,KEY,TS#"
 */
function makeCmd(string $instr): string {
    // eğer checksum, CRC gerekiyorsa burada hesaplayıp ekleyebilirsin.
    return $instr . "\n";
}

/**
 * Gelen voltage string'ini float’a çevirir.
 */
function convertVoltage(string $v): float {
    return floatval($v) / 100.0;
}

// Diğer util fonksiyonlarını buraya ekleyebilirsin.
