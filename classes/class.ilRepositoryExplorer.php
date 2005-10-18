<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2001 ILIAS open source, University of Cologne            |
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

/*
* Repository Explorer
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @package core
*/

require_once("classes/class.ilExplorer.php");

class ilRepositoryExplorer extends ilExplorer
{

	/**
	 * id of root folder
	 * @var int root folder id
	 * @access private
	 */
	var $root_id;
	var $output;
	var $ctrl;
	/**
	* Constructor
	* @access	public
	* @param	string	scriptname
	* @param    int user_id
	*/
	function ilRepositoryExplorer($a_target)
	{
		global $tree,$ilCtrl;

		$this->ctrl = $ilCtrl;

		parent::ilExplorer($a_target);
		$this->tree = $tree;
		$this->root_id = $this->tree->readRootId();
		$this->order_column = "title";
		$this->setSessionExpandVariable("repexpand");

		// please do not uncomment this
		$this->addFilter("root");
		$this->addFilter("cat");
		//$this->addFilter("exc");
		//$this->addFilter("sahs");
		$this->addFilter("grp");
		//$this->addFilter("lm");
		//$this->addFilter("htlm");
		//$this->addFilter("mep");
		//$this->addFilter("frm");
		//$this->addFilter("dbk");
		//$this->addFilter("chat");
		//$this->addFilter("glo");
		//$this->addFilter("file");
		$this->addFilter("icrs");
		$this->addFilter("crs");
		//$this->addFilter('tst');
		$this->setFiltered(true);
		$this->setFilterMode(IL_FM_POSITIVE);
	}

	/**
	* note: most of this stuff is used by ilCourseContentInterface too
	*/
	function buildLinkTarget($a_node_id, $a_type)
	{
		global $ilCtrl;

		switch($a_type)
		{
			case "cat":
				return "repository.php?ref_id=".$a_node_id;

			case "lm":
			case "dbk":
				return "content/lm_presentation.php?ref_id=".$a_node_id;

			case "htlm":
				return "content/fblm_presentation.php?ref_id=".$a_node_id;

			case "sahs":
				return "content/sahs_presentation.php?ref_id=".$a_node_id;

			case "mep":
				return "";

			case "grp":
				return "repository.php?ref_id=".$a_node_id."&cmdClass=ilobjgroupgui";

			case "crs":
				return "repository.php?ref_id=".$a_node_id."&cmdClass=ilobjcoursegui";
				// seems not to work in some cases
				#$ilCtrl->setParameterByClass("ilObjCourseGUI","ref_id",$a_node_id);
				#$ilCtrl->setParameterByClass("ilObjCourseGUI","set_mode","flat");
				#return $ilCtrl->getLinkTargetByClass("ilObjCourseGUI");
				
			case "frm":
				#return "forums_threads_liste.php?ref_id=".$a_node_id."&backurl=repository";
				$ilCtrl->setParameterByClass('ilObjForumGUI','ref_id',$a_node_id);
				//$ilCtrl->setParameterByClass("ilObjForumGUI","set_mode","flat");
				
				return $ilCtrl->getLinkTargetByClass('ilObjForumGUI','showThreads');


			case "glo":
				return "content/glossary_presentation.php?ref_id=".$a_node_id;

			case "exc":
				return "exercise.php?cmd=view&ref_id=".$a_node_id;

			case "chat":
				return "chat/chat_rep.php?cmd=view&ref_id=".$a_node_id;

			case "fold":
				$ilCtrl->setParameterByClass("ilObjFolderGUI","ref_id",$a_node_id);
				//$ilCtrl->setParameterByClass("ilObjFolderGUI","set_mode","flat");
				return $ilCtrl->getLinkTargetByClass("ilObjFolderGUI");
				
			case "file":
				return "repository.php?cmd=sendfile&ref_id=".$a_node_id;

			case 'tst':
				return "assessment/test.php?cmd=run&ref_id=".$a_node_id;

			case 'svy':
				return "survey/survey.php?cmd=run&ref_id=".$a_node_id;

			case 'spl':
				return "survey/questionpool.php?cmd=questions&ref_id=".$a_node_id;

			case 'qpl':
				return "assessment/questionpool.php?cmd=questions&ref_id=".$a_node_id;

			case 'crsg':
				return "repository.php?ref_id=".$a_node_id;

			case 'webr':
				return "./link/link_resources.php?ref_id=".$a_node_id;
				
			case "icrs":
				return "repository.php?ref_id=".$a_node_id."&cmdClass=ilobjilinccoursegui";
		}
	}
	
	/**
	* note: this method is not used by repository explorer any more
	* but still by ilCourseContentInterface (should be redesigned)
	*/
	function buildEditLinkTarget($a_node_id, $a_type)
	{
		global $ilCtrl;

		switch($a_type)
		{
			case "cat":
				return "repository.php?cmd=edit&ref_id=".$a_node_id;

			case "lm":
			case "dbk":
				return "content/lm_edit.php?ref_id=".$a_node_id;

			case "htlm":
				return "content/fblm_edit.php?ref_id=".$a_node_id;

			case "sahs":
				return "content/sahs_edit.php?ref_id=".$a_node_id;

			case "mep":
				return "content/mep_edit.php?ref_id=".$a_node_id;

			case "grp":
				return; // following link is the same as "read" link
				return "repository.php?ref_id=".$a_node_id."&cmdClass=ilobjgroupgui";

			case "crs":
				return "repository.php?ref_id=".$a_node_id;
				#$ilCtrl->setParameterByClass("ilObjCourseGUI","ref_id",$a_node_id);
				#$ilCtrl->setParameterByClass("ilObjCourseGUI","set_mode","flat");
				#return $ilCtrl->getLinkTargetByClass("ilObjCourseGUI");
				
			case "frm":
				$ilCtrl->setParameterByClass('ilObjForumGUI','ref_id',$a_node_id);
				//$ilCtrl->setParameterByClass("ilObjForumGUI","set_mode","flat");
				
				return $ilCtrl->getLinkTargetByClass('ilObjForumGUI','edit');
				
				#return "repository.php?ref_id=".$a_node_id."set_mode=flat&cmdClass=ilobjforumgui";
				#return "forums_threads_liste.php?cmd=properties&ref_id=".$a_node_id."&backurl=repository";

			case "glo":
				return "content/glossary_edit.php?cmd=listTerms&ref_id=".$a_node_id;

			case "exc":
				return "exercise.php?cmd=view&ref_id=".$a_node_id;

			case "chat":
				return "chat/chat_rep.php?cmd=view&ref_id=".$a_node_id;

			case "fold":
				return "repository.php?cmd=edit&ref_id=".$a_node_id;
				
			case "file":
				return "repository.php?cmd=edit&cmdClass=ilobjfilegui&ref_id=".$a_node_id;

			case 'tst':
				return "assessment/test.php?ref_id=".$a_node_id;
				
			case 'svy':
				return "survey/survey.php?ref_id=".$a_node_id;
				
			case 'qpl':
				return "assessment/questionpool.php?ref_id=".$a_node_id
					."&cmd=questions";
					
			case 'spl':
				return "survey/questionpool.php?ref_id=".$a_node_id
					."&cmd=questions";

			case 'svy':
				return "survey/survey.php?ref_id=".$a_node_id;

			case 'crsg':
				return "repository.php?cmd=edit&ref_id=".$a_node_id;

			case 'webr':
				return "./link/link_resources.php?cmd=editItems&ref_id=".$a_node_id;
		}
	}		

	/**
	*
	* STATIC, do not use $this inside!
	*
	* Note: this is used by course interface !?
	*/
	function buildFrameTarget($a_type, $a_child = 0, $a_obj_id = 0)
	{
		global $ilias;
		
		
		
		switch($a_type)
		{
			case "cat":
				$t_frame = ilFrameTargetInfo::_getFrame("RepositoryContent", "cat");
				return $t_frame;

			case "lm":
			case "dbk":
			case "htlm":
			case "sahs":
				// Determine whether the view of a learning resource should
				// be shown in the frameset of ilias, or in a separate window.
				$showViewInFrameset = $ilias->ini->readVariable("layout","view_target") == "frame";

				if ($showViewInFrameset) 
				{
	  				return "bottom";
				}
				else
				{
					return "ilContObj".$a_obj_id;
				}

			case "grp":
				$t_frame = ilFrameTargetInfo::_getFrame("RepositoryContent", "grp");
				return $t_frame;

			case "crs":
				$t_frame = ilFrameTargetInfo::_getFrame("RepositoryContent", "crs");
				return $t_frame;

			case "frm":
				return "";

			case "glo":
				return "";

			case "tst":
				$showViewInFrameset = $ilias->ini->readVariable("layout","view_target") == "frame";
				if ($showViewInFrameset) 
				{
	  				return "bottom";
				}
				else
				{
					return "ilTest".$a_obj_id;
				}
				break;

			case "icrs":
				$t_frame = ilFrameTargetInfo::_getFrame("RepositoryContent", "icrs");
				return $t_frame;

			default:
				return "";
		}
	}
	
	/**
	* get image path
	*/
	function getImage($a_name, $a_type = "", $a_obj_id = "")
	{
		if ($a_type != "")
		{
			// custom icons
			if ($this->ilias->getSetting("custom_icons") &&
				in_array($a_type, array("cat","grp","crs")))
			{
				require_once("classes/class.ilContainer.php");
				if (($path = ilContainer::_lookupIconPath($a_obj_id, "small")) != "")
				{
					return $path;
				}
			}
		}
		
		return parent::getImage($a_name);
	}

	function isClickable($a_type, $a_ref_id,$a_obj_id = 0)
	{
		global $rbacsystem,$tree,$ilDB,$ilUser;

		if(!ilConditionHandler::_checkAllConditionsOfTarget($a_obj_id))
		{
			return false;
		}

		switch ($a_type)
		{
			case "crs":
				include_once './course/classes/class.ilObjCourse.php';

				// Has to be replaced by ilAccess calls
				if(!ilObjCourse::_isActivated($a_obj_id) and !$rbacsystem->checkAccess('write',$a_ref_id))
				{
					return false;
				}
				
				include_once './course/classes/class.ilCourseMembers.php';

				if(ilCourseMembers::_isBlocked($a_obj_id,$ilUser->getId()))
				{
					return false;
				}
				if(($rbacsystem->checkAccess('join',$a_ref_id) or
					$rbacsystem->checkAccess('read',$a_ref_id)))
				{
					return true;
				}
				return false;

			// visible groups can allways be clicked; group processing decides
			// what happens next
			case "grp":
				return true;
				break;

			case 'tst':
				if(!$rbacsystem->checkAccess("read", $a_ref_id))
				{
					return false;
				}

				$query = sprintf("SELECT * FROM tst_tests WHERE obj_fi=%s",$a_obj_id);
				$res = $ilDB->query($query);
				while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
				{
					return (bool) $row->complete;
				}
				return false;

			case 'svy':
				if(!$rbacsystem->checkAccess("read", $a_ref_id))
				{
					return false;
				}

				$query = sprintf("SELECT * FROM survey_survey WHERE obj_fi=%s",$a_obj_id);
				$res = $ilDB->query($query);
				while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
				{
					return (bool) $row->complete;
				}
				return false;

			// media pools can only be edited
			case "mep":
				if ($rbacsystem->checkAccess("read", $a_ref_id))
				{
					return true;
				}
				else
				{
					return false;
				}
				break;

			// all other types are only clickable, if read permission is given
			default:
				if ($rbacsystem->checkAccess("read", $a_ref_id))
				{
					// check if lm is online
					if ($a_type == "lm")
					{
						include_once("content/classes/class.ilObjLearningModule.php");
						$lm_obj =& new ilObjLearningModule($a_ref_id);
						if((!$lm_obj->getOnline()) && (!$rbacsystem->checkAccess('write',$a_ref_id)))
						{
							return false;
						}
					}
					// check if fblm is online
					if ($a_type == "htlm")
					{
						include_once("content/classes/class.ilObjFileBasedLM.php");
						$lm_obj =& new ilObjFileBasedLM($a_ref_id);
						if((!$lm_obj->getOnline()) && (!$rbacsystem->checkAccess('write',$a_ref_id)))
						{
							return false;
						}
					}
					// check if fblm is online
					if ($a_type == "sahs")
					{
						include_once("content/classes/class.ilObjSAHSLearningModule.php");
						$lm_obj =& new ilObjSAHSLearningModule($a_ref_id);
						if((!$lm_obj->getOnline()) && (!$rbacsystem->checkAccess('write',$a_ref_id)))
						{
							return false;
						}
					}
					// check if glossary is online
					if ($a_type == "glo")
					{
						$obj_id = ilObject::_lookupObjectId($a_ref_id);
						include_once("content/classes/class.ilObjGlossary.php");
						if((!ilObjGlossary::_lookupOnline($obj_id)) &&
							(!$rbacsystem->checkAccess('write',$a_ref_id)))
						{
							return false;
						}
					}

					return true;
				}
				else
				{
					return false;
				}
				break;
		}
	}

	function showChilds($a_ref_id,$a_obj_id = 0)
	{
		global $rbacsystem,$tree;
//vd($a_ref_id);

		if ($a_ref_id == 0)
		{
			return true;
		}
		if(!ilConditionHandler::_checkAllConditionsOfTarget($a_obj_id))
		{
			return false;
		}
		if ($rbacsystem->checkAccess("read", $a_ref_id))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	function isVisible($a_ref_id,$a_type)
	{
		global $rbacsystem,$tree;

		if(!$rbacsystem->checkAccess('visible',$a_ref_id))
		{
			return false;
		}
		if($crs_id = $tree->checkForParentType($a_ref_id,'crs'))
		{
			if(!$rbacsystem->checkAccess('write',$crs_id))
			{
				// Show only activated courses
				$tmp_obj =& ilObjectFactory::getInstanceByRefId($crs_id,false);

				if(!$tmp_obj->isActivated())
				{
					unset($tmp_obj);
					return false;
				}
				if(($crs_id != $a_ref_id) and $tmp_obj->isArchived())
				{
					return false;
				}
				// Show only activated course items
				include_once "./course/classes/class.ilCourseItems.php";

				if(($crs_id != $a_ref_id) and (!ilCourseItems::_isActivated($a_ref_id)))
				{
					return false;
				}
			}
		}
		return true;
	}



	/**
	* overwritten method from base class
	* @access	public
	* @param	integer obj_id
	* @param	integer array options
	* @return	string
	*/
	function formatHeader($a_obj_id,$a_option)
	{
		global $lng, $ilias;

		$tpl = new ilTemplate("tpl.tree.html", true, true);

		$tpl->setCurrentBlock("link");
		$tpl->setVariable("TITLE", $lng->txt("repository"));
		$tpl->setVariable("LINK_TARGET", "repository.php?ref_id=1");
		$tpl->setVariable("TARGET", " target=\"rep_content\"");
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("row");
		$tpl->parseCurrentBlock();

		$this->output[] = $tpl->get();
	}

} // END class ilRepositoryExplorer
?>
