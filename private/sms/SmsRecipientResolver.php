<?php
/**
 * SMS Recipient Resolver
 * کلاس کمکی برای پردازش و نرمال‌سازی شماره‌های موبایل
 *
 * این کلاس یک Pure Utility است و هیچ وابستگی به دیتابیس ندارد
 * تمام عملیات آن Stateless و قابل استفاده در هر جایی است
 *
 * @version 3.6.0
 * @author ReadyCRM Team
 */

class SmsRecipientResolver
{
    /**
     * Supported formats for Iranian mobile numbers
     */
    const FORMATS = [
        '09xxxxxxxx',      // Standard Iranian format
        '989xxxxxxxxx',    // International with +98
        '00989xxxxxxxxx',  // International with 0098
        '9xxxxxxxxx'       // Without zero prefix
    ];

    /**
     * Allowed Iranian mobile prefixes
     */
    const VALID_PREFIXES = [
        '0910', '0911', '0912', '0913', '0914', '0915', '0916', '0917', '0918', '0919', // Hamrah-e Aval
        '0901', '0902', '0903', '0904', '0905',                                          // Irancell
        '0930', '0933', '0935', '0936', '0937', '0938', '0939',                         // Irancell
        '0920', '0921',                                                                   // RighTel
        '0932'                                                                            // TeleKish
    ];

    /**
     * نرمال‌سازی یک شماره موبایل به فرمت استاندارد ایرانی (09xxxxxxxxx)
     *
     * @param string $phone شماره ورودی
     * @return string|null شماره نرمال‌شده یا null در صورت نامعتبر بودن
     */
    public static function normalize($phone)
    {
        if (empty($phone)) {
            return null;
        }

        // حذف تمام کاراکترهای غیرعددی (فاصله، خط تیره، پرانتز، و...)
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // حذف پیش‌شماره‌های بین‌المللی
        // +98 → 98
        // 0098 → 98
        $phone = ltrim($phone, '0');
        $phone = preg_replace('/^98/', '', $phone);

        // حالا باید یک عدد 10 رقمی داشته باشیم (9xxxxxxxxx)
        if (strlen($phone) === 10 && $phone[0] === '9') {
            $phone = '0' . $phone; // تبدیل به 09xxxxxxxxx
        }

        // اعتبارسنجی نهایی
        if (!self::isValid($phone)) {
            return null;
        }

        return $phone;
    }

    /**
     * بررسی معتبر بودن شماره موبایل
     *
     * @param string $phone شماره نرمال‌شده
     * @return bool
     */
    public static function isValid($phone)
    {
        // باید دقیقاً 11 رقم باشد
        if (!preg_match('/^09[0-9]{9}$/', $phone)) {
            return false;
        }

        // بررسی پیش‌شماره
        $prefix = substr($phone, 0, 4);

        return in_array($prefix, self::VALID_PREFIXES, true);
    }

    /**
     * نرمال‌سازی یک آرایه از شماره‌ها
     *
     * @param array $phones آرایه شماره‌ها
     * @return array آرایه شماره‌های نرمال‌شده (بدون null)
     */
    public static function normalizeArray(array $phones)
    {
        $normalized = [];

        foreach ($phones as $phone) {
            $result = self::normalize($phone);
            if ($result !== null) {
                $normalized[] = $result;
            }
        }

        return $normalized;
    }

    /**
     * پردازش متن حاوی شماره‌های موبایل (جدا شده با کاما، سطر جدید، فاصله)
     *
     * @param string $text متن ورودی
     * @return array آرایه شماره‌های نرمال‌شده
     */
    public static function parseText($text)
    {
        if (empty($text)) {
            return [];
        }

        // جداسازی بر اساس کاما، سطر جدید، فاصله
        $phones = preg_split('/[,\n\r\s]+/', trim($text));

        return self::normalizeArray($phones);
    }

    /**
     * حذف شماره‌های تکراری
     *
     * @param array $phones آرایه شماره‌ها
     * @return array آرایه منحصر به فرد
     */
    public static function removeDuplicates(array $phones)
    {
        return array_values(array_unique($phones));
    }

    /**
     * فیلتر کردن شماره‌های نامعتبر از آرایه
     *
     * @param array $phones آرایه شماره‌ها
     * @return array آرایه شماره‌های معتبر
     */
    public static function filterInvalid(array $phones)
    {
        return array_values(array_filter($phones, [self::class, 'isValid']));
    }

    /**
     * Batch نرمال‌سازی: پردازش کامل یک لیست شامل نرمال‌سازی، اعتبارسنجی و حذف تکراری
     *
     * @param array|string $input آرایه شماره‌ها یا متن
     * @return array آرایه نهایی آماده ارسال
     */
    public static function process($input)
    {
        // اگر ورودی متن است، ابتدا پارس کن
        if (is_string($input)) {
            $phones = self::parseText($input);
        } elseif (is_array($input)) {
            $phones = self::normalizeArray($input);
        } else {
            return [];
        }

        // فیلتر نامعتبرها
        $phones = self::filterInvalid($phones);

        // حذف تکراری
        $phones = self::removeDuplicates($phones);

        return $phones;
    }

    /**
     * شناسایی اپراتور موبایل
     *
     * @param string $phone شماره نرمال‌شده
     * @return string نام اپراتور یا 'Unknown'
     */
    public static function detectOperator($phone)
    {
        if (!self::isValid($phone)) {
            return 'Unknown';
        }

        $prefix = substr($phone, 0, 4);

        $operators = [
            '0910' => 'همراه اول',
            '0911' => 'همراه اول',
            '0912' => 'همراه اول',
            '0913' => 'همراه اول',
            '0914' => 'همراه اول',
            '0915' => 'همراه اول',
            '0916' => 'همراه اول',
            '0917' => 'همراه اول',
            '0918' => 'همراه اول',
            '0919' => 'همراه اول',
            '0901' => 'ایرانسل',
            '0902' => 'ایرانسل',
            '0903' => 'ایرانسل',
            '0904' => 'ایرانسل',
            '0905' => 'ایرانسل',
            '0930' => 'ایرانسل',
            '0933' => 'ایرانسل',
            '0935' => 'ایرانسل',
            '0936' => 'ایرانسل',
            '0937' => 'ایرانسل',
            '0938' => 'ایرانسل',
            '0939' => 'ایرانسل',
            '0920' => 'رایتل',
            '0921' => 'رایتل',
            '0932' => 'تله کیش'
        ];

        return $operators[$prefix] ?? 'Unknown';
    }

    /**
     * گروه‌بندی شماره‌ها بر اساس اپراتور
     *
     * @param array $phones آرایه شماره‌های نرمال‌شده
     * @return array آرایه‌ای از گروه‌ها ['همراه اول' => [...], 'ایرانسل' => [...]]
     */
    public static function groupByOperator(array $phones)
    {
        $groups = [];

        foreach ($phones as $phone) {
            $operator = self::detectOperator($phone);

            if (!isset($groups[$operator])) {
                $groups[$operator] = [];
            }

            $groups[$operator][] = $phone;
        }

        return $groups;
    }

    /**
     * فرمت کردن شماره برای نمایش (به فرمت خوانا: 0912-345-6789)
     *
     * @param string $phone شماره نرمال‌شده
     * @return string شماره فرمت‌شده
     */
    public static function format($phone)
    {
        if (!self::isValid($phone)) {
            return $phone;
        }

        // 09123456789 → 0912-345-6789
        return substr($phone, 0, 4) . '-' . substr($phone, 4, 3) . '-' . substr($phone, 7);
    }

    /**
     * تبدیل شماره به فرمت بین‌المللی (+98)
     *
     * @param string $phone شماره نرمال‌شده
     * @return string شماره بین‌المللی یا خالی در صورت نامعتبر بودن
     */
    public static function toInternational($phone)
    {
        if (!self::isValid($phone)) {
            return '';
        }

        // 09123456789 → +989123456789
        return '+98' . substr($phone, 1);
    }

    /**
     * اعتبارسنجی Batch با گزارش خطاها
     *
     * @param array|string $input ورودی
     * @return array ['valid' => [...], 'invalid' => [...]]
     */
    public static function validateBatch($input)
    {
        if (is_string($input)) {
            $phones = preg_split('/[,\n\r\s]+/', trim($input));
        } elseif (is_array($input)) {
            $phones = $input;
        } else {
            return ['valid' => [], 'invalid' => []];
        }

        $valid = [];
        $invalid = [];

        foreach ($phones as $phone) {
            $normalized = self::normalize($phone);

            if ($normalized !== null) {
                $valid[] = $normalized;
            } else {
                $invalid[] = [
                    'original' => $phone,
                    'reason' => 'فرمت نامعتبر'
                ];
            }
        }

        return [
            'valid' => array_values(array_unique($valid)),
            'invalid' => $invalid
        ];
    }

    /**
     * آمار اعتبارسنجی
     *
     * @param array $validation_result نتیجه از validateBatch
     * @return array آمار کامل
     */
    public static function getValidationStats($validation_result)
    {
        $total_valid = count($validation_result['valid']);
        $total_invalid = count($validation_result['invalid']);
        $total = $total_valid + $total_invalid;

        return [
            'total' => $total,
            'valid' => $total_valid,
            'invalid' => $total_invalid,
            'success_rate' => $total > 0 ? round(($total_valid / $total) * 100, 2) : 0
        ];
    }

    /**
     * پیشنهاد اصلاح برای شماره‌های نامعتبر
     *
     * @param string $phone شماره نامعتبر
     * @return array پیشنهادها
     */
    public static function suggestCorrection($phone)
    {
        if (self::isValid($phone)) {
            return ['message' => 'شماره معتبر است', 'suggestions' => []];
        }

        $suggestions = [];

        // حذف کاراکترهای غیرعددی
        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        // کوتاه‌تر از حد
        if (strlen($cleaned) < 10) {
            $suggestions[] = 'شماره باید حداقل 10 رقم باشد';
        }

        // بلندتر از حد
        if (strlen($cleaned) > 13) {
            $suggestions[] = 'شماره بیش از حد طولانی است';
        }

        // بررسی شروع با 9 بدون 0
        if (strlen($cleaned) === 10 && $cleaned[0] === '9') {
            $suggested = '0' . $cleaned;
            if (self::isValid($suggested)) {
                $suggestions[] = 'احتمالاً منظورتان این بود: ' . $suggested;
            }
        }

        // بررسی شروع با 98
        if (preg_match('/^98[0-9]{10}$/', $cleaned)) {
            $suggested = '0' . substr($cleaned, 2);
            if (self::isValid($suggested)) {
                $suggestions[] = 'احتمالاً منظورتان این بود: ' . $suggested;
            }
        }

        return [
            'message' => 'شماره نامعتبر است',
            'suggestions' => $suggestions
        ];
    }

    /**
     * محاسبه تعداد پیام برای یک متن با توجه به طول و کدینگ
     *
     * @param string $message متن پیام
     * @return int تعداد پیام (هر پیام فارسی 70 کاراکتر)
     */
    public static function estimateMessageCount($message)
    {
        $length = mb_strlen($message, 'UTF-8');

        // پیام فارسی: 70 کاراکتر اول، 67 کاراکتر بعدی‌ها
        if ($length <= 70) {
            return 1;
        } else {
            return 1 + (int)ceil(($length - 70) / 67);
        }
    }

    /**
     * محاسبه هزینه تقریبی ارسال
     *
     * @param array $phones آرایه شماره‌ها
     * @param string $message متن پیام
     * @param float $cost_per_sms هزینه هر پیام (تومان)
     * @return array آمار هزینه
     */
    public static function estimateCost(array $phones, $message, $cost_per_sms = 500)
    {
        $recipient_count = count($phones);
        $message_count = self::estimateMessageCount($message);

        $total_messages = $recipient_count * $message_count;
        $total_cost = $total_messages * $cost_per_sms;

        return [
            'recipient_count' => $recipient_count,
            'message_parts' => $message_count,
            'total_messages' => $total_messages,
            'cost_per_sms' => $cost_per_sms,
            'total_cost' => $total_cost,
            'formatted_cost' => number_format($total_cost) . ' تومان'
        ];
    }

    /**
     * تقسیم لیست شماره‌ها به Batch‌های کوچک‌تر
     *
     * @param array $phones آرایه شماره‌ها
     * @param int $batch_size اندازه هر Batch
     * @return array آرایه‌ای از Batch‌ها
     */
    public static function createBatches(array $phones, $batch_size = 100)
    {
        if ($batch_size <= 0) {
            $batch_size = 100;
        }

        return array_chunk($phones, $batch_size);
    }

    /**
     * Export شماره‌ها به فرمت CSV
     *
     * @param array $phones آرایه شماره‌ها
     * @param string $filename نام فایل (اختیاری)
     * @return string محتوای CSV
     */
    public static function exportToCSV(array $phones, $filename = null)
    {
        $csv = "شماره موبایل,اپراتور\n";

        foreach ($phones as $phone) {
            $operator = self::detectOperator($phone);
            $csv .= "\"{$phone}\",\"{$operator}\"\n";
        }

        return $csv;
    }

    /**
     * Import شماره‌ها از فایل CSV
     *
     * @param string $csv_content محتوای CSV
     * @return array آرایه شماره‌های نرمال‌شده
     */
    public static function importFromCSV($csv_content)
    {
        $lines = explode("\n", $csv_content);
        $phones = [];

        foreach ($lines as $line) {
            // Skip header or empty lines
            if (empty(trim($line)) || stripos($line, 'شماره') !== false) {
                continue;
            }

            // Extract phone number (first column)
            $parts = str_getcsv($line);
            if (!empty($parts[0])) {
                $normalized = self::normalize($parts[0]);
                if ($normalized) {
                    $phones[] = $normalized;
                }
            }
        }

        return self::removeDuplicates($phones);
    }
}
