<?php

	namespace Intrum;

	class Cache
	{
		private static $instance = null;
		private $folder = null;
		private $expire = null;
	
		private function __construct()
		{
			$this->folder = "cache";
			$this->expire = 600;
		}
		
		//инициализация
		public static function getInstance()
		{
			if(null === self::$instance)
				self::$instance = new self();
		
			return self::$instance;
		}
		
		/*настройки
			( 
				array(
					$folder => cache директория
					$expire => время хранения в секундах
				)
			)
		*/
		public function setup(array $params)
		{
			if(isset($params['folder'])) $this->folder = $params['folder'];
			if(isset($params['expire'])) $this->expire = $params['expire'];
		}
		
		//запись
		public function push($key,$data)
		{
			file_put_contents(
				$this->folder . "/{$key}.cache",
				serialize($data)
			);
		}

		//чтение
		public function pop($key)
		{
			$file = $this->folder . "/{$key}.cache";
		
			if(file_exists($file) and (time() - filemtime($file)) <= $this->expire)
				return unserialize(file_get_contents($file));
			
			return false;
		}
		
		//очистка	
		public function clear()
		{
			$find = glob($this->folder . "/*.cache");
		
			if($find){
				foreach($find as $f){
					@unlink($f);
				}	
			}
		}
	}
?>