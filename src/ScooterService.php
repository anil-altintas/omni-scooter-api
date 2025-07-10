<?php
namespace App;

class ScooterService {
    private \PDO $db;

    /** Bağlantıyı hazırlar */
    public function __construct() {
        $this->db = \getPDO();
    }

    /**
     * IMEI’li scooter’ın o anki durumunu döner.
     * @throws \Exception
     */
    public function getStatus(string $imei): array {
        $sql = "SELECT * FROM `lock` WHERE lockid = :imei";
        $stmt= $this->db->prepare($sql);
        $stmt->execute(['imei'=>$imei]);
        $row = $stmt->fetch();
        if (!$row) throw new \Exception("Scooter bulunamadı: $imei");
        $row['locked'] = (bool)$row['locked'];
        return $row;
    }

    /**
     * Komutu instruction sütununa yazar.
     * Geçerli komutlar: lock, unlock, reserve, cancel, alarm
     */
    public function setInstruction(string $imei, string $instr): void {
        $allowed = ['lock','unlock','reserve','cancel','alarm'];
        if (!in_array($instr, $allowed, true)) {
            throw new \InvalidArgumentException("Geçersiz komut: $instr");
        }
        $sql = "UPDATE `lock` SET instruction = :instr WHERE lockid = :imei";
        $stmt= $this->db->prepare($sql);
        $stmt->execute(['instr'=>$instr,'imei'=>$imei]);
    }
}
