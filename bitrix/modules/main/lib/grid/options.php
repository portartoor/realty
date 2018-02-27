<?

namespace Bitrix\Main\Grid;


class Options extends \CGridOptions
{
	public function setColumnsSizes($expand, $sizes)
	{
		$columns = array();
		foreach ((array) $sizes as $name => $width)
		{
			$name  = trim($name);
			$width = is_scalar($width) ? (int) $width : 0;
			if ($name != '' && $width > 0)
				$columns[$name] = $width;
		}

		$this->all_options['views'][$this->currentView]['columns_sizes'] = array(
			'expand'  => is_scalar($expand) ? round((float) $expand, 8) : 1,
			'columns' => $columns
		);
	}

	public function setPageSize($size)
	{
		$size = is_scalar($size) ? (int) $size : 20;
		$size = $size > 0 ? $size : 20;

		$this->all_options['views'][$this->currentView]['page_size'] = $size;
	}

	public function setCustomNames($names)
	{
		$this->all_options["views"]["default"]["custom_names"] = $names;
	}

	public function deleteView($view_id)
	{
		unset($this->all_options["views"][$view_id]);
		unset($_SESSION["main.interface.grid"][$this->grid_id]);
		$this->Save();
		parent::__construct($this->grid_id);
	}

}