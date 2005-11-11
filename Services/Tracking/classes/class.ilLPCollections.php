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
* Class ilLPObjSettings
*
* @author Stefan Meyer <smeyer@databay.de>
*
* @version $Id$
*
* @package ilias-tracking
*
*/

class ilLPCollections
{
	var $db = null;

	var $obj_id = null;
	var $items = array();

	function ilLPCollections($a_obj_id)
	{
		global $ilObjDataCache,$ilDB;

		$this->db =& $ilDB;

		$this->obj_id = $a_obj_id;

		$this->__read();
	}

	function getObjId()
	{
		return (int) $this->obj_id;
	}

	function getItems()
	{
		return $this->items;
	}

	function add($item_id)
	{
		$query = "INSERT INTO ut_lp_collections ".
			"SET obj_id = '".$this->obj_id."', ".
			"item_id = '".(int) $item_id."'";
		$this->db->query($query);
		
		return true;
	}

	function delete($item_id)
	{
		$query = "DELETE FROM ut_lp_collections ".
			"WHERE item_id = '".$item_id."' ".
			"AND obj_id = '".$this->obj_id."'";
		$this->db->query($query);

		return true;
	}


	// Static
	function _deleteAll($a_obj_id)
	{
		global $ilDB;

		$query = "DELETE FROM ut_lp_collections ".
			"WHERE obj_id = '".$a_obj_id."'";
		$ilDB->query($query);

		return true;
	}

	function _getItems($a_obj_id)
	{
		global $ilDB;

		$query = "SELECT * FROM ut_lp_collections WHERE obj_id = '".(int) $a_obj_id."'";
		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$items[] = $row->item_id;
		}
		return $items ? $items : array();
	}

	// Private
	function __read()
	{
		$res = $this->db->query("SELECT * FROM ut_lp_collections WHERE obj_id = '".$this->db->quote($this->obj_id)."'");
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$items[] = $row->item_id;
		}

		return true;
	}
}
?>