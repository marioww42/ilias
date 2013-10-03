<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * Class ilOrgUnitTranslation GUI class
 *
 * Based on Methods of ilObjCategoryGUI
 *
 * @author: Martin Studer <ms@studer-raimann.ch>
 * Date: 4/07/13
 * Time: 1:09 PM
 *
 * @ilCtrl_Calls ilOrgUnitTranslationGUI:
 */


//class ilObjOrgUnitGUI extends ilObjCategoryGUI{
class ilOrgUnitTranslationGUI {


	function __construct(ilObjOrgUnitGUI $ilObjOrgUnitGUI)
    {
		global $tpl, $ilCtrl, $ilDB, $lng;
		/**
		 * @var $tpl    ilTemplate
		 * @var $ilCtrl ilCtrl
		 * @var $ilDB ilDB
		 */
		$this->tpl = $tpl;
		$this->ctrl = $ilCtrl;
		$this->lng = $lng;

        $this->ilObjOrgUnitGui = $ilObjOrgUnitGUI;
        $this->ilObjectOrgUnit = $ilObjOrgUnitGUI->object;
    }

    public function executeCommand()
    {
        $cmd = $this->ctrl->getCmd();
        $this->$cmd();
    }


	public function editTranslations($a_get_post_values = false, $a_add = false)
    {

        $this->lng->loadLanguageModule($this->ilObjectOrgUnit->getType());
        //$this->setEditTabs("settings_trans");

        include_once("./Services/Object/classes/class.ilObjectTranslationTableGUI.php");
        $table = new ilObjectTranslationTableGUI($this, "editTranslations", true,
            "Translation");
        if ($a_get_post_values)
        {
            $vals = array();
            foreach($_POST["title"] as $k => $v)
            {
                $vals[] = array("title" => $v,
                    "desc" => $_POST["desc"][$k],
                    "lang" => $_POST["lang"][$k],
                    "default" => ($_POST["default"] == $k));
            }
            $table->setData($vals);
        }
        else
        {
            $data = $this->ilObjectOrgUnit->getTranslations();
            foreach($data["Fobject"] as $k => $v)
            {
                $data["Fobject"][$k]["default"] = ($k == $data["default_language"]);
            }
            if($a_add)
            {
                $data["Fobject"][++$k]["title"] = "";
            }
            $table->setData($data["Fobject"]);
        }
        $this->tpl->setContent($table->getHTML());

		//$this->setContentSubTabs();
	}


    /**
     * Save title and translations
     */
    function saveTranslations()
    {
        // default language set?
        if (!isset($_POST["default"]))
        {
            ilUtil::sendFailure($this->lng->txt("msg_no_default_language"));
            return $this->editTranslations(true);
        }

        // all languages set?
        if (array_key_exists("",$_POST["lang"]))
        {
            ilUtil::sendFailure($this->lng->txt("msg_no_language_selected"));
            return $this->editTranslations(true);
        }

        // no single language is selected more than once?
        if (count(array_unique($_POST["lang"])) < count($_POST["lang"]))
        {
            ilUtil::sendFailure($this->lng->txt("msg_multi_language_selected"));
            return $this->editTranslations(true);
        }

        // save the stuff
        $this->ilObjectOrgUnit->removeTranslations();
        foreach($_POST["title"] as $k => $v)
        {
            // update object data if default
            $is_default = ($_POST["default"] == $k);
            if($is_default)
            {
                $this->ilObjectOrgUnit->setTitle(ilUtil::stripSlashes($v));
                $this->ilObjectOrgUnit->setDescription(ilUtil::stripSlashes($_POST["desc"][$k]));
                $this->ilObjectOrgUnit->update();
            }

            $this->ilObjectOrgUnit->addTranslation(
                ilUtil::stripSlashes($v),
                ilUtil::stripSlashes($_POST["desc"][$k]),
                ilUtil::stripSlashes($_POST["lang"][$k]),
                $is_default);
        }

        ilUtil::sendSuccess($this->lng->txt("msg_obj_modified"), true);
        $this->ctrl->redirect($this, "editTranslations");
    }

    /**
     * Add a translation
     */
    function addTranslation()
    {

        if($_POST["title"])
        {
            $k = max(array_keys($_POST["title"]));
            $k++;
            $_POST["title"][$k] = "";
            $this->editTranslations(true);
        }
        else
        {
            $this->editTranslations(false, true);
        }
    }

    /**
     * Remove translation
     */
    function deleteTranslations()
    {
        foreach($_POST["title"] as $k => $v)
        {
            if ($_POST["check"][$k])
            {
                // default translation cannot be deleted
                if($k != $_POST["default"])
                {
                    unset($_POST["title"][$k]);
                    unset($_POST["desc"][$k]);
                    unset($_POST["lang"][$k]);
                }
                else
                {
                    ilUtil::sendFailure($this->lng->txt("msg_no_default_language"));
                    return $this->editTranslations();
                }
            }
        }
        $this->saveTranslations();
    }

}