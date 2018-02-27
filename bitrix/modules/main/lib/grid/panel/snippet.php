<?

namespace Bitrix\Main\Grid\Panel;


use Bitrix\Main\Grid\Panel\Snippet\Button;
use Bitrix\Main\Grid\Panel\Snippet\Checkbox;
use Bitrix\Main\Grid\Panel\Snippet\Onchange;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);


class Snippet
{
	protected $applyButton;
	protected $saveButton;
	protected $cancelButton;
	protected $editButton;
	protected $removeButton;
	protected $saveAndCancelButtons;
	protected $forAllCheckbox;


	public function getSaveEditButton()
	{
		if (!is_array($this->saveButton))
		{
			$onchange = new Onchange();
			$onchange->addAction(array("ACTION" => Actions::SHOW_ALL, "DATA" => array()));
			$onchange->addAction(array("ACTION" => Actions::CALLBACK, "DATA" => array(array("JS" => "Grid.editSelectedSave()"))));
			$onchange->addAction(array("ACTION" => Actions::REMOVE, "DATA" => array(array("ID" => DefaultValue::SAVE_BUTTON_ID), array("ID" => DefaultValue::CANCEL_BUTTON_ID))));

			$saveButton = new Button();
			$saveButton->setClass(DefaultValue::SAVE_BUTTON_CLASS);
			$saveButton->setText(Loc::getMessage("SAVE_BUTTON_TEXT"));
			$saveButton->setId(DefaultValue::SAVE_BUTTON_ID);
			$saveButton->setOnchange($onchange);

			$this->saveButton = $saveButton->toArray();
		}

		return $this->saveButton;
	}

	public function getCancelEditButton()
	{
		if (!is_array($this->cancelButton))
		{
			$onchange = new Onchange();
			$onchange->addAction(array("ACTION" => Actions::SHOW_ALL, "DATA" => array()));
			$onchange->addAction(array("ACTION" => Actions::CALLBACK, "DATA" => array(array("JS" => "Grid.editSelectedCancel()"))));
			$onchange->addAction(array("ACTION" => Actions::REMOVE, "DATA" => array(array("ID" => DefaultValue::SAVE_BUTTON_ID), array("ID" => DefaultValue::CANCEL_BUTTON_ID))));

			$cancelButton = new Button();
			$cancelButton->setClass(DefaultValue::CANCEL_BUTTON_CLASS);
			$cancelButton->setText(Loc::getMessage("CANCEL_BUTTON_TEXT"));
			$cancelButton->setId(DefaultValue::CANCEL_BUTTON_ID);
			$cancelButton->setOnchange($onchange);

			$this->cancelButton = $cancelButton->toArray();
		}

		return $this->cancelButton;
	}

	public function getEditButton()
	{
		if (!is_array($this->editButton))
		{
			$onchange = new Onchange();
			$onchange->addAction(array("ACTION" => Actions::CREATE, "DATA" => array($this->getSaveEditButton(), $this->getCancelEditButton())));
			$onchange->addAction(array("ACTION" => Actions::CALLBACK, "DATA" => array(array("JS" => "Grid.editSelected()"))));
			$onchange->addAction(array("ACTION" => Actions::HIDE_ALL_EXPECT, "DATA" => array(array("ID" => DefaultValue::SAVE_BUTTON_ID), array("ID" => DefaultValue::CANCEL_BUTTON_ID))));

			$editButton = new Button();
			$editButton->setClass(DefaultValue::EDIT_BUTTON_CLASS);
			$editButton->setId(DefaultValue::EDIT_BUTTON_ID);
			$editButton->setText(Loc::getMessage("EDIT_ACTION_NAME"));
			$editButton->setOnchange($onchange);
			$editButton->setTitle(Loc::getMessage("EDIT_BUTTON_TITLE"));

			$this->editButton = $editButton->toArray();
		}

		return $this->editButton;
	}

	public function getEditAction()
	{
		return array(
			"NAME" =>  GetMessage("EDIT_ACTION_NAME"),
			"VALUE" => "edit",
			"ONCHANGE" => array(
				array("ACTION" => Actions::CREATE, "DATA" => array($this->getSaveEditButton(), $this->getCancelEditButton())),
				array("ACTION" => Actions::CALLBACK, "DATA" => array(array("JS" => "Grid.editSelected()"))),
				array("ACTION" => Actions::HIDE_ALL_EXPECT, "DATA" => array(array("ID" => DefaultValue::SAVE_BUTTON_ID), array("ID" => DefaultValue::CANCEL_BUTTON_ID)))
			)
		);
	}

	public function getRemoveButton()
	{
		if (!is_array($this->removeButton))
		{
			$onchange = new Onchange();
			$onchange->addAction(
				array(
					"ACTION" => Actions::CALLBACK,
					"CONFIRM" => true,
					"CONFIRM_APPLY_BUTTON" => Loc::getMessage("CONFIRM_APPLY_REMOVE_BUTTON_TEXT"),
					"DATA" => array(
						array("JS" => "Grid.removeSelected()")
					)
				)
			);

			$removeButton = new Button();
			$removeButton->setClass(DefaultValue::REMOVE_BUTTON_CLASS);
			$removeButton->setId(DefaultValue::REMOVE_BUTTON_ID);
			$removeButton->setOnchange($onchange);
			$removeButton->setText(Loc::getMessage("REMOVE_ACTION_NAME"));
			$removeButton->setTitle(Loc::getMessage("REMOVE_BUTTON_TITLE"));

			$this->removeButton = $removeButton->toArray();
		}

		return $this->removeButton;
	}

	public function getRemoveAction()
	{
		return array(
			"NAME" =>  GetMessage("REMOVE_ACTION_NAME"),
			"VALUE" => "remove",
			"ONCHANGE" => array(
				array(
					"ACTION" => Actions::CALLBACK,
					"CONFIRM" => true,
					"CONFIRM_APPLY_BUTTON" => Loc::getMessage("CONFIRM_APPLY_REMOVE_BUTTON_TEXT"),
					"DATA" => array(
						array("JS" => "Grid.removeSelected()")
					)
				)
			)
		);
	}

	public function getApplyButton(array $params)
	{
		return array(
			'TYPE' => Types::BUTTON,
			'ID' => "apply_button",
			'CLASS' => "apply",
			'TEXT' => Loc::getMessage("APPLY_BUTTON_TEXT"),
			'ONCHANGE' => isset($params['ONCHANGE']) && is_array($params['ONCHANGE']) ? $params['ONCHANGE'] : array()
		);
	}

	public function getForAllCheckbox()
	{
		if (!is_array($this->forAllCheckbox))
		{
			$onchange = new Onchange();
			$onchange->addAction(array("ACTION" => Actions::CALLBACK, "DATA" => array(array("JS" => "Grid.confirmForAll()"))));

			$checkbox = new Checkbox();
			$checkbox->setId(DefaultValue::FOR_ALL_CHECKBOX_ID);
			$checkbox->setOnchange($onchange);
			$checkbox->setName(DefaultValue::FOR_ALL_CHECKBOX_NAME);
			$checkbox->setValue(DefaultValue::FOR_ALL_CHECKBOX_VALUE);
			$checkbox->setClass(DefaultValue::FOR_ALL_CHECKBOX_CLASS);
			$this->forAllCheckbox = $checkbox->toArray();
		}

		return $this->forAllCheckbox;
	}
}