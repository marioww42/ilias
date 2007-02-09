<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
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

require_once "classes/class.ilObjUser.php";
require_once "classes/class.ilMailbox.php";
require_once "classes/class.ilFormatMail.php";
require_once "classes/class.ilAddressbook.php";

/**
* @author Jens Conze
* @version $Id$
*
* @ingroup ServicesMail
*/
class ilMailSearchCoursesGUI
{
	private $tpl = null;
	private $ctrl = null;
	private $lng = null;
	
	private $umail = null;
	private $abook = null;

	public function __construct()
	{
		global $tpl, $ilCtrl, $lng, $ilUser;

		$this->tpl = $tpl;
		$this->ctrl = $ilCtrl;
		$this->lng = $lng;
		
		$this->ctrl->saveParameter($this, "mobj_id");

		$this->umail = new ilFormatMail($ilUser->getId());
		$this->abook = new ilAddressbook($ilUser->getId());
	}

	public function executeCommand()
	{
		$forward_class = $this->ctrl->getNextClass($this);
		switch($forward_class)
		{
			default:
				if (!($cmd = $this->ctrl->getCmd()))
				{
					$cmd = "showMyCourses";
				}

				$this->$cmd();
				break;
		}
		return true;
	}

	function mail()
	{
		global $ilUser, $lng;

		if ($_GET["view"] == "mycourses")
		{
			if (is_array($_POST["search_crs"]))
			{
				$this->mailCourses();
			}
			else
			{
				ilUtil::sendInfo($lng->txt("mail_select_course"));
				$this->showMyCourses();
			}
		}
		else if ($_GET["view"] == "crs_members")
		{
			if (is_array($_POST["search_members"]))
			{
				$this->mailMembers();
			}
			else
			{
				ilUtil::sendInfo($lng->txt("mail_select_one_entry"));
				$this->showCourseMembers();
			}
		}
		else
		{
			$this->showMyCourses();
		}
	}

	function mailCourses()
	{
		global $ilUser, $lng;

		$members = array();

		if (!is_array($this->umail->getSavedData()))
		{
			$this->umail->savePostData(
				$ilUser->getId(),
				array(),
				"",
				"",
				"",
				"",
				"",
				"",
				""
			);
		}
		
		foreach ($_POST["search_crs"] as $crs)
		{
			array_push($members, "#il_crs_members_".$crs);
		}
		$mail_data = $this->umail->appendSearchResult($members,"to");

		$this->umail->savePostData(
			$mail_data["user_id"],
			$mail_data["attachments"],
			$mail_data["rcp_to"],
			$mail_data["rcp_cc"],
			$mail_data["rcp_bcc"],
			$mail_data["m_type"],
			$mail_data["m_email"],
			$mail_data["m_subject"],
			$mail_data["m_message"]
		);
		
		ilUtil::redirect("ilias.php?baseClass=ilMailGUI&type=search_res");
	}

	function mailMembers()
	{
		$members = array();

		if (!is_array($this->umail->getSavedData()))
		{
			$this->umail->savePostData(
				$ilUser->getId(),
				array(),
				"",
				"",
				"",
				"",
				"",
				"",
				""
			);
		}
	
		foreach ($_POST["search_members"] as $member)
		{
			$login = ilObjUser::_lookupLogin($member);
			array_push($members, $login);
		}
		$mail_data = $this->umail->appendSearchResult($members,"to");

		$this->umail->savePostData(
			$mail_data["user_id"],
			$mail_data["attachments"],
			$mail_data["rcp_to"],
			$mail_data["rcp_cc"],
			$mail_data["rcp_bcc"],
			$mail_data["m_type"],
			$mail_data["m_email"],
			$mail_data["m_subject"],
			$mail_data["m_message"]
		);
	
		ilUtil::redirect("ilias.php?baseClass=ilMailGUI&type=search_res");
	}

	/**
	 * Take over course members to addressbook
	 */
	public function adoptMembers()
	{
		global $lng;

		if (is_array($_POST["search_members"]))
		{
			$members = array();
		
			foreach ($_POST["search_members"] as $member)
			{
				$login = ilObjUser::_lookupLogin($member);
	
				if (!$this->abook->checkEntry($login))
				{
					$name = ilObjUser::_lookupName($member);
					$email = ilObjUser::_lookupEmail($member);
					$this->abook->addEntry(
						$login,
						$name["firstname"],
						$name["lastname"],
						$email
					);
				}
			}
			ilUtil::sendInfo($lng->txt("mail_members_added_addressbook"));
		}
		else
		{
			ilUtil::sendInfo($lng->txt("mail_select_one_entry"));
		}

		$this->showMembers();
	}
	
	/**
	 * Cancel action
	 */
	function cancel()
	{
		if ($_GET["view"] == "mycourses" &&
			$_GET["ref"] == "mail")
		{
			$this->ctrl->returnToParent($this);	
		}
		else
		{
			$this->showMyCourses();
		}
	}
	
	/**
	 * Show user's courses
	 */
	public function showMyCourses()
	{
		global $lng, $ilUser, $ilObjDataCache;

		include_once 'Modules/Course/classes/class.ilCourseMembers.php';

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.mail_addressbook_search.html", "Services/Mail");
		$this->tpl->setVariable("HEADER", $this->lng->txt("mail"));
		
		$_GET["view"] = "mycourses";

		$this->ctrl->setParameter($this, "cmd", "post");
		$this->ctrl->setParameter($this, "view", "mycourses");
		if ($_GET["ref"] != "") $this->ctrl->setParameter($this, "ref", $_GET["ref"]);
		if (is_array($_POST["search_crs"])) $this->ctrl->setParameter($this, "search_crs", implode(",", $_POST["search_crs"]));
		$this->tpl->setVariable("ACTION", $this->ctrl->getLinkTarget($this));
		$this->ctrl->clearParameters($this);

		$lng->loadLanguageModule('crs');
	
		$user = new ilObjUser($ilUser->getId());
		$crs_ids = $user->getCourseMemberships();
	
		$counter = 0;
		if (is_array($crs_ids) &&
					count($crs_ids) > 0)
		{
				
			$this->tpl->setVariable("CRS_TXT_COURSES",$lng->txt("mail_my_courses"));
			$this->tpl->setVariable("CRS_TXT_NO_MEMBERS",$lng->txt("crs_count_members"));
		
			foreach($crs_ids as $crs_id) 
			{
				$crs_members = ilCourseMembers::_getMembers($crs_id);
	
				$this->tpl->setCurrentBlock("loop_crs");
				$this->tpl->setVariable("LOOP_CRS_CSSROW",++$counter%2 ? 'tblrow1' : 'tblrow2');
				$this->tpl->setVariable("LOOP_CRS_ID",$crs_id);
				$this->tpl->setVariable("LOOP_CRS_NAME",$ilObjDataCache->lookupTitle($crs_id));
				$this->tpl->setVariable("LOOP_CRS_NO_MEMBERS",count($crs_members));
				$this->tpl->parseCurrentBlock();
			}
	
			$this->tpl->setVariable("BUTTON_MAIL",$lng->txt("mail_members"));
			$this->tpl->setVariable("BUTTON_LIST",$lng->txt("mail_list_members"));
		}
	
		if ($counter == 0)
		{
			$this->tpl->setCurrentBlock("crs_not_found");
			$this->tpl->setVariable("TXT_CRS_NOT_FOUND",$lng->txt("mail_search_courses_not_found"));
			$this->tpl->parseCurrentBlock();
		}

		if ($_GET["ref"] == "mail") $this->tpl->setVariable("BUTTON_CANCEL",$lng->txt("cancel"));

		$this->tpl->setVariable("TXT_MARKED_ENTRIES",$lng->txt("marked_entries"));
		$this->tpl->show();
	}

	/**
	 * Show course members
	 */
	public function showMembers()
	{
		global $lng, $ilUser, $ilObjDataCache;

		include_once 'Modules/Course/classes/class.ilCourseMembers.php';

		if ($_GET["search_crs"] != "")
		{
			$_POST["search_crs"] = explode(",", $_GET["search_crs"]);
			$_GET["search_crs"] = "";
		}
		else if ($_SESSION["search_crs"] != "")
		{
			$_POST["search_crs"] = explode(",", $_SESSION["search_crs"]);
			$_SESSION["search_crs"] = "";
		} 

		if (!is_array($_POST["search_crs"]) ||
			count($_POST["search_crs"]) == 0)
		{
			ilUtil::sendInfo($lng->txt("mail_select_course"));
			$this->showMyCourses();
		}
		else
		{
			$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.mail_addressbook_search.html", "Services/Mail");
			$this->tpl->setVariable("HEADER", $this->lng->txt("mail"));
		
			$this->ctrl->setParameter($this, "cmd", "post");
			$this->ctrl->setParameter($this, "view", "crs_members");
			if ($_GET["ref"] != "") $this->ctrl->setParameter($this, "ref", $_GET["ref"]);
			if (is_array($_POST["search_crs"])) $this->ctrl->setParameter($this, "search_crs", implode(",", $_POST["search_crs"]));
			$this->tpl->setVariable("ACTION", $this->ctrl->getLinkTarget($this));
			$this->ctrl->clearParameters($this);

			$lng->loadLanguageModule('crs');
	
			$this->tpl->setCurrentBlock("members_course");
			$this->tpl->setVariable("MEMBERS_TXT_COURSE",$lng->txt("course"));
			$this->tpl->parseCurrentBlock();
			$this->tpl->setVariable("MEMBERS_TXT_LOGIN",$lng->txt("login"));
			$this->tpl->setVariable("MEMBERS_TXT_NAME",$lng->txt("name"));
			$this->tpl->setVariable("MEMBERS_TXT_IN_ADDRESSBOOK",$lng->txt("mail_in_addressbook"));
	
			$counter = 0;
			foreach($_POST["search_crs"] as $crs_id) 
			{
				$tmp_members = ilCourseMembers::_getMembers($crs_id);
				$course_members[$crs_id] = ilUtil::_sortIds($tmp_members,'usr_data','lastname','usr_id');
	
				foreach ($course_members[$crs_id] as $member)
				{
					$name = ilObjUser::_lookupName($member);
					$login = ilObjUser::_lookupLogin($member);
	
					$this->tpl->setCurrentBlock("loop_members");
					$this->tpl->setVariable("LOOP_MEMBERS_CSSROW",++$counter%2 ? 'tblrow1' : 'tblrow2');
					$this->tpl->setVariable("LOOP_MEMBERS_ID",$member);
					$this->tpl->setVariable("LOOP_MEMBERS_LOGIN",$login);
					$this->tpl->setVariable("LOOP_MEMBERS_NAME",$name["lastname"].", ".$name["firstname"]);
					$this->tpl->setVariable("LOOP_MEMBERS_CRS_GRP",$ilObjDataCache->lookupTitle($crs_id));
					$this->tpl->setVariable("LOOP_MEMBERS_IN_ADDRESSBOOK", $this->abook->checkEntry($login) ? $lng->txt("yes") : $lng->txt("no"));
					$this->tpl->parseCurrentBlock();
				}
			}
			
			if ($counter == 0)
			{
				$this->tpl->setCurrentBlock("members_not_found");
				$this->tpl->setVariable("TXT_MEMBERS_NOT_FOUND",$lng->txt("mail_search_members_not_found"));
				$this->tpl->parseCurrentBlock();
			}
			else
			{
				$this->tpl->setVariable("BUTTON_MAIL",$lng->txt("grp_mem_send_mail"));
				$this->tpl->setVariable("BUTTON_ADOPT",$lng->txt("mail_into_addressbook"));
			}

			$this->tpl->setVariable("BUTTON_CANCEL",$lng->txt("cancel"));

			$this->tpl->setVariable("TXT_MARKED_ENTRIES",$lng->txt("marked_entries"));
			$this->tpl->show();
		}
	}

}

?>
