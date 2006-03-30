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

/**
* @author Stefan Meyer <smeyer@databay.de>
*
* @version $Id$
*
* @package ilias-tracking
*
*/

include_once './Services/Tracking/classes/class.ilLPStatus.php';

class ilLPStatusTypicalLearningTime extends ilLPStatus
{

	function ilLPStatusTypicalLearningTime($a_obj_id)
	{
		global $ilDB;

		parent::ilLPStatus($a_obj_id);
		$this->db =& $ilDB;
	}

	function _getInProgress($a_obj_id)
	{
		global $ilDB;

		include_once './Services/MetaData/classes/class.ilMDEducational.php';

		$tlt = ilMDEducational::_getTypicalLearningTimeSeconds($a_obj_id);

		$query = "SELECT DISTINCT(user_id) FROM ut_learning_progress ".
			"WHERE spent_time < '".$tlt."' ".
			"AND obj_id = '".$a_obj_id."'";

		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$user_ids[] = $row->user_id;
		}
		return $user_ids ? $user_ids : array();
	}

	function _getCompleted($a_obj_id)
	{
		global $ilDB;

		include_once './Services/MetaData/classes/class.ilMDEducational.php';

		$tlt = ilMDEducational::_getTypicalLearningTimeSeconds($a_obj_id);
		$query = "SELECT DISTINCT(user_id) FROM ut_learning_progress ".
			"WHERE spent_time >= '".$tlt."' ".
			"AND obj_id = '".$a_obj_id."'";

		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$user_ids[] = $row->user_id;
		}
		return $user_ids ? $user_ids : array();
	}		

}	
?>