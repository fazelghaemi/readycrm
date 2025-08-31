
<?php
// msgway_otp_module/includes/msgway_client.php
// Wrapper around MSGway PHP SDK. Falls back to a clear error if SDK not found.
// SDK: https://github.com/MessageWay/MessageWayPHP

namespace RS\MSGWAY;
use Exception;

class Client {
    private $apiKey;
    private $sdk;

    public function __construct(string $apiKey) {
        $this->apiKey = $apiKey;
        // Try SDK via Composer
        if (class_exists('MessageWay\Api\MessageWayAPI')) {
            $this->sdk = new \MessageWay\Api\MessageWayAPI($apiKey);
            return;
        }
        // Try manual include (cloned repo in includes/MessageWayPHP)
        $manual = __DIR__ . '/MessageWayPHP/src/MessageWayAPI.php';
        if (file_exists($manual)) {
            require_once $manual;
            if (class_exists('MessageWay\Api\MessageWayAPI')) {
                $this->sdk = new \MessageWay\Api\MessageWayAPI($apiKey);
                return;
            }
        }
        throw new Exception('MSGway PHP SDK یافت نشد. لطفا با Composer نصب کنید: composer require messageway/messagewayphp . یا مخزن را کلون کرده و مسیر را در includes/MessageWayPHP قرار دهید.');
    }

    /** Send OTP over SMS. Returns [referenceID, sender] */
    public function sendOTP(string $mobile, int $templateID): array {
        // Using SDK public API (per official README)
        return $this->sdk->sendViaSMS($mobile, $templateID);
    }

    /** Verify OTP code */
    public function verifyOTP(string $otp, string $mobile): array {
        return $this->sdk->verifyOTP($otp, $mobile);
    }

    /** Optional: status lookup */
    public function getStatus(string $referenceID): array {
        return $this->sdk->getStatus($referenceID);
    }

    public function getBalance() {
        return $this->sdk->getBalance();
    }
}
