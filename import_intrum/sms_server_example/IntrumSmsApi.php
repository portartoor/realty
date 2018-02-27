<?php
abstract class IntrumSmsApi {
	public $errorMessage = '';
	
	/* Отправить смс с текстом $text на номер $destination используя имя $sender */
	abstract protected function sendSms($sender, $destination, $text);
	
	/* Получить статус отправки смс с идентификатором $smsId */
	abstract protected function smsStatus($smsId);
	
	/* Сообщить о полученном сообщении */
	abstract protected function smsRecieved($smsId, $sender, $destination, $message, $count);
	
	/* Запрос к серверу INTRUM */
	public function apiRequest($params, $apiKey, $url) {
		ksort($params);
		
		$hash = md5(implode('', $params) . $apiKey);
		$params['hash'] = $hash;
		
		$query = http_build_query ($params);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_ENCODING , '');
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		$result = curl_exec($ch);
		
		if ( curl_errno($ch) ) {
			//
		}

		curl_close ($ch);

		$result = json_decode($result, true);
		
		return $result;
	}
	
	/* Разбор запроса.
	 * $post - ассоциативный массив параметров POST запроса от CRM
	 */
	public function routeApiRequest($post, $apiKey) {
		$response = array(
			'error' => 'Неверный запрос',
			'success' => false
		);
		
		if (isset($post['hash'])) {
			$hash = mb_strtolower($post['hash']);
			unset($post['hash']);
			
			ksort($post);
			
			$check = mb_strtolower( md5(implode('', $post) . $apiKey) );
			if ($check !== $hash) return $response;
		}
		
		if (isset($post['action'])) {
			$response['success'] = true;
			
			switch ($post['action']) {
				case 'sendSms':
					unset($response['error']);
					$response['data'] = $this->sendSms($post['sender'], $post['destination'], $post['text']);
					if ($response['data'] === false) {
						$response['data'] = $this->errorMessage;
						$response['success'] = false;
					}
				break;
				
				case 'smsStatus':
					$response = array(
						'success' => true,
						'count' => 1,
						'status' => $this->smsStatus($post['sms_id'])
					);
				break;
				
				default:
					$response['success'] = false;
					$response['error'] = 'Неизвестный запрос';
				break;
			}
		}
		
		return $response;
	}
}
