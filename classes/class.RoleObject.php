<?php
/**
* Class RoleObject
*
* @author Stefan Meyer <smeyer@databay.de> 
* @version $Id$
* 
* @extends Object
* @package ilias-core
*/
class RoleObject extends Object
{
	/**
	* Constructor
	* @access	public
	*/
	function RoleObject($a_id = 0,$a_call_by_reference = "")
	{
		$this->Object($a_id,$a_call_by_reference);
		$this->type = "role";
	}


	/**
	* delete a role object
	* @access	public
	*/
	function deleteObject($a_obj_id, $a_parent, $a_tree_id = 1)
	{
		global $tree, $rbacadmin;
		
		if($rbacadmin->isAssignable($a_obj_id,$a_parent))
		{
			// IT'S THE BASE ROLE
			$rbacadmin->deleteRole($a_obj_id,$a_parent);
			
			//remove role entry in object_data
			deleteObject($a_rol_id);
			
			//TODO: delete references	
		}
		else
		{
			// INHERITANCE WAS STOPPED, SO DELETE ONLY THIS LOCAL ROLE
			$rbacadmin->deleteLocalRole($a_obj_id,$a_parent);

			//TODO: delete references	
		}

		return true;
	}


	/**
	* update a role object
	* @access	public
	* @param	array	object data of role
	* @return	boolean
	*/
	function update()
	{
		global $rbacsystem, $rbacadmin;

		// check if role title is unique
		if ($rbacadmin->roleExists($this->getTitle()))
		{
			$this->ilias->raiseError("A role with the name '".$this->getTitle().
				 "' already exists! <br />Please choose another name.",$this->ilias->error_obj->MESSAGE);
		}

		parent::update();
	}

	/**
	* show permission templates of role object
	* @access	public
	*/
	function permObject()
	{
		global $tree, $tpl, $rbacadmin, $rbacreview, $rbacsystem, $lng;

		if ($rbacsystem->checkAccess('edit permission',$_GET["ref_id"]))
		{
			$obj_data = getObjectList("typ","title","ASC");

			// BEGIN OBJECT_TYPES
			foreach ($obj_data as $data)
			{
				$output["obj_types"][] = $data["title"];
			}

			// END OBJECT TYPES
			$all_ops = getOperationList();

			// BEGIN TABLE_DATA_OUTER
			foreach ($all_ops as $key => $operations)
			{
				$operation_name = $operations["operation"];
				// BEGIN CHECK_PERM

				foreach ($obj_data as $data)
				{
					if (in_array($operations["ops_id"],$rbacadmin->getOperationsOnType($data["obj_id"])))
					{
						$selected = $rbacadmin->getRolePermission($this->id,$data["title"],$_GET["parent"]);

						$checked = in_array($operations["ops_id"],$selected);
						// Es wird eine 2-dim Post Variable �bergeben: perm[rol_id][ops_id]
						$box = TUtil::formCheckBox($checked,"template_perm[".$data["title"]."][]",$operations["ops_id"]);
						$output["perm"]["$operation_name"][] = $box;
					}
					else
					{
						$output["perm"]["$operation_name"][] = "";
					}
				}

				// END CHECK_PERM
				// color changing
				$css_row = TUtil::switchColor($key, "tblrow1", "tblrow2");
				$output["perm"]["$operation_name"]["color"] = $css_row;
			}

			// END TABLE DATA OUTER
			$box = TUtil::formCheckBox($checked,"recursive",1);
		
			$output["col_anz"] = count($obj_data);
			$output["check_bottom"] = $box;
			$output["message_table"] = "Change existing objects";

			// USER ASSIGNMENT
			if ($rbacadmin->isAssignable($this->id,$_GET["parent"]))
			{
				$users = getObjectList("usr","title","ASC");
				$assigned_users = $rbacreview->assignedUsers($this->id);

				foreach ($users as $key => $user)
				{
					$output["users"][$key]["css_row_user"] = $key % 2 ? "tblrow1" : "tblrow2";
					$checked = in_array($user["obj_id"],$assigned_users);
					$box = TUtil::formCheckBox($checked,"user[]",$user["obj_id"]);
					$output["users"][$key]["check_user"] = $box;
					$output["users"][$key]["username"] = $user["title"];
				}
				
				$output["message_bottom"] = "Assign User To Role";
				$output["formaction_assign"] = "adm_object.php?cmd=assignSave&obj_id=".
								  $this->id."&parent_parent=".$this->parent_parent."&parent=".$this->parent;
			}

			// ADOPT PERMISSIONS
			$output["message_middle"] = "Adopt Permissions from Role Template";
			// BEGIN ADOPT_PERMISSIONS
			$parent_role_ids = $rbacadmin->getParentRoleIds($_GET["ref_id"],true);

			// sort output for correct color changing
			ksort($parent_role_ids);

			foreach ($parent_role_ids as $key => $par)
			{
				$radio = TUtil::formRadioButton(0,"adopt",$par["obj_id"]);
				$output["adopt"][$key]["css_row_adopt"] = TUtil::switchColor($key, "tblrow1", "tblrow2");
				$output["adopt"][$key]["check_adopt"] = $radio;
				$output["adopt"][$key]["type"] = ($par["type"] == 'role' ? 'Role' : 'Template');
				$output["adopt"][$key]["role_name"] = $par["title"];
			}
			$output["formaction_adopt"] = "adm_object.php?cmd=adoptPermSave&obj_id="
				.$this->id."&parent_parent=".$this->parent_parent."&parent=".$this->parent;

			// END ADOPT_PERMISSIONS
			$output["formaction"] = "adm_object.php?cmd=permSave&obj_id=".
				$this->id."&parent_parent=".$this->parent_parent."&parent=".$this->parent;
			$role_data = getObject($this->id);
			$output["message_top"] = "Permission Template of Role: ".$role_data["title"];
		}
		else
		{
			$this->ilias->raiseError("No permission to write to role folder",$this->ilias->error_obj->WARNING);
		}

		return $output;
	}
	/**
	* save permission templates of a role object
	* @access	public
	*/
	function permSaveObject($a_perm, $a_stop_inherit, $a_type, $a_template_perm, $a_recursive)
	{
		global $tree, $rbacsystem, $rbacadmin;

		// SET TEMPLATE PERMISSIONS
		if ($rbacsystem->checkAccess('edit permission',$_GET["parent"],$_GET["parent_parent"]))
		{

			// delete all template entries
			$rbacadmin->deleteRolePermission($this->id,$_GET["parent"]);

			if (empty($a_template_perm))
			{
				$a_template_perm = array();
			}
			
			foreach ($a_template_perm as $key => $ops_array)
			{
				// sets new template permissions
				$rbacadmin->setRolePermission($this->id, $key,$ops_array, $_GET["parent"]);
			}

			// CHANGE ALL EXISTING OBJECT UNDER PARENT NODE OF ROLE FOLDER
			// BUT DON'T CHANGE PERMISSIONS OF SUBTREE OBJECTS IF INHERITANCE WAS STOPED
			if ($a_recursive)
			{
				$parent_obj = $_GET["parent_parent"];
				// IF PARENT NODE IS SYTEM FOLDER START AT ROOT FOLDER
				if ($parent_obj == SYSTEM_FOLDER_ID)
				{
					$object_id = ROOT_FOLDER_ID;
					$parent = 0;
				}
				else
				{
					$node_data = $tree->getParentNodeData($_GET["parent"]);
					$object_id = $node_data["obj_id"];
					$parent = $node_data["parent"];
				}
				// GET ALL SUBNODES
				$node_data = $tree->getNodeData($object_id);
				$subtree_nodes = $tree->getSubTree($node_data);

				// GET ALL OBJECTS THAT CONTAIN A ROLE FOLDERS
				$all_rolf_obj = $rbacadmin->getObjectsWithStopedInheritance($this->id);

				// DELETE ACTUAL ROLE FOLDER FROM ARRAY
				$key = array_keys($all_rolf_obj,$object_id);
				unset($all_rolf_obj["$key[0]"]);

				$check = false;
				foreach($subtree_nodes as $node)
				{
					if(!$check)
					{
						if(in_array($node["obj_id"],$all_rolf_obj))
						{
							$lft = $node["lft"];
							$rgt = $node["rgt"];
							$check = true;
							continue;
						}
						$valid_nodes[] = $node;
					}
					else
					{
						if(($node["lft"] > $lft) && ($node["rgt"] < $rgt))
						{
							continue;
						}
						else
						{
							$check = false;
							$valid_nodes[] = $node;
						}
					}
				}
				// NOW SET ALL PERMISSIONS
				foreach($a_template_perm as $type => $a_perm)
				{
					foreach($valid_nodes as $node)
					{
						if($type == $node["type"])
						{
							$rbacadmin->revokePermission($node["obj_id"],$this->id);
							$rbacadmin->grantPermission($this->id,$a_perm,$node["obj_id"]);
						}
					}
				}
			}// END IF RECURSIVE
		}// END CHECK ACCESS
		else
		{
			$this->ilias->raiseError("No permission to edit permissions",$this->ilias->error_obj->WARNING);
		}
		return true;
	}
	
	/**
	* copy permissions from role
	* @access	public
	*/
	function adoptPermSaveObject()
	{
		global $rbacadmin, $rbacsystem;
		
		// TODO: get rid of $_GET variables

		if ($rbacsystem->checkAccess('edit permission',$_GET["parent"],$_GET["parent_parent"]))
		{
			$rbacadmin->deleteRolePermission($_GET["obj_id"], $_GET["parent"]);
			$parentRoles = $rbacadmin->getParentRoleIds($_GET["parent"],$_GET["parent_parent"],true);
			$rbacadmin->copyRolePermission($_POST["adopt"],$parentRoles[$_POST["adopt"]]["parent"],
										   $_GET["parent"],$_GET["obj_id"]);
		}
		else
		{
			$this->ilias->raiseError("No Permission to edit permissions",$this->ilias->error_obj->WARNING);
		}
		return true;
	}

	/**
	* assign user to role
	* @access	public
	*/
	function assignSaveObject()
	{
		global $tree, $rbacsystem, $rbacadmin, $rbacreview;
		
		// TODO: get rid of $_GET variables
		 
		if ($rbacadmin->isAssignable($_GET["obj_id"],$_GET["parent"]))
		{
			if ($rbacsystem->checkAccess('edit permission',$_GET["parent"],$_GET["parent_parent"]))
			{
				$assigned_users = $rbacreview->assignedUsers($_GET["obj_id"]);
				$_POST["user"] = $_POST["user"] ? $_POST["user"] : array();

				foreach (array_diff($assigned_users,$_POST["user"]) as $user)
				{
					$rbacadmin->deassignUser($_GET["obj_id"],$user);
				}

				foreach (array_diff($_POST["user"],$assigned_users) as $user)
				{
					$rbacadmin->assignUser($_GET["obj_id"],$user,false);
				}
			}
			else
			{
				$this->ilias->raiseError("No permission to edit permissions",$this->ilias->error_obj->WARNING);
			}

			return true;
		}
		else
		{
			$this->ilias->raiseError("It's worth a try. ;-)",$this->ilias->error_obj->WARNING);
		}
	}
} // END class.RoleObject
?>
