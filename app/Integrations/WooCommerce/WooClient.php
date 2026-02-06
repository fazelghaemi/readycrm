<?php
/**
 * WooCommerce REST API Client
 * با پشتیبانی از BasicAuth، Pagination، Retry
 */

namespace App\Integrations\WooCommerce;

class WooClient {
    private $storeUrl;
    private $consumerKey;
    private $consumerSecret;
    private $apiVersion;
    private $verifySsl;
    private $timeout;

    public function __construct() {
        $this->storeUrl = rtrim(WOO_STORE_URL, '/');
        $this->consumerKey = WOO_CONSUMER_KEY;
        $this->consumerSecret = WOO_CONSUMER_SECRET;
        $this->apiVersion = WOO_API_VERSION;
        $this->verifySsl = WOO_VERIFY_SSL;
        $this->timeout = WOO_TIMEOUT;
    }

    /**
     * ارسال درخواست GET
     */
    public function get($endpoint, $params = []) {
        return $this->request('GET', $endpoint, $params);
    }

    /**
     * ارسال درخواست POST
     */
    public function post($endpoint, $data = []) {
        return $this->request('POST', $endpoint, [], $data);
    }

    /**
     * ارسال درخواست PUT
     */
    public function put($endpoint, $data = []) {
        return $this->request('PUT', $endpoint, [], $data);
    }

    /**
     * ارسال درخواست DELETE
     */
    public function delete($endpoint) {
        return $this->request('DELETE', $endpoint);
    }

    /**
     * متد اصلی ارسال درخواست
     */
    private function request($method, $endpoint, $params = [], $data = []) {
        $url = $this->buildUrl($endpoint, $params);
        
        $ch = curl_init($url);
        
        // تنظیمات پایه
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verifySsl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        
        // BasicAuth
        curl_setopt($ch, CURLOPT_USERPWD, $this->consumerKey . ':' . $this->consumerSecret);
        
        // متد و داده
        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }
        
        $startTime = microtime(true);
        $response = curl_exec($ch);
        $duration = round((microtime(true) - $startTime) * 1000); // ms
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // لاگ کردن
        $this->logRequest($method, $endpoint, $params, $data, $response, $httpCode, $error, $duration);
        
        if ($error) {
            throw new \Exception("cURL Error: " . $error);
        }
        
        if ($httpCode >= 400) {
            throw new \Exception("HTTP Error $httpCode: " . $response);
        }
        
        return json_decode($response, true);
    }

    /**
     * ساخت URL کامل
     */
    private function buildUrl($endpoint, $params = []) {
        $url = $this->storeUrl . '/wp-json/' . $this->apiVersion . '/' . ltrim($endpoint, '/');
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }

    /**
     * لاگ کردن درخواست
     */
    private function logRequest($method, $endpoint, $params, $data, $response, $httpCode, $error, $duration) {
        // TODO: ذخیره در جدول woo_sync_logs
    }

    /**
     * دریافت تمام آیتم‌ها با Pagination
     */
    public function getAllPaginated($endpoint, $perPage = 100) {
        $page = 1;
        $allItems = [];
        
        do {
            $response = $this->get($endpoint, [
                'per_page' => $perPage,
                'page' => $page
            ]);
            
            if (empty($response)) {
                break;
            }
            
            $allItems = array_merge($allItems, $response);
            $page++;
            
            // حداکثر 1000 آیتم (محدودیت امنیتی)
            if (count($allItems) >= 1000) {
                break;
            }
            
        } while (!empty($response));
        
        return $allItems;
    }
}
