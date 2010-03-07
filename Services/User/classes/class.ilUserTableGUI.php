<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Table/classes/class.ilTable2GUI.php");

/**
* TableGUI class for user administration
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @ilCtrl_Calls ilUserTableGUI: ilFormPropertyDispatchGUI
* @ingroup ServicesUser
*/
class ilUserTableGUI extends ilTable2GUI
{
	
	/**
	* Constructor
	*/
	function __construct($a_parent_obj, $a_parent_cmd)
	{
		global $ilCtrl, $lng, $ilAccess, $lng, $rbacsystem;
		
		$this->setId("user");
		
		parent::__construct($a_parent_obj, $a_parent_cmd);
//		$this->setTitle($this->lng->txt("users"));
		
		$this->addColumn("", "", "1", true);
		$this->addColumn($this->lng->txt("login"), "login");
		
		foreach ($this->getSelectedColumns() as $c)
		{
			$this->addColumn($this->lng->txt($c), $c);
		}
				
		$this->setExternalSorting(true);
		$this->setExternalSegmentation(true);
		$this->setEnableHeader(true);
		$this->setFormAction($ilCtrl->getFormAction($this->parent_obj, "applyFilter"));
		$this->setRowTemplate("tpl.user_list_row.html", "Services/User");
		//$this->disable("footer");
		$this->setEnableTitle(true);
		$this->initFilter();
		$this->setFilterCommand("applyFilter");
		$this->setDefaultOrderField("login");
		$this->setDefaultOrderDirection("asc");

		$this->setSelectAllCheckbox("id[]");
		$this->setTopCommands(true);

		if ($rbacsystem->checkAccess('delete', $a_parent_obj->object->getRefId()))
		{
			$this->addMultiCommand("deleteUsers", $lng->txt("delete"));
		}
		$this->addMultiCommand("activateUsers", $lng->txt("activate"));
		$this->addMultiCommand("deactivateUsers", $lng->txt("deactivate"));
		$this->addMultiCommand("restrictAccess", $lng->txt("accessRestrict"));
		$this->addMultiCommand("freeAccess", $lng->txt("accessFree"));
		$this->addMultiCommand("exportUsers", $lng->txt("export"));
		//$this->addCommandButton("importUserForm", $lng->txt("import_users"));
		//$this->addCommandButton("addUser", $lng->txt("usr_add"));
		
		$this->getItems();
	}
	
	/**
	 * Get selectable columns
	 *
	 * @param
	 * @return
	 */
	function getSelectableColumns()
	{
		global $lng;
		
		include_once("./Services/User/classes/class.ilUserProfile.php");
		$up = new ilUserProfile();
		$up->skipGroup("preferences");
		$up->skipGroup("settings");
		$ufs = $up->getStandardFields();

		// default fields
		$cols = array();
		$cols["firstname"] = array(
			"txt" => $lng->txt("firstname"),
			"default" => true);
		$cols["lastname"] = array(
			"txt" => $lng->txt("lastname"),
			"default" => true);
		$cols["email"] = array(
			"txt" => $lng->txt("email"),
			"default" => true);
		$cols["access_until"] = array(
			"txt" => $lng->txt("access_until"),
			"default" => true);
		$cols["last_login"] = array(
			"txt" => $lng->txt("last_login"),
			"default" => true);
		
		// other user profile fields
		foreach ($ufs as $f => $fd)
		{
			if (!isset($cols[$f]) && !$fd["lists_hide"])
			{
				$cols[$f] = array(
					"txt" => $lng->txt($f),
					"default" => false);
			}
		}
		
		// fields that are always shown
		unset($cols["username"]);
		
		return $cols;
	}
	
	/**
	* Get user items
	*/
	function getItems()
	{
		global $lng;
//if ($GLOBALS["kk"]++ == 1) nj();

		$this->determineOffsetAndOrder();
		
		include_once("./Services/User/classes/class.ilUserQuery.php");
		
		$additional_fields = $this->getSelectedColumns();
		unset($additional_fields["firstname"]);
		unset($additional_fields["lastname"]);
		unset($additional_fields["email"]);
		unset($additional_fields["last_login"]);
		unset($additional_fields["access_until"]);

		$usr_data = ilUserQuery::getUserListData(
			ilUtil::stripSlashes($this->getOrderField()),
			ilUtil::stripSlashes($this->getOrderDirection()),
			ilUtil::stripSlashes($this->getOffset()),
			ilUtil::stripSlashes($this->getLimit()),
			$this->filter["query"],
			$this->filter["activation"],
			$this->filter["last_login"],
			$this->filter["limited_access"],
			$this->filter["no_courses"],
			$this->filter["course_group"],
			$this->filter["global_role"],
			$additional_fields
			);
			
		if (count($usr_data["set"]) == 0 && $this->getOffset() > 0)
		{
			$this->resetOffset();
			$usr_data = ilUserQuery::getUserListData(
				ilUtil::stripSlashes($this->getOrderField()),
				ilUtil::stripSlashes($this->getOrderDirection()),
				ilUtil::stripSlashes($this->getOffset()),
				ilUtil::stripSlashes($this->getLimit()),
				$this->filter["query"],
				$this->filter["activation"],
				$this->filter["last_login"],
				$this->filter["limited_access"],
				$this->filter["no_courses"],
				$this->filter["course_group"],
				$this->filter["global_role"],
				$additional_fields
				);
		}

		foreach ($usr_data["set"] as $k => $user)
		{
			$current_time = time();
			if ($user['active'])
			{
				if ($user["time_limit_unlimited"])
				{
					$txt_access = $lng->txt("access_unlimited");
					$usr_data["set"][$k]["access_class"] = "smallgreen";
				}
				elseif ($user["time_limit_until"] < $current_time)
				{
					$txt_access = $lng->txt("access_expired");
					$usr_data["set"][$k]["access_class"] = "smallred";
				}
				else
				{
					$txt_access = ilDatePresentation::formatDate(new ilDateTime($user["time_limit_until"],IL_CAL_UNIX));
					$usr_data["set"][$k]["access_class"] = "small";
				}
			}
			else
			{
				$txt_access = $lng->txt("inactive");
				$usr_data["set"][$k]["access_class"] = "smallred";
			}
			$usr_data["set"][$k]["access_until"] = $txt_access;
		}

		$this->setMaxCount($usr_data["cnt"]);
		$this->setData($usr_data["set"]);
	}
	
	
	/**
	* Init filter
	*/
	function initFilter()
	{
		global $lng, $rbacreview, $ilUser;
		
		// title/description
		include_once("./Services/Form/classes/class.ilTextInputGUI.php");
		$ti = new ilTextInputGUI($lng->txt("login")."/".$lng->txt("email")."/".$lng->txt("name"), "query");
		$ti->setMaxLength(64);
		$ti->setSize(20);
		$ti->setSubmitFormOnEnter(true);
		$this->addFilterItem($ti);
		$ti->readFromSession();
		$this->filter["query"] = $ti->getValue();
		
		// activation
		include_once("./Services/Form/classes/class.ilSelectInputGUI.php");
		$options = array(
			"" => $lng->txt("user_all"),
			"active" => $lng->txt("active"),
			"inactive" => $lng->txt("inactive"),
			);
		$si = new ilSelectInputGUI($this->lng->txt("user_activation"), "activation");
		$si->setOptions($options);
		$this->addFilterItem($si);
		$si->readFromSession();
		$this->filter["activation"] = $si->getValue();
		
		// limited access
		include_once("./Services/Form/classes/class.ilCheckboxInputGUI.php");
		$cb = new ilCheckboxInputGUI($this->lng->txt("user_limited_access"), "limited_access");
		$this->addFilterItem($cb);
		$cb->readFromSession();
		$this->filter["limited_access"] = $cb->getChecked();
		
		// last login
		include_once("./Services/Form/classes/class.ilDateTimeInputGUI.php");
		$di = new ilDateTimeInputGUI($this->lng->txt("user_last_login_before"), "last_login");
		$default_date = new ilDateTime(time(),IL_CAL_UNIX);
		$default_date->increment(IL_CAL_DAY, 1);
		$di->setDate($default_date);
		$this->addFilterItem($di);
		$di->readFromSession();
		$this->filter["last_login"] = $di->getDate();

		// no assigned courses
		include_once("./Services/Form/classes/class.ilCheckboxInputGUI.php");
		$cb = new ilCheckboxInputGUI($this->lng->txt("user_no_courses"), "no_courses");
		$this->addFilterItem($cb);
		$cb->readFromSession();
		$this->filter["no_courses"] = $cb->getChecked();
		
		// course/group members
		include_once("./Services/Form/classes/class.ilRepositorySelectorInputGUI.php");
		$rs = new ilRepositorySelectorInputGUI($lng->txt("user_member_of_course_group"), "course_group");
		$rs->setSelectText($lng->txt("user_select_course_group"));
		$rs->setHeaderMessage($lng->txt("user_please_select_course_group"));
		$rs->setClickableTypes(array("crs", "grp"));
		$this->addFilterItem($rs);
		$rs->readFromSession();
		$this->filter["course_group"] = $rs->getValue();

		// global roles
		$options = array(
			"" => $lng->txt("user_any"),
			);
		$roles = $rbacreview->getRolesByFilter(2, $ilUser->getId());
		foreach ($roles as $role)
		{
			$options[$role["rol_id"]] = $role["title"];
		}
		$si = new ilSelectInputGUI($this->lng->txt("user_global_role"), "global_role");
		$si->setOptions($options);
		$this->addFilterItem($si);
		$si->readFromSession();
		$this->filter["global_role"] = $si->getValue();
	}
	
	/**
	* Fill table row
	*/
	protected function fillRow($user)
	{
		global $ilCtrl, $lng;

		foreach ($this->getSelectedColumns() as $c)
		{
			if ($c == "access_until")
			{
				$this->tpl->setCurrentBlock("access_until");
				$this->tpl->setVariable("VAL_ACCESS_UNTIL", $user["access_until"]);
				$this->tpl->setVariable("CLASS_ACCESS_UNTIL", $user["access_class"]);						
			}
			else if ($c == "last_login")
			{
				$this->tpl->setCurrentBlock("last_login");
				$this->tpl->setVariable("VAL_LAST_LOGIN",
					ilDatePresentation::formatDate(new ilDateTime($user['last_login'],IL_CAL_DATETIME)));
			}
			else if (in_array($c, array("email", "firstname", "lastname")))
			{
				$this->tpl->setCurrentBlock($c);
				$this->tpl->setVariable("VAL_".strtoupper($c), $user[$c]);
			}
			else	// all other fields
			{
				$this->tpl->setCurrentBlock("user_field");
				$val = (trim($user[$c]) == "")
					? " "
					: $user[$c];
					
				if ($user[$c] != "")
				{
					switch ($c)
					{
						case "gender":
							$val = $lng->txt("gender_".$user[$c]);
							break;
					}
				}
				$this->tpl->setVariable("VAL_UF", $val);
			}
			
			$this->tpl->parseCurrentBlock();
		}
		
		if ($user["usr_id"] != 6)
		{
			$this->tpl->setCurrentBlock("checkb");
			$this->tpl->setVariable("ID", $user["usr_id"]);
			$this->tpl->parseCurrentBlock();
		}
		
		$this->tpl->setVariable("VAL_LOGIN", $user["login"]);
		$ilCtrl->setParameterByClass("ilobjusergui", "obj_id", $user["usr_id"]);
		$this->tpl->setVariable("HREF_LOGIN",
			$ilCtrl->getLinkTargetByClass("ilobjusergui", "view"));
		$ilCtrl->setParameterByClass("ilobjusergui", "obj_id", "");
	}

}
?>
