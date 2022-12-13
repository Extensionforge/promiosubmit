<?php

/**
 * Class NssApi
 * Version 1.6.4
 * Requires PHP >= 5.4.0
 */
class NssApi {

	/**
	 * @var string Holds the http url targeting the api endpoint
	 */
	private static $api_endpoint;

	const API_ENDPOINT_GEVESTOR = NSS_API_ENDPOINT_GEVESTOR;
	const API_ENDPOINT_FID = NSS_API_ENDPOINT_FID;
	const API_ENDPOINT_COMPUTERWISSEN = NSS_API_ENDPOINT_COMPUTERWISSEN;
	const API_ENDPOINT_B2B = NSS_API_ENDPOINT_B2B;
	const API_PASSWORD = API_PASSWORD;

	/**
	 * @var stdClass[] Used to cache calls to get_opt_in_email_information()
	 */
	private static $opt_in_email_information_cache = [];

	/**
	 * @param string $email_address
	 * @param string[] $newsletter_abbreviations
	 * @param string $opt_in_process_id
	 * @param string|null $affiliate
	 * @param bool $trader_fox
	 * @param bool $bullvestor
	 * @param string $coreg
	 * @param string $adref
	 * @param stdClass $additional_properties
	 * @param callable $callback
	 * @throws Exception
	 */
	public static function subscribe($email_address, $newsletter_abbreviations, $opt_in_process_id, $affiliate = null,
									 $trader_fox = false, $bullvestor = false, $coreg = '', $adref = null,
									 $additional_properties = null, $callback = null) {

		$customer_remote_address = null;
		if (isset($_SERVER['HTTP_X_REAL_IP']) && strlen(trim($_SERVER['HTTP_X_REAL_IP'])) > 0) {
			$customer_remote_address = $_SERVER['HTTP_X_REAL_IP'];
		} elseif (isset($_SERVER['REMOTE_ADDR']) && strlen(trim($_SERVER['REMOTE_ADDR'])) > 0) {
			$customer_remote_address = $_SERVER['REMOTE_ADDR'];
		}

		$calling_service = sprintf(
			'%s://%s%s',
			isset($_SERVER['HTTPS']) ? 'https' : 'http',
			isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '',
			isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''
		);

		$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;

		$visitor_id = isset($_COOKIE['ePPxlID']) ? $_COOKIE['ePPxlID'] : null;

		$session_id = isset($_COOKIE['ePxlID']) ? $_COOKIE['ePxlID'] : null;

		$response = self::remote_call(
			'Subscribe',
			[
				$email_address, $newsletter_abbreviations, $opt_in_process_id, $customer_remote_address,
				$calling_service, $affiliate, $visitor_id, $session_id, $trader_fox, $bullvestor, $coreg, $user_agent,
				$adref, $additional_properties
			]
		);

		if (!is_array($response)) {
			throw new Exception('invalid response format');
		}

		// relocate to soi page if provided
		if (isset($response[0])) {
			if ($callback !== null && is_callable($callback)) {
				$callback();
			}
			self::relocate($response[0]);
		}

	}

	/**
	 * @param string $key
	 * @return bool
	 * @throws Exception
	 */
	public static function is_coreg_campaign_active($key) {

		$response = self::remote_call('IsCoregCampaignActive', [trim($key)]);

		if (!is_bool($response)) {
			throw new Exception('invalid response format');
		}

		return $response;

	}

	/**
	 * @param string $uri
	 */
	private static function relocate($uri){
		header(sprintf('Location: %s', $uri));
		exit();
	}

	/**
	 * @param $api_endpoint
	 */
	public static function set_api_endpoint($api_endpoint){
		self::$api_endpoint = $api_endpoint;
	}

	/**
	 * @return string
	 */
	public static function get_api_endpoint() {
		return self::$api_endpoint;
	}

	/**
	 * @param $abbreviated_api_endpoint
	 * @return string
	 */
	public static function expand_api_endpoint($abbreviated_api_endpoint) {
		$api_endpoint = $abbreviated_api_endpoint;
		switch (strtolower($abbreviated_api_endpoint)) {
			case 'gevestor':
				$api_endpoint = self::API_ENDPOINT_GEVESTOR;
				break;
			case 'fid':
				$api_endpoint = self::API_ENDPOINT_FID;
				break;
			case 'computerwissen':
				$api_endpoint = self::API_ENDPOINT_COMPUTERWISSEN;
				break;
			case 'b2b':
				$api_endpoint = self::API_ENDPOINT_B2B;
				break;
		}
		return $api_endpoint;
	}

	/**
	 * @param int $process_id
	 * @param string $email_address
	 * @return stdClass
	 * @throws Exception
	 */
	public static function get_opt_in_email_information($process_id, $email_address) {

		if (isset(self::$opt_in_email_information_cache[$process_id . $email_address])) {
			return self::$opt_in_email_information_cache[$process_id . $email_address];
		}

		$response = self::remote_call('GetOptInEMailInformation', [self::API_PASSWORD, $process_id, $email_address]);

		return self::$opt_in_email_information_cache[$process_id . $email_address] = $response;

	}

	/**
	 * @param $method
	 * @param array $parameters
	 * @return mixed
	 * @throws Exception
	 */
	private static function remote_call($method, $parameters = []) {

		$request = new stdClass();
		$request->Method = $method;
		$request->Parameters = $parameters;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, self::get_api_endpoint());
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$raw_response = curl_exec($ch);
		if (curl_errno($ch) > 0) {
			throw new Exception(curl_error($ch));
		}
		curl_close($ch);

		$response = json_decode($raw_response);
		if ($response === null) {
			file_put_contents(
				__DIR__ . DIRECTORY_SEPARATOR . 'nss_api_errors.txt',
				sprintf("======== %s ========\r\n%s\r\n================", strftime('%Y-%m-%d %H:%M:%S'), $raw_response),
				FILE_APPEND
			);
			throw new Exception('response json cannot be decoded');
		}

		if (count($response->Errors) > 0) {
			$error_message = '';
			foreach($response->Errors as $error) {
				if (!is_object($error)) {
					$error_message .= 'invalid error format';
				} else {
					if (!isset($error->Message) || strlen(trim($error->Message)) === 0) {
						$error_message .= 'unknown error';
					} else {
						$error_message .= $error->Message;
					}
				}
				$error_message .= ', ';
			}
			throw new Exception(trim($error_message, ' ,'));
		}

		if (!isset($response->Result)) {
			throw new Exception('invalid response format');
		}

		return $response->Result;

	}

}
