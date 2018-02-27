<?

namespace Bitrix\Main\Grid\Panel\Snippet;


use Bitrix\Main\Grid\Panel\Snippet;
use Bitrix\Main\Grid\Panel\Types;


class Checkbox
{
	protected $id = "";
	protected $name = "";
	protected $class = "";
	protected $text = "";
	protected $onchange;
	protected $value = "";

	public function __construct()
	{
		$this->type = Types::CHECKBOX;
		$this->id = 'panel_control_'.uniqid();
	}

	public function setValue($value = "")
	{
		$this->value = $value;
		return $this;
	}

	public function setId($id = "")
	{
		$this->id = $id;
		return $this;
	}

	public function setName($name = "")
	{
		$this->name = $name;
		return $this;
	}

	public function setClass($class = "")
	{
		$this->class = $class;
		return $this;
	}

	public function setText($text = "")
	{
		$this->text = $text;
		return $this;
	}

	public function setOnchange(Snippet\Onchange $onchange)
	{
		$this->onchange = $onchange;
		return $this;
	}

	public function toArray()
	{
		$result = array(
			"TYPE" => $this->type,
			"ID" => $this->id,
			"NAME" => $this->name,
			"CLASS" => $this->class,
			"LABEL" => $this->text,
			"VALUE" => $this->value,
			"ONCHANGE" => $this->onchange->toArray()
		);

		return $result;
	}
}