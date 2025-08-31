<?php
// msgway_otp_module/includes/msgway_client.php
declare(strict_types=1);

namespace RS\MSGWAY;

use Exception;

class Client {
    /** @var string */
    private $apiKey;

    /** @var object|null */
    private $sdk = null;

    public function __construct(string $apiKey) {
        $this->apiKey = $apiKey;

        // Composer
        if (class_exists('\MessageWay\Api\MessageWayAPI')) {
            $this->sdk = new \MessageWay\Api\MessageWayAPI($apiKey);
            return;
        }

        // Manual include fallback
        $manual = __DIR__ . '/MessageWayPHP/src/MessageWayAPI.php';
        if (file_exists($manual)) {
            require_once $manual;
            if (class_exists('\MessageWay\Api\MessageWayAPI')) {
                $this->sdk = new \MessageWay\Api\MessageWayAPI($apiKey);
                return;
            }
        }

        throw new Exception('MSGway PHP SDK یافت نشد. composer require messageway/messagewayphp');
    }

    /** @return array [referenceID, sender, ...] */
    public function sendOTP(string $mobile, int $templateID): array {
        return $this->sdk->sendViaSMS($mobile, $templateID);
    }

    /** @return array status info */
    public function verifyOTP(string $otp, string $mobile): array {
        return $this->sdk->verifyOTP($otp, $mobile);
    }

    public function getStatus(string $referenceID): array {
        return $this->sdk->getStatus($referenceID);
    }

    public function getBalance() {
        return $this->sdk->getBalance();
    }
}
