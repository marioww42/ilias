<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Helper class to create new object types (object_data, RBAC) 
 *
 * @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
 * $Id: class.ilObjFolderGUI.php 25134 2010-08-13 14:22:11Z smeyer $
 *
 * @ingroup ServicesMigration
 */
class ilDBUpdateNewObjectType
{	
	const RBAC_OP_EDIT_PERMISSIONS = 1;
	const RBAC_OP_VISIBLE = 2;
	const RBAC_OP_READ = 3;
	const RBAC_OP_WRITE = 4;
	const RBAC_OP_DELETE = 6;
	const RBAC_OP_COPY = 99;
	
	/**
	 * Add new type to object data
	 * 
	 * @param string $a_type_id
	 * @param string $a_type_title 
	 * @return int insert id
	 */
	public static function addNewType($a_type_id, $a_type_title)
	{
		global $ilDB;
		
		$type_id = $ilDB->nextId('object_data');
		
		$fields = array(
			'obj_id' => array('integer', $type_id),
			'type' => array('text', 'typ'),
			'title' => array('text', $a_type_id),
			'description' => array('text', $a_type_title),
			'owner' => array('integer', -1),
			'create_date' => array('timestamp', ilUtil::now()),
			'last_update' => array('timestamp', ilUtil::now())
		);	
		$ilDB->insert('object_data', $fields);
		
		return $type_id;
	}
	
	/**
	 * Add RBAC operations for type
	 * 
	 * @param int $a_type_id
	 * @param array $a_operations 
	 */
	public static function addRBACOperations($a_type_id, array $a_operations)
	{		
		foreach($a_operations as $ops_id)
		{						
			if(self::isValidRBACOperation($ops_id))
			{			
				if($ops_id == self::RBAC_OP_COPY)
				{
					$ops_id = self::getCustomRBACOperationId('copy');					
				}
				
				self::addRBACOperation($a_type_id, $ops_id);
			}
		}		
	}
	
	/**
	 * Add RBAC operation
	 * 
	 * @param int $a_type_id
	 * @param int $a_ops_id 
	 */
	protected static function addRBACOperation($a_type_id, $a_ops_id)
	{
		global $ilDB;
		
		$fields = array(
			'typ_id' => array('integer', $a_type_id),
			'ops_id' => array('integer', $a_ops_id)
		);
		return $ilDB->insert('rbac_ta', $fields);
	}
	
	/**
	 * Check if given RBAC operation id is valid
	 * 
	 * @param int $a_ops_id 
	 * @return bool
	 */
	protected static function isValidRBACOperation($a_ops_id)
	{
		$valid = array(
			self::RBAC_OP_EDIT_PERMISSIONS,
			self::RBAC_OP_VISIBLE,
			self::RBAC_OP_READ,
			self::RBAC_OP_WRITE,
			self::RBAC_OP_DELETE,
			self::RBAC_OP_COPY				
		);
		if(in_array($a_ops_id, $valid))
		{
			return true;
		}
		return false;
	}
	
	/**
	 * Get id of RBAC operation
	 * 
	 * @param string $a_operation
	 * @return int
	 */
	protected static function getCustomRBACOperationId($a_operation)
	{
		global $ilDB;
		
		$sql = 'SELECT ops_id'.
			' FROM rbac_operations'.
			' WHERE operation = '.$ilDB->quote($a_operation, 'text');
		$res = $ilDB->query($sql);
		$row = $ilDB->fetchAssoc($res);
		return $row['ops_id'];
	}
	
	/**
	 * Add custom RBAC operation
	 * 
	 * @param string $a_id
	 * @param string $a_title 
	 * @return int ops_id
	 */
	public static function addCustomRBACOperation($a_id, $a_title)
	{
		global $ilDB;
		
		$ops_id = $ilDB->nextId('rbac_operations');
		
		$fields = array(
			'ops_id' => array('integer', $ops_id),
			'operation' => array('text', $a_id),
			'description' => array('text', $a_title)
		);		
		$ilDB->insert('rbac_operations', $fields);
		
		return $ops_id;
	}	

	/**
	 * Get id for object data type entry
	 * 
	 * @param string $a_type
	 * @return int
	 */
	protected static function getObjectTypeId($a_type)
	{
		global $ilDB;
		
		$sql = 'SELECT obj_id FROM object_data'.
			' WHERE type = '.$ilDB->quote('typ', 'text').
			' AND title = '.$ilDB->quote($a_type, 'text');
		$res = $ilDB->query($sql);
		$row = $ilDB->fetchAssoc($res);
		return $row['obj_id'];
	}
	
	/**
	 * Add create RBAC operations for parent object types
	 * 
	 * @param string  $a_id
	 * @param string $a_title
	 * @param array $a_parent_types 
	 */
	public static function addRBACCreate($a_id, $a_title, array $a_parent_types)
	{		
		$ops_id = self::addCustomRBACOperation($a_id, $a_title);
		
		foreach($a_parent_types as $type)
		{
			$type_id = self::getObjectTypeId($type);		
			if($type_id)
			{
				self::addRBACOperation($type_id, $ops_id);
			}
		}		
	}	
}

?>