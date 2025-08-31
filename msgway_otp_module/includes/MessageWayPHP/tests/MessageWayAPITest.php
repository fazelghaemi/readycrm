<?php
use MessageWay\Api\MessageWayAPI;
use PHPUnit\Framework\TestCase;

class MessageWayAPITest extends TestCase
{

	private static MessageWayAPI $messageWay;

	public static function setUpBeforeClass(): void
	{
		self::$messageWay = new MessageWayAPI();
		self::$messageWay->setApiKey(getenv("API_KEY"));
		self::$messageWay->setMobile(getenv("MOBILE"));
	}

	/**
	 * @throws Exception
	 */
	public function testSendViaSMS(): string
	{
		$referenceID = self::$messageWay->setTemplateID(3)->send('sms');
		$this->assertIsString($referenceID['referenceID']);
		return $referenceID['referenceID'];
	}

	/**
	 * @throws Exception
	 */
	public function testSendViaMessenger()
	{
		$referenceID = self::$messageWay->setTemplateID(3)->send('messenger',2);
		$this->assertIsString($referenceID['referenceID']);
	}

	/**
	 * @throws Exception
	 */
	public function testSendViaIVR()
	{
		$referenceID = self::$messageWay->setTemplateID(2)->send('ivr');
		$this->assertIsString($referenceID['referenceID']);
	}

	/**
	 * @depends testSendViaSMS
	 * @throws Exception
	 */
	public function testCheckStatus(string $referenceID)
	{
		$status = self::$messageWay->getStatus($referenceID);
		$this->assertContains($status['OTPStatus'], ['pending', 'sent', 'operatorDelivered']);
		$this->assertEquals(false,$status['OTPVerified']);
		$this->assertContains($status['OTPMethod'], ['sms', 'ivr', 'email', 'messenger']);
	}

	/**
	 * @throws Exception
	 */
	public function testVerify()
	{
		try {
			self::$messageWay->setMobile("+989356" . rand(100000, 999999))->setOTP(rand(1000, 9999))->verifyOTP();
			$this->fail('verify OTP was not thrown');
		} catch (Exception $e) {
			$this->assertGreaterThan(0, $e->getCode());
		}
	}

	/**
	 * @throws Exception
	 */
	public function testSendViaMessengerWithParams(): string
	{
		$otpCode = rand(1000, 9999);
		$referenceID = self::$messageWay
			->setTemplateID(11)
			->setParam1("value_1")
			->setParam2("value_2")
			->setParam3("value_3")
			->setParam4("value_4")
			->setParam5("value_5")
			->setCode($otpCode)
			->setLength(strlen($otpCode))
			->setProvider(2)
			->send('messenger');
		$this->assertIsString($referenceID['referenceID']);
		return $otpCode;
	}

}
