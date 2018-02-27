<?

namespace Bitrix\Main\Grid;

class Declension
{
	public $oneItem;
	public $fourItem;
	public $fiveItem;

	public function __construct($one = "", $four = "", $five = "")
	{
		$this->oneItem = $one;
		$this->fourItem = $four;
		$this->fiveItem = $five;
	}


	public function get($number)
	{
		$result = $this->fiveItem;
		$number = $number % 100;

		if ($number >= 11 && $number <= 19)
		{
			$result = $this->fiveItem;
		}
		else
		{
			$number = $number % 10;

			if ($number === 1)
			{
				$result = $this->oneItem;
			}

			if ($number >= 2 && $number <= 4)
			{
				$result = $this->fourItem;
			}
		}

		return $result;
	}
}