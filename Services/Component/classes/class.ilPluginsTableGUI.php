<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2008 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/

include_once("Services/Table/classes/class.ilTable2GUI.php");


/**
* TableGUI class for plugins listing
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @ingroup ServicesComponent
*/
class ilPluginsTableGUI extends ilTable2GUI
{
	function ilPluginsTableGUI($a_parent_obj, $a_parent_cmd = "",
		$a_c_type, $a_c_name, $a_slot_id)
	{
		global $ilCtrl, $lng;
		
		include_once("./Services/Component/classes/class.ilPluginSlot.php");
		$this->slot = new ilPluginSlot($a_c_type, $a_c_name, $a_slot_id);
		
		parent::__construct($a_parent_obj, $a_parent_cmd);
		
		//$this->addColumn($lng->txt("cmps_module"));
		$this->addColumn($lng->txt("cmps_plugin"));
		$this->addColumn($lng->txt("cmps_basic_files"));
		$this->addColumn($lng->txt("cmps_languages"));
		$this->addColumn($lng->txt("cmps_database"));
		
		$this->setEnableHeader(true);
		$this->setFormAction($ilCtrl->getFormAction($a_parent_obj));
		$this->setRowTemplate("tpl.table_row_plugin.html",
			"Services/Component");
		$this->getPlugins();
		//$this->setDefaultOrderField("subdir");
		$this->setLimit(10000);
		
		// save options command
		//$this->addCommandButton("saveOptions", $lng->txt("cmps_save_options"));

		$this->setTitle($lng->txt("cmps_plugins"));
	}
	
	/**
	* Get pages for list.
	*/
	function getPlugins()
	{
		$plugins = $this->slot->getPluginsInformation();
//var_dump($plugins);
		$this->setData($plugins);
	}
	
	/**
	* Standard Version of Fill Row. Most likely to
	* be overwritten by derived class.
	*/
	protected function fillRow($a_set)
	{
		global $lng, $ilCtrl;

		$this->tpl->setVariable("VAL_PLUGIN_NAME", $a_set["name"]);
		$this->tpl->setVariable("VAL_PLUGIN_ID", $a_set["id"]);
		$this->tpl->setVariable("TXT_PLUGIN_NAME", $lng->txt("cmps_name"));
		$this->tpl->setVariable("TXT_PLUGIN_ID", $lng->txt("cmps_id"));
		$this->tpl->setVariable("TXT_PLUGIN_VERSION", $lng->txt("cmps_version"));
		$this->tpl->setVariable("TXT_XML_FILE", "plugin.xml");
		$this->tpl->setVariable("TXT_CLASS_FILE", $lng->txt("cmps_class_file"));
		$this->tpl->setVariable("VAL_CLASS_FILE", $a_set["class_file"]);
		$this->tpl->setVariable("TXT_VERSION", $lng->txt("cmps_version"));
		$this->tpl->setVariable("VAL_PLUGIN_VERSION", $a_set["current_version"]);
		$this->tpl->setVariable("TXT_ILIAS_MIN", $lng->txt("cmps_ilias_min_version"));
		$this->tpl->setVariable("VAL_ILIAS_MIN", $a_set["ilias_min_version"]);
		$this->tpl->setVariable("TXT_ILIAS_MAX", $lng->txt("cmps_ilias_max_version"));
		$this->tpl->setVariable("VAL_ILIAS_MAX", $a_set["ilias_max_version"]);
		
		if ($a_set["xml_file_status"])
		{
			$this->tpl->setVariable("VAL_XML_FILE_STATUS", $lng->txt("cmps_available"));
		}
		else
		{
			$this->tpl->setVariable("VAL_XML_FILE_STATUS", $lng->txt("cmps_missing"));
		}
		if ($a_set["class_file_status"])
		{
			$this->tpl->setVariable("VAL_CLASS_FILE_STATUS", $lng->txt("cmps_available"));
		}
		else
		{
			$this->tpl->setVariable("VAL_CLASS_FILE_STATUS", $lng->txt("cmps_missing"));
		}
	}

}
?>
