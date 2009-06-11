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


/**
* Class ilSCORM2004Utilities
*
* Sequencing Utilities class for SCORM 2004 Editing
*
* @author Hendrik Holtmann <holtmann@me.com>
* @version $Id$
*
* @ingroup ModulesScorm2004
*/

class ilSCORM2004Utilities 
{
	
	
	private $id = null;
	
	/*
	* Constructor
	* @access	public
	*/
	function ilSCORM2004Utilities($a_id)
	{
		$this->id = $a_id;
	}
	
	public function parentHasSeqTemplate($a_slm_id)
	{
		require_once("./Modules/Scorm2004/classes/seq_editor/class.ilSCORM2004Template.php");
		
		global $ilDB,$ilLog;
		$has_template = false;
			
		$mtree = new ilTree($a_slm_id);
		$mtree->setTableNames('sahs_sc13_tree','sahs_sc13_tree_node');
		$mtree->setTreeTablePK("slm_id");
		//get all parents for current node
		$parents = $this -> getParentsForNode($a_parents=array(),$this->id);
		for ($i=0;$i<count($parents);$i++)
		{
			$template = ilSCORM2004SeqTemplate::templateForChapter($parents[$i]);
			if ($template) {
				$has_template = true;
				break;
			}
		}
		
		return $has_template;
	}
	
	private function getParentsForNode($a_parents,$a_id){
		$parent_id = $tree->getParentId($id);
		if ($parent_id != 0) {
			array_push($a_parents,$parent_id);
			$this->getParentsForNode($a_parents,$parent_id);
		} else {
			return $a_parents;
		}
	}
	
	
	public function getLeftRightInfo() 
	{
		global $ilDB,$ilLog;
		$ilLog->write("SCORM: getLeftRightInfo");
		$query = "SELECT * FROM sahs_sc13_seq_tree WHERE (child = ".$ilDB->quote($this->getSeqNodeId()).
														  " AND importid=".$ilDB->quote($this->getImportIdent()).")";
		$obj_set = $ilDB->query($query);
		$ilLog->write("SCORM: getLeftRightInfo executed".$query);
		$obj_rec = $obj_set->fetchRow(DB_FETCHMODE_ASSOC);
		return array("left"=> $obj_rec["lft"], "right" => $obj_rec["rgt"]);
	}
	
	
	protected function getSeqNodeId() 
	{
		global $ilDB,$ilLog;
		$all_props = $this->getAllSequencingProperties();
		$ilLog->write("SCORM: getSeqNodeId: ".$all_props["seqNodeId"]);
		return $all_props["seqNodeId"];
	}
	
	private function getSequencingId() 
	{
		global $ilDB,$ilLog;
		$ilLog->write("SCORM: getSequencingId for".$this->getId());
		$query = "SELECT * FROM sahs_sc13_seq_item WHERE sahs_sc13_tree_node_id = ".$ilDB->quote($this->getId());
		$obj_set = $ilDB->query($query);
		$obj_rec = $obj_set->fetchRow(DB_FETCHMODE_ASSOC);
		return $obj_rec['sequencingId'];
	}
	
	private function getItemId() 
	{
		global $ilDB,$ilLog;
		$ilLog->write("SCORM: getSequencingId for".$this->getId);
		$query = "SELECT * FROM sahs_sc13_seq_item WHERE sahs_sc13_tree_node_id = ".$ilDB->quote($this->getId());
		$obj_set = $ilDB->query($query);
		$obj_rec = $obj_set->fetchRow(DB_FETCHMODE_ASSOC);
		return $obj_rec['seqNodeId'];
	}
	
	public function getImportIdent() 
	{
		global $ilDB,$ilLog;
		$ilLog->write("SCORM: getImportIdent for".$this->getId);
		$query = "SELECT * FROM sahs_sc13_seq_item WHERE sahs_sc13_tree_node_id = ".$ilDB->quote($this->getId());
		$obj_set = $ilDB->query($query);
		$obj_rec = $obj_set->fetchRow(DB_FETCHMODE_ASSOC);
		return $obj_rec['importId'];
	}
	
	
	
	private function getId()
	{
		return $this->id;
	}
	
	public function getAllowedActions()
	{
		global $ilDB,$ilLog;
		$ilLog->write("SCORM: getAllowedActions for".$this->tree_node_id);
		$query = "SELECT * FROM sahs_sc13_seq_item WHERE sahs_sc13_tree_node_id = ".$ilDB->quote($this->getId());
		$obj_set = $ilDB->query($query);
		$obj_rec = $obj_set->fetchRow(DB_FETCHMODE_ASSOC);
		return array("copy"=>!$obj_rec['nocopy'],"move"=>!$obj_rec['nomove'],"delete"=>!$obj_rec['nodelete']);
	}
	
	public function getControlModeProperties()
	{
		global $ilDB;
		$query = "SELECT * FROM sahs_sc13_seq_sequencing WHERE id = ".$ilDB->quote($this->getSequencingId());
		$obj_set = $ilDB->query($query);
		$obj_rec = $obj_set->fetchRow(DB_FETCHMODE_ASSOC);
		$c_properties = array('flow' => $obj_rec['flow'],'forwardOnly' => $obj_rec['forwardOnly'], 'choice' => $obj_rec['choice'],'choiceExit' => $obj_rec['choiceExit'] );
		return $c_properties;
	}
	
	public function getAllSequencingProperties()
	{
		global $ilDB,$ilLog;
		$query = "SELECT * FROM sahs_sc13_seq_sequencing WHERE (id = ".$ilDB->quote($this->getSequencingId()).
																" AND importId=".$ilDB->quote($this->getImportIdent()).")";
		$obj_set = $ilDB->query($query);
		$ilLog->write("SCORM: getAllSequencingProperties for".$this->getSequencingId());
		$obj_rec = $obj_set->fetchRow(DB_FETCHMODE_ASSOC);
		return $obj_rec;
	}
	

	
}
?>
