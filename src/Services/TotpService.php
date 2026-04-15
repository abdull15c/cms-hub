<?php
namespace Src\Services;

class TotpService {
    public function generateSecret() {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 16; $i++) $secret .= $chars[random_int(0, 31)];
        return $secret;
    }

    public function getCode($secret, $timeSlice = null) {
        if ($timeSlice === null) $timeSlice = floor(time() / 30);
        $secretKey = $this->base32_decode($secret);
        $time = chr(0).chr(0).chr(0).chr(0).pack('N*', $timeSlice);
        $hmac = hash_hmac('sha1', $time, $secretKey, true);
        $offset = ord(substr($hmac, -1)) & 0x0F;
        $hashPart = substr($hmac, $offset, 4);
        $value = unpack('N', $hashPart);
        $value = $value[1] & 0x7FFFFFFF;
        return str_pad($value % 1000000, 6, '0', STR_PAD_LEFT);
    }

    public function verify($secret, $code) {
        if (empty($secret) || empty($code)) return false;
        $timeSlice = floor(time() / 30);
        for ($i = -1; $i <= 1; $i++) {
            if ($this->getCode($secret, $timeSlice + $i) === $code) return true;
        }
        return false;
    }

    public function getQrUrl($name, $secret, $issuer = 'CMS-HUB') {
        return "otpauth://totp/$issuer:$name?secret=$secret&issuer=$issuer";
    }

    private function base32_decode($base32) {
        $base32 = strtoupper($base32);
        $l = strlen($base32);
        $n = 0; $j = 0; $binary = "";
        $map = array('A'=>0,'B'=>1,'C'=>2,'D'=>3,'E'=>4,'F'=>5,'G'=>6,'H'=>7,'I'=>8,'J'=>9,'K'=>10,'L'=>11,'M'=>12,'N'=>13,'O'=>14,'P'=>15,'Q'=>16,'R'=>17,'S'=>18,'T'=>19,'U'=>20,'V'=>21,'W'=>22,'X'=>23,'Y'=>24,'Z'=>25,'2'=>26,'3'=>27,'4'=>28,'5'=>29,'6'=>30,'7'=>31);
        for ($i = 0; $i < $l; $i++) {
            $x = $map[$base32[$i]];
            $n = $n << 5; $n = $n + $x; $j += 5;
            if ($j >= 8) { $j -= 8; $binary .= chr(($n & (0xFF << $j)) >> $j); }
        }
        return $binary;
    }
}