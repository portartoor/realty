<?php
class CustomSmsService extends IntrumSmsApi {
	
	public function __construct($apiKey, $crmUrl) {
		$this->apiKey = $apiKey;
		$this->crmUrl = $crmUrl;
	}
	
	protected function sendSms($sender, $destination, $text) {
		// TODO: Произвести реальную отправку смс
		
		return time(); // Заменить на реальный id
	}
	
	protected function smsStatus($smsId) {
		// TODO: Проверить статус смс
		
		$statuses = array('new', 'inprogress', 'send', 'delivered', 'notdelivered', 'blocked', 'absent', 'notfound');
		return $statuses[ array_rand($statuses) ];
	}
	
	public function smsRecieved($smsId, $sender, $destination, $message, $count) {
		// TODO: А этот метод должен вызываться при получении смс
		
		$params = array(
			'action' => 'smsRecieved',
			'sms_id' => $smsId,
			'sender' => $sender,
			'destination' => $destination,
			'message' => $message,
			'count' => $count,
			'date' => (time() - 60 * 15) // Здесь должно быть реальное время получения смс (необязательный параметр)
		);
		
		return parent::apiRequest($params, $this->apiKey, $this->crmUrl);
	}				
	
	public function routeApiRequest($post) {
		$subresp = parent::routeApiRequest($post, $this->apiKey);
		
		return $subresp;
	}
	
	
}
