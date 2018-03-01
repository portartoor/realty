<?
class Page {
	public function Parametres() {
		$parametres = array(
			'sell' => array('title'=>'Продажи'),
			'list' => array('title'=>'Продажи - список', 'page_list_limit'=>20)
		);
		
		return $parametres;
    }
}