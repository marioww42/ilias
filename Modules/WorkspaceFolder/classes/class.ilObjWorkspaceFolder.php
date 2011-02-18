<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* Class ilObjWorkspaceFolder
*
* @author Wolfgang Merkens <wmerkens@databay.de>
* @version $Id: class.ilObjFolder.php 25528 2010-09-03 10:37:11Z smeyer $
*
* @extends ilObject
*/
class ilObjWorkspaceFolder extends ilObject
{
	var $folder_tree;
	
	/**
	* Constructor
	* @access	public
	* @param	integer	reference_id or object_id
	* @param	boolean	treat the id as reference_id (true) or object_id (false)
	*/
	function __construct($a_id = 0,$a_call_by_reference = true)
	{
		$this->type = "fold";
		parent::__construct($a_id,$a_call_by_reference);
		$this->lng->loadLanguageModule('fold');
	}

	function setFolderTree($a_tree)
	{
		$this->folder_tree =& $a_tree;
	}
	
	/**
	 * Clone folder
	 *
	 * @access public
	 * @param int target id
	 * @param int copy id
	 * 
	 */
	public function cloneObject($a_target_id,$a_copy_id = 0)
	{
	 	$new_obj = parent::cloneObject($a_target_id,$a_copy_id);
		
		// Copy learning progress settings
		include_once('Services/Tracking/classes/class.ilLPObjSettings.php');
		$obj_settings = new ilLPObjSettings($this->getId());
		$obj_settings->cloneSettings($new_obj->getId());
		unset($obj_settings);
		
		return $new_obj;
	}

	/**
	* insert folder into grp_tree
	*
	*/
	function putInTree($a_parent)
	{
		global $tree;
		
		if (!is_object($this->folder_tree))
		{
			$this->folder_tree =& $tree; 
		}

		if ($this->withReferences())
		{
			// put reference id into tree
			$this->folder_tree->insertNode($this->getRefId(), $a_parent);
		}
		else
		{
			// put object id into tree
			$this->folder_tree->insertNode($this->getId(), $a_parent);
		}
	}
	
	/**
	 * Clone object dependencies (crs items, preconditions)
	 *
	 * @access public
	 * @param int target ref id of new course
	 * @param int copy id
	 * 
	 */
	public function cloneDependencies($a_target_id,$a_copy_id)
	{
		global $tree;
		
		parent::cloneDependencies($a_target_id,$a_copy_id);

		if($course_ref_id = $tree->checkForParentType($this->getRefId(),'crs') and
			$new_course_ref_id = $tree->checkForParentType($a_target_id,'crs'))
		{
			include_once('Modules/Course/classes/class.ilCourseItems.php');
			$course_obj =& ilObjectFactory::getInstanceByRefId($course_ref_id,false);
			$course_items = new ilCourseItems($course_obj->getRefId(),$this->getRefId());
			$course_items->cloneDependencies($a_target_id,$a_copy_id);			
		}
		
		include_once('Services/Tracking/classes/class.ilLPCollections.php');
		$lp_collection = new ilLPCollections($this->getId());
		$lp_collection->cloneCollections($a_target_id,$a_copy_id);		
		
	 	return true;
	}
	
	/**
	 * private functions which iterates through all folders and files 
	 * and create an according file structure in a temporary directory. This function works recursive. 
	 *
	 * @param integer $refid reference it
	 * @param tmpdictory $tmpdir
	 * @return returns first created directory
	 */
	private static function recurseFolder ($refid, $title, $tmpdir) {
		global $rbacsystem, $tree, $ilAccess;
				
		$tmpdir = $tmpdir.DIRECTORY_SEPARATOR.ilUtil::getASCIIFilename($title);
		ilUtil::makeDir($tmpdir);
		
		$subtree = $tree->getChildsByTypeFilter($refid, array("fold","file"));
		
		foreach ($subtree as $child) 
		{
			if (!$ilAccess->checkAccess("read", "", $child["ref_id"]))
			{
				continue;			
			}
			if (ilObject::_isInTrash($child["ref_id"]))
			{
				continue;
			}
			if ($child["type"] == "fold")
			{
				ilObjFolder::recurseFolder ($child["ref_id"], $child["title"], $tmpdir);
			} else {
				$newFilename = $tmpdir.DIRECTORY_SEPARATOR.ilUtil::getASCIIFilename($child["title"]);
				// copy to temporal directory
				$oldFilename = ilObjFile::_lookupAbsolutePath($child["obj_id"]);
				if (!copy ($oldFilename, $newFilename))
				{
					throw new ilFileException("Could not copy ".$oldFilename." to ".$newFilename);
				}	
				touch($newFilename, filectime($oldFilename));								
			}
		}
		
	}
	
	public function downloadFolder() {
		global $lng, $rbacsystem, $ilAccess;
		include_once "./Services/Utilities/classes/class.ilUtil.php";
		include_once 'Modules/File/classes/class.ilObjFile.php';
		include_once 'Modules/File/classes/class.ilFileException.php';
		if (!$ilAccess->checkAccess("read", "", $this->getRefId()))
		{
			$this->ilErr->raiseError(get_class($this)."::downloadFolder(): missing read permission!",$this->ilErr->WARNING);
		}
		if (ilObject::_isInTrash($this->getRefId()))
		{
			$this->ilErr->raiseError(get_class($this)."::downloadFolder(): object is trashed!",$this->ilErr->WARNING);
		}
		
		$zip = PATH_TO_ZIP;
		$tmpdir = ilUtil::ilTempnam();		
		ilUtil::makeDir($tmpdir);
		$basename = ilUtil::getAsciiFilename($this->getTitle());
		$deliverFilename = $basename.".zip";
		$zipbasedir = $tmpdir.DIRECTORY_SEPARATOR.$basename;
		$tmpzipfile = $tmpdir.DIRECTORY_SEPARATOR.$deliverFilename;
		
		try {
			ilObjFolder::recurseFolder ($this->getRefId(), $this->getTitle(), $tmpdir);
			ilUtil::zip($zipbasedir, $tmpzipfile);
			rename($tmpzipfile,$zipfile = ilUtil::ilTempnam());
			ilUtil::delDir($tmpdir);
			ilUtil::deliverFile($zipfile,$deliverFilename,'',false,true);
		} catch (ilFileException $e) {
			ilUtil::sendInfo($e->getMessage(), true);
		}
	}
	
	/**
	* Get container view mode
	*/
	function getViewMode()
	{
		global $tree;
		
		// default: by type
		$view = ilContainer::VIEW_BY_TYPE;
		
		// get view mode from course
		if ($course_ref_id = $tree->checkForParentType($this->ref_id,'crs'))
		{
			include_once("./Modules/Course/classes/class.ilObjCourseAccess.php");
			$view_mode = ilObjCourseAccess::_lookupViewMode(ilObject::_lookupObjId($course_ref_id));
			if ($view_mode == ilContainer::VIEW_SESSIONS ||
				$view_mode == ilContainer::VIEW_BY_TYPE ||
				$view_mode == ilContainer::VIEW_SIMPLE)
			{
				$view = $view_mode;
			}
		}
		
		return $view;
	}

	/**
	* Add additional information to sub item, e.g. used in
	* courses for timings information etc.
	*/
	function addAdditionalSubItemInformation(&$a_item_data)
	{
		global $tree;
		
		static $items = null;
		
		if(!is_object($items[$this->getRefId()]))
		{
			if ($course_ref_id = $tree->checkForParentType($this->getRefId(),'crs'))
			{
				include_once("./Modules/Course/classes/class.ilObjCourse.php");
				include_once("./Modules/Course/classes/class.ilCourseItems.php");
				$course_obj = new ilObjCourse($course_ref_id);
				$items[$this->getRefId()] = new ilCourseItems($course_obj->getRefId(), $this->getRefId());
			}
		}
		if(is_object($items[$this->getRefId()]))
		{
			$items[$this->getRefId()]->addAdditionalSubItemInformation($a_item_data);
		}
	}
	
	/**
	 * Overwritten read method
	 *
	 * @access public
	 * @param
	 * @return
	 */
	public function read()
	{
		global $tree;
		
		parent::read();
		
		// Inherit order type from parent course (if exists)
		include_once('./Services/Container/classes/class.ilContainerSortingSettings.php');
		$this->setOrderType(ilContainerSortingSettings::_lookupSortMode($this->getId()));
	}

} // END class.ilObjFolder
?>
