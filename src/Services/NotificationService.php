<?php
namespace Src\Services;
use Config\Database;

class NotificationService {
    public static function send($userId, $message, $type = 'info', $link = null) {
        Database::connect()->prepare("INSERT INTO notifications (user_id, message, type, link) VALUES (?,?,?,?)")
            ->execute([$userId, $message, $type, $link]);
    }
}