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
   * Soap object administration methods
   *
   * @author Stefan Meyer <smeyer@databay.de>
   * @version $Id$
   *
   * @package ilias
   */
include_once './webservice/soap/classes/class.ilSoapAdministration.php';

class ilSoapObjectAdministration extends ilSoapAdministration
{
	function ilSoapObjectAdministration()
	{
		parent::ilSoapAdministration();
	}

	function getObjIdByImportId($sid,$import_id)
	{
		if(!$this->__checkSession($sid))
		{
			return $this->__raiseError($this->sauth->getMessage(),$this->sauth->getMessageCode());
		}
		if(!$import_id)
		{
			return $this->__raiseError('No import id given.',
									   'Client');
		}

		// Include main header
		include_once './include/inc.header.php';
		global $ilLog;

		$obj_id = ilObject::_lookupObjIdByImportId($import_id);
		$ilLog->write("SOAP getObjIdByImportId(): import_id = ".$import_id.' obj_id = '.$obj_id);

		return $obj_id ? $obj_id : "0";
	}

	function getRefIdsByImportId($sid,$import_id)
	{
		global $tree;

		if(!$this->__checkSession($sid))
		{
			return $this->__raiseError($this->sauth->getMessage(),$this->sauth->getMessageCode());
		}
		if(!$import_id)
		{
			return $this->__raiseError('No import id given.',
									   'Client');
		}

		// Include main header
		include_once './include/inc.header.php';
		global $tree;

		$obj_id = ilObject::_lookupObjIdByImportId($import_id);


		$ref_ids = ilObject::_getAllReferences($obj_id);

		foreach($ref_ids as $ref_id)
		{
			// only get non deleted reference ids
			if ($tree->isInTree($ref_id))
			{
				$new_refs[] = $ref_id;
			}
		}
		return $new_refs ? $new_refs : array();
	}

	function getRefIdsByObjId($sid,$obj_id)
	{
		if(!$this->__checkSession($sid))
		{
			return $this->__raiseError($this->sauth->getMessage(),$this->sauth->getMessageCode());
		}
		if(!$obj_id)
		{
			return $this->__raiseError('No object id given.',
									   'Client');
		}

		// Include main header
		include_once './include/inc.header.php';

		$ref_ids = ilObject::_getAllReferences($obj_id);
		foreach($ref_ids as $ref_id)
		{
			$new_refs[] = $ref_id;
		}
		return $new_refs ? $new_refs : array();
	}

	/**
	*	Returns a array of object ids which match the references id, given by a comma seperated string.
	*
	*	@param	string $sid	Session ID
	*	@param	array of int $ref ids as comma separated list
	*	@return	array of ref ids, same order as object ids there for there might by duplicates
	*
	*/
	function getObjIdsByRefIds($sid, $ref_ids)
	{
		if(!$this->__checkSession($sid))
		{
			return $this->__raiseError($this->sauth->getMessage(),$this->sauth->getMessageCode());
		}


		// Include main header
		include_once './include/inc.header.php';

		if(!count($ref_ids) || !is_array ($ref_ids))
		{
			return $this->__raiseError('No reference id(s) given.', 'Client');
		}

		$obj_ids = array();
		if (count($ref_ids)) {
			foreach ($ref_ids as $ref_id)
			{
				$ref_id = trim($ref_id);
				if (!is_numeric($ref_id)){
					return $this->__raiseError('Reference ID has to be numeric. Value: '.$ref_id, 'Client');
				}

				$obj_id = ilObject::_lookupObjectId($ref_id);
				if (!$obj_id){
					return $this->__raiseError('No object found for reference ID. Value: '.$ref_id, 'Client');
				}
				if (!ilObject::_hasUntrashedReference($obj_id)){
					return $this->__raiseError('No untrashed reference found for reference ID. Value: '.$ref_id, 'Client');
				}
				$obj_ids[] = $obj_id;
			}
		}
		return $obj_ids;
	}



	function getObjectByReference($sid,$a_ref_id,$user_id)
	{
		if(!$this->__checkSession($sid))
		{
			return $this->__raiseError($this->sauth->getMessage(),$this->sauth->getMessageCode());
		}
		if(!is_numeric($a_ref_id))
		{
			return $this->__raiseError('No valid reference id given. Please choose an existing reference id of an ILIAS object',
									   'Client');
		}

		// Include main header
		include_once './include/inc.header.php';

		if(!$tmp_obj = ilObjectFactory::getInstanceByRefId($a_ref_id,false))
		{
			return $this->__raiseError('Cannot create object instance!','Server');
		}


		if(ilObject::_isInTrash($a_ref_id))
		{
			return $this->__raiseError("Object with ID $a_ref_id has been deleted.", 'Client');
		}

		include_once './webservice/soap/classes/class.ilObjectXMLWriter.php';

		$xml_writer = new ilObjectXMLWriter();
		if($user_id)
		{
			$xml_writer->setUserId($user_id);
			$xml_writer->enableOperations(true);
		}
		$xml_writer->setObjects(array($tmp_obj));
		if($xml_writer->start())
		{
			return $xml_writer->getXML();
		}

		return $this->__raiseError('Cannot create object xml !','Server');
	}

	function getObjectsByTitle($sid,$a_title,$user_id)
	{
		if(!$this->__checkSession($sid))
		{
			return $this->__raiseError($this->sauth->getMessage(),$this->sauth->getMessageCode());
		}
		if(!strlen($a_title))
		{
			return $this->__raiseError('No valid query string given.',
									   'Client');
		}

		// Include main header
		include_once './include/inc.header.php';

		include_once './Services/Search/classes/class.ilQueryParser.php';

		$query_parser =& new ilQueryParser($a_title);
		$query_parser->setMinWordLength(0);
		$query_parser->setCombination(QP_COMBINATION_AND);
		$query_parser->parse();
		if(!$query_parser->validate())
		{
			return $this->__raiseError($query_parser->getMessage(),
									   'Client');
		}

		include_once './Services/Search/classes/class.ilObjectSearchFactory.php';

		$object_search =& ilObjectSearchFactory::_getObjectSearchInstance($query_parser);
		$object_search->setFields(array('title'));
		$object_search->appendToFilter('role');
		$object_search->appendToFilter('rolt');
		$res =& $object_search->performSearch();
		if($user_id)
		{
			$res->setUserId($user_id);
		}

		$res->filter(ROOT_FOLDER_ID,true);

		$objs = array();
		foreach($res->getUniqueResults() as $entry)
		{
			$objs[] = ilObjectFactory::getInstanceByObjId($entry['obj_id'],false);
		}
		if(!count($objs))
		{
			return '';
		}

		include_once './webservice/soap/classes/class.ilObjectXMLWriter.php';

		$xml_writer = new ilObjectXMLWriter();
		if($user_id)
		{
			$xml_writer->setUserId($user_id);
			$xml_writer->enableOperations(true);
		}
		$xml_writer->setObjects($objs);
		if($xml_writer->start())
		{
			return $xml_writer->getXML();
		}

		return $this->__raiseError('Cannot create object xml !','Server');
	}

	function searchObjects($sid,$types,$key,$combination,$user_id)
	{
		if(!$this->__checkSession($sid))
		{
			return $this->__raiseError($this->sauth->getMessage(),$this->sauth->getMessageCode());
		}
		if(!is_array($types))
		{
			return $this->__raiseError('Types must be an array of object types.',
									   'Client');
		}
		if($combination != 'and' and $combination != 'or')
		{
			return $this->__raiseError('No valid combination given. Must be "and" or "or".',
									   'Client');
		}


		// Include main header
		include_once './include/inc.header.php';

		include_once './Services/Search/classes/class.ilQueryParser.php';

		$query_parser =& new ilQueryParser($key);
		$query_parser->setMinWordLength(3);
		$query_parser->setCombination($combination == 'and' ? QP_COMBINATION_AND : QP_COMBINATION_OR);
		$query_parser->parse();
		if(!$query_parser->validate())
		{
			return $this->__raiseError($query_parser->getMessage(),
									   'Client');
		}

		include_once './Services/Search/classes/class.ilObjectSearchFactory.php';

		$object_search =& ilObjectSearchFactory::_getObjectSearchInstance($query_parser);
		$object_search->setFilter($types);

		$res =& $object_search->performSearch();
		if($user_id)
		{
			$res->setUserId($user_id);
		}
		$res->filter(ROOT_FOLDER_ID,$combination == 'and' ? true : false);


		// Limit to 30 objects
		$counter = 0;
		$objs = array();
		foreach($res->getUniqueResults() as $entry)
		{
			if(++$counter == 30)
			{
				break;
			}
			$objs[] = ilObjectFactory::getInstanceByObjId($entry['obj_id'],false);
		}
		if(!count($objs))
		{
			return '';
		}

		include_once './webservice/soap/classes/class.ilObjectXMLWriter.php';

		$xml_writer = new ilObjectXMLWriter();

		if($user_id)
		{
			$xml_writer->setUserId($user_id);
			$xml_writer->enableOperations(true);
		}
		$xml_writer->setObjects($objs);
		if($xml_writer->start())
		{
			return $xml_writer->getXML();
		}

		return $this->__raiseError('Cannot create object xml !','Server');
	}

	function getTreeChilds($sid,$ref_id,$types,$user_id)
	{
		$all = false;

		if(!$this->__checkSession($sid))
		{
			return $this->__raiseError($this->sauth->getMessage(),$this->sauth->getMessageCode());
		}

		// Include main header
		include_once './include/inc.header.php';
		global $tree;

		if(!$target_obj =& ilObjectFactory::getInstanceByRefId($ref_id,false))
		{
			return $this->__raiseError('No valid reference id given.',
									   'Client');
		}
		if (intval($ref_id) == SYSTEM_FOLDER_ID) {
		    return $this->__raiseError('No valid reference id given.',
									   'Client');
		}

		if(!$types)
		{
			$all = true;
		}
 		$filter = is_array($types) ? $types : array();

		foreach($tree->getChilds($ref_id,'title') as $child)
		{
			if($all or in_array($child['type'],$types))
			{
				if($tmp = ilObjectFactory::getInstanceByRefId($child['ref_id'],false))
				{
					$objs[] = $tmp;
				}
			}
		}

		if(!$objs)
		{
			return '';
		}

		include_once './webservice/soap/classes/class.ilObjectXMLWriter.php';

		$xml_writer = new ilObjectXMLWriter();
		$xml_writer->setObjects($objs);
		$xml_writer->enableOperations(true);
		if($user_id)
		{
			$xml_writer->setUserId($user_id);
		}

		if($xml_writer->start())
		{
			return $xml_writer->getXML();
		}

		return $this->__raiseError('Cannot create object xml !','Server');
	}

	function getXMLTree($sid,$ref_id,$types,$user_id) {

	  if(!$this->__checkSession($sid))
            {
              return $this->__raiseError($this->sauth->getMessage(),$this->sauth->getMessageCode());
            }

          include_once './include/inc.header.php';

          global $tree;

          $nodedata  = $tree->getNodeData($ref_id);

          $nodearray = $tree->getSubTree($nodedata);

	  $filter = is_array($types) ? $types :  array("0" => "root","adm","lngf","mail",
			    "usrf","rolf","taxf","trac","pays",
			    "auth","chac","objf","recf","assf",
			    "stys","seas","extt");

	  foreach($nodearray as $node) {
            if (!in_array($node['type'], $filter)) {
              if ($tmp = ilObjectFactory::getInstanceByRefId($node['ref_id'],false)) {
                $nodes[] = $tmp;
              }
            }
          }


	  include_once './webservice/soap/classes/class.ilObjectXMLWriter.php';

	  $xml_writer = new ilObjectXMLWriter();
	  $xml_writer->setObjects($nodes);
	  $xml_writer->enableOperations(false);

	  if($user_id)
	    {
	      $xml_writer->setUserId($user_id);
	    }

	  if($xml_writer->start())
	    {
	      return $xml_writer->getXML();
	    }

	  return $this->__raiseError('Cannot create object xml !','Server');
	}


	function addObject($sid,$a_target_id,$a_xml)
	{
		if(!$this->__checkSession($sid))
		{
			return $this->__raiseError($this->sauth->getMessage(),$this->sauth->getMessageCode());
		}
		if(!strlen($a_xml))
		{
			return $this->__raiseError('No valid xml string given.',
									   'Client');
		}

		// Include main header
		include_once './include/inc.header.php';
		global $rbacsystem, $objDefinition,$ilUser;

		if(!$target_obj =& ilObjectFactory::getInstanceByRefId($a_target_id,false))
		{
			return $this->__raiseError('No valid target given.',
									   'Client');
		}

		if(ilObject::_isInTrash($a_target_id))
		{
			return $this->__raiseError("Parent with ID $a_target_id has been deleted.", 'Client');
		}

		$allowed_types = array('root','cat','grp','crs','fold');
		if(!in_array($target_obj->getType(),$allowed_types))
		{
			return $this->__raiseError('No valid target type. Target must be reference id of "course, group, category or folder"',
									   'Client');
		}

		$allowed_subtypes = $objDefinition->getSubObjects($target_obj->getType());

		foreach($allowed_subtypes as $row)
		{
			if($row['name'] != 'rolf')
			{
				$allowed[] = $row['name'];
			}
		}

		include_once './webservice/soap/classes/class.ilObjectXMLParser.php';

		$xml_parser =& new ilObjectXMLParser($a_xml);
		$xml_parser->startParsing();

		foreach($xml_parser->getObjectData() as $object_data)
		{
			// Check possible subtype
			if(!in_array($object_data['type'],$allowed))
			{
				return $this->__raiseError('Objects of type: '.$object_data['type'].' are not allowed to be subobjects of type '.
										   $target_obj->getType().'!',
										   'Client');
			}
			if(!$rbacsystem->checkAccess('create',$a_target_id,$object_data['type']))
			{
				return $this->__raiseError('No permission to create objects of type '.$object_data['type'].'!',
										   'Client');
			}
			if($object_data['type'] == 'crs')
			{
				return $this->__raiseError('Cannot create course objects. Use method addCourse() ',
										   'Client');
			}

			// It's not possible to add objects with non unique import ids
			if(strlen($object_data['import_id']) and ilObject::_lookupObjIdByImportId($object_data['import_id']))
			{
				return $this->__raiseError('An object with import id '.$object_data['import_id'].' already exists!',
										   'Server');
			}



			// call gui object method
			$class_name = $objDefinition->getClassName($object_data['type']);
			$module = $objDefinition->getModule($object_data['type']);
			$module_dir = ($module == "")
				? ""
				: $module."/";

			$class_constr = "ilObj".$class_name;
			require_once("./".$module_dir."classes/class.ilObj".$class_name.".php");

			$newObj = new $class_constr();

			$newObj->setType($object_data['type']);
			if(strlen($object_data['import_id']))
			{
				$newObj->setImportId($object_data['import_id']);
			}
			$newObj->setTitle($object_data['title']);
			$newObj->setDescription($object_data['description']);
			$newObj->create(); // true for upload
			$newObj->createReference();
			$newObj->putInTree($a_target_id);
			$newObj->setPermissions($a_target_id);
			$newObj->initDefaultRoles();

			switch($object_data['type'])
			{
				case 'grp':
					// Add member
					$newObj->addMember($object_data['owner'] ? $object_data['owner'] : $ilUser->getId(),
									   $newObj->getDefaultAdminRole());
					break;

				case 'lm':
				case 'dbk':
					$newObj->createLMTree();
					break;

			}

		}
		$ref_id = $newObj->getRefId();
		return  $ref_id  ? $ref_id : "0";
	}

	function addReference($sid,$a_source_id,$a_target_id)
	{
		if(!$this->__checkSession($sid))
		{
			return $this->__raiseError($this->sauth->getMessage(),$this->sauth->getMessageCode());
		}
		if(!is_numeric($a_source_id))
		{
			return $this->__raiseError('No source id given.',
									   'Client');
		}
		if(!is_numeric($a_target_id))
		{
			return $this->__raiseError('No target id given.',
									   'Client');
		}

		include_once './include/inc.header.php';
		global $objDefinition, $rbacsystem, $tree;

		if(!$source_obj =& ilObjectFactory::getInstanceByRefId($a_source_id,false))
		{
			return $this->__raiseError('No valid source id given.',
									   'Client');
		}
		if(!$target_obj =& ilObjectFactory::getInstanceByRefId($a_target_id,false))
		{
			return $this->__raiseError('No valid target id given.',
									   'Client');
		}

		if(!$objDefinition->allowLink($source_obj->getType()))
		{
			return $this->__raiseError('Linking of object type: '.$source_obj->getType().' is not allowed',
									   'Client');
		}

		$allowed_subtypes = $objDefinition->getSubObjects($target_obj->getType());
		foreach($allowed_subtypes as $row)
		{
			if($row['name'] != 'rolf')
			{
				$allowed[] = $row['name'];
			}
		}
		if(!in_array($source_obj->getType(),$allowed))
		{
			return $this->__raiseError('Objects of type: '.$source_obj->getType().' are not allowed to be subobjects of type '.
									   $target_obj->getType().'!',
									   'Client');
		}

		// Permission checks
		if(!$rbacsystem->checkAccess('create',$target_obj->getRefId(),$source_obj->getType()))
		{
			return $this->__raiseError('No permission to create objects of type '.$source_obj->getType().'!',
									   'Client');
		}
		if(!$rbacsystem->checkAccess('delete',$source_obj->getRefId()))
		{
			return $this->__raiseError('No permission to link object with id: '.$source_obj->getRefId().'!',
									   'Client');
		}
		// check if object already linked to target
		$possibleChilds = $tree->getChildsByType($target_obj->getRefId(), $source_obj->getType());
		foreach ($possibleChilds as $child) 
		{
			if ($child["obj_id"] == $source_obj->getId())
				return $this->__raiseError("Object already linked to target.","Client");
		}
		
		// Finally link it to target position

		$new_ref_id = $source_obj->createReference();
		$source_obj->putInTree($target_obj->getRefId());
		$source_obj->setPermissions($target_obj->getRefId());
		$source_obj->initDefaultRoles();

		return $new_ref_id ? $new_ref_id : "0";
	}

	function deleteObject($sid,$reference_id)
	{
		global $tree;

		if(!$this->__checkSession($sid))
		{
			return $this->__raiseError($this->sauth->getMessage(),$this->sauth->getMessageCode());
		}
		if(!is_numeric($reference_id))
		{
			return $this->__raiseError('No reference id given.',
									   'Client');
		}
		include_once './include/inc.header.php';
		global $tree, $rbacsystem, $rbacadmin;

		if(!$del_obj =& ilObjectFactory::getInstanceByRefId($reference_id,false))
		{
			return $this->__raiseError('No valid reference id given.',
									   'Client');
		}
		if(!$rbacsystem->checkAccess('delete',$del_obj->getRefId()))
		{
			return $this->__raiseError('No permission to delete object with id: '.$del_obj->getRefId().'!',
									   'Client');
		}

		// Delete tree
		if($tree->isDeleted($reference_id))
		{
			return $this->__raiseError('Node already deleted','Server');
		}

		$subnodes = $tree->getSubtree($tree->getNodeData($reference_id));
		foreach($subnodes as $subnode)
		{
			$rbacadmin->revokePermission($subnode["child"]);
			// remove item from all user desktops
			$affected_users = ilUtil::removeItemFromDesktops($subnode["child"]);
		}
		if(!$tree->saveSubTree($reference_id))
		{
			return $this->__raiseError('Node already deleted','Client');
		}

		return true;
	}

	function removeFromSystemByImportId($sid,$import_id)
	{
		if(!$this->__checkSession($sid))
		{
			return $this->__raiseError($this->sauth->getMessage(),$this->sauth->getMessageCode());
		}
		if(!strlen($import_id))
		{
			return $this->__raiseError('No import id given. Aborting!',
									   'Client');
		}
		include_once './include/inc.header.php';
		global $rbacsystem, $tree, $ilLog;

		// get obj_id
		if(!$obj_id = ilObject::_lookupObjIdByImportId($import_id))
		{
			return $this->__raiseError('No object found with import id: '.$import_id,
									   'Client');
		}

		// Check access
		$permission_ok = false;
		foreach($ref_ids = ilObject::_getAllReferences($obj_id) as $ref_id)
		{
			if($rbacsystem->checkAccess('delete',$ref_id))
			{
				$permission_ok = true;
				break;
			}
		}
		if(!$permission_ok)
		{
			return $this->__raiseError('No permission to delete the object with import id: '.$import_id,
									   'Server');
		}

		// Delete all references (delete permssions and entries in object_reference)
		foreach($ref_ids as $ref_id)
		{
			// All subnodes
			$node_data = $tree->getNodeData($ref_id);
			$subtree_nodes = $tree->getSubtree($node_data);

			foreach($subtree_nodes as $node)
			{
				$ilLog->write('Soap: removeFromSystemByImportId(). Deleting object with title id: '.$node['title']);
				$tmp_obj = ilObjectFactory::getInstanceByRefId($node['ref_id']);
				if(!is_object($tmp_obj))
				{
					return $this->__raiseError('Cannot create instance of reference id: '.$node['ref_id'],
											   'Server');
				}
				$tmp_obj->delete();
			}
			// Finally delete tree
			$tree->deleteTree($node_data);

		}

		return true;
	}


	function updateObjects($sid,$a_xml)
	{
		if(!$this->__checkSession($sid))
		{
			return $this->__raiseError($this->sauth->getMessage(),$this->sauth->getMessageCode());
		}
		if(!strlen($a_xml))
		{
			return $this->__raiseError('No valid xml string given.',
									   'Client');
		}

		// Include main header
		include_once './include/inc.header.php';
		global $rbacreview, $rbacsystem;

		include_once './webservice/soap/classes/class.ilObjectXMLParser.php';

		$xml_parser =& new ilObjectXMLParser($a_xml);
		$xml_parser->startParsing();


		// Validate incoming data
		$object_datas = $xml_parser->getObjectData();
		foreach($object_datas as & $object_data)
		{
			if(!$object_data["obj_id"])
			{
				return $this->__raiseError('No obj_id in xml found.', 'Client');
			}
			elseif ((int) $object_data["obj_id"] == -1 && count($object_data["references"])>0)
			{
				// object id might be unknown, resolve references instead to determine object id
				// all references should point to the same object, so using the first one is ok.
				foreach ($object_data["references"] as $refid) 
				{
					if(ilObject::_isInTrash($refid))
					{
						continue;
					}
					break;
				}	
				
				$obj_id_from_refid = ilObject::_lookupObjectId($object_data["references"][0], false);
				if (!$obj_id_from_refid)
				{
					return $this->__raiseError('No obj_id found for reference id '.$object_data["references"][0], 'CLIENT_OBJECT_NOT_FOUND');
				} else
				{
					$tmp_obj = ilObjectFactory::getInstanceByObjId($object_data['obj_id'], false);
					$object_data["obj_id"] = $obj_id_from_refid;
				}
			}
			
			$tmp_obj = ilObjectFactory::getInstanceByObjId($object_data['obj_id'], false);
			if ($tmp_obj == null)
			{
				return $this->__raiseError('No object for id '.$object_data['obj_id'].'!', 'CLIENT_OBJECT_NOT_FOUND');
			} 
			else 
			{
				$object_data["instance"] = $tmp_obj;
			}
			
			if($object_data['type'] == 'role')
			{
				$rolf_ids = $rbacreview->getFoldersAssignedToRole($object_data['obj_id'],true);
				$rolf_id = $rolf_ids[0];

				if(!$rbacsystem->checkAccess('write',$rolf_id))
				{
					return $this->__raiseError('No write permission for object with id '.$object_data['obj_id'].'!', 'Client');
				}
			}
			else
			{
				$permission_ok = false;
				foreach(ilObject::_getAllReferences($object_data['obj_id']) as $ref_id)
				{
					if($rbacsystem->checkAccess('write',$object_data['obj_id']))
					{
						$permission_ok = true;
						break;
					}
				}
				if(!$permission_ok)
				{
					return $this->__raiseError('No write permission for object with id '.$object_data['obj_id'].'!', 'Client');
				}
			}
		}
		// perform update
		if (count ($object_datas) > 0)
		{
			foreach($object_datas as $object_data)
			{
				$tmp_obj = $object_data["instance"];
				$tmp_obj->setTitle($object_data['title']);
				$tmp_obj->setDescription($object_data['description']);
				if(strlen($object_data['owner']))
				{
					$tmp_obj->setOwner($object_data['owner']);
				}
				$tmp_obj->update();
			}
			return true;
		}
		return false;

	}


}
?>