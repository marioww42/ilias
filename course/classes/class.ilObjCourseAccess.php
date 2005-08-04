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

include_once("classes/class.ilObjectAccess.php");

/**
* Class ilObjCourseAccess
*
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @package AccessControl
*/
class ilObjCourseAccess extends ilObjectAccess
{
	/**
	* checks wether a user may invoke a command or not
	* (this method is called by ilAccessHandler::checkAccess)
	*
	* @param	string		$a_cmd		command (not permission!)
	* @param	string		$a_permission	permission
	* @param	int			$a_ref_id	reference id
	* @param	int			$a_obj_id	object id
	* @param	int			$a_user_id	user id (if not provided, current user is taken)
	*
	* @return	boolean		true, if everything is ok
	*/
	function _checkAccess($a_cmd, $a_permission, $a_ref_id, $a_obj_id, $a_user_id = "")
	{
		global $ilUser, $lng, $rbacsystem, $ilAccess, $ilias;

		if ($a_user_id == "")
		{
			$a_user_id = $ilUser->getId();
		}

		switch ($a_cmd)
		{
			case "view":
				include_once 'course/classes/class.ilCourseMembers.php';

				if(ilCourseMembers::_isBlocked($a_obj_id,$a_user_id))
				{
					$ilAccess->addInfoItem(IL_NO_OBJECT_ACCESS, $lng->txt("crs_status_blocked"));
					return false;
				}					
				break;

			case "info":
				include_once 'course/classes/class.ilCourseMembers.php';

				if(ilCourseMembers::_isMember($a_user_id,$a_obj_id))
				{
					$ilAccess->addInfoItem(IL_STATUS_MESSAGE, $lng->txt("info_is_member"));
				}
				else
				{
					$ilAccess->addInfoItem(IL_STATUS_MESSAGE, $lng->txt("info_is_not_member"));
				}			
				break;

			case 'join':
				include_once 'course/classes/class.ilCourseMembers.php';

				if(ilCourseMembers::_isMember($a_user_id,$a_obj_id))
				{
					return false;
				}
				break;

			case 'details':
				include_once 'course/classes/class.ilCourseMembers.php';

				// No details button if user is member
				if(ilCourseMembers::_isMember($a_user_id,$a_obj_id))
				{
					return false;
				}
				// No details button if user has join permission
				if($rbacsystem->checkAccessOfUser($a_user_id,'join',$a_ref_id))
				{
					return false;
				}
				break;
		}

		switch ($a_permission)
		{
			case "visible":
				include_once 'course/classes/class.ilObjCourse.php';
				if(!($activated = ilObjCourse::_isActivated($a_obj_id)))
				{
					$ilAccess->addInfoItem(IL_NO_OBJECT_ACCESS, $lng->txt("offline"));
				}
				else
				{
					$ilAccess->addInfoItem(IL_STATUS_MESSAGE, $lng->txt("online"));
				}
				if(!$rbacsystem->checkAccessOfUser($a_user_id,'write',$a_ref_id) and !$activated)
				{
					return false;
				}
				
				break;
		}
		return true;
	}

	/**
	 * get commands
	 * 
	 * this method returns an array of all possible commands/permission combinations
	 * 
	 * example:	
	 * $commands = array
	 *	(
	 *		array("permission" => "read", "cmd" => "view", "lang_var" => "show"),
	 *		array("permission" => "write", "cmd" => "edit", "lang_var" => "edit"),
	 *	);
	 */
	function _getCommands()
	{
		$commands = array
		(
			array("permission" => "read", "cmd" => "view", "lang_var" => "view",
				  "default" => true),
			array("permission" => "join", "cmd" => "join", "lang_var" => "join"),
			array("permission" => "write", "cmd" => "edit", "lang_var" => "edit"),
			array('permission' => 'visible', 'cmd' => 'details','lang_var' => 'crs_info')
		);
		
		return $commands;
	}
}

?>
