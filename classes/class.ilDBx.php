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



//pear DB abstraction layer
require_once ("DB.php");

/**
* Database Wrapper
*
* this class should extend PEAR::DB, add error Management
* in case of a db-error in any database query the ilDBx-class raises an error
*
* @author Peter Gabriel <peter@gabriel-online.net>
*
* @version $Id$
* @package application
* @access public
*/
class ilDBx extends PEAR
{
	/**
	* error class
	* @var object error_class
	* @access private
	*/
	var $error_class;

	/**
	* database handle from pear database class.
	* @var string
	*/
	var $db;

	/**
	* database-result-object
	* @var string
	*/
	var $result;

	/**
	* myqsl max_allowed_packet size
	* @var int
	*/
	var $max_allowed_packet_size;


	/**
	* constructor
	*
	* set up database conncetion and the errorhandling
	*
	* @param string dsn database-connection-string for pear-db
	*/
	function ilDBx($dsn)
	{
		//call parent constructor
		$parent = get_parent_class($this);
		$this->$parent();

		//set up error handling
		$this->error_class = new ilErrorHandling();
		$this->setErrorHandling(PEAR_ERROR_CALLBACK, array($this->error_class,'errorHandler'));

		//check dsn
		if ($dsn=="")
			$this->raiseError("no DSN given", $this->error_class->FATAL);

		$this->dsn = $dsn;

		//connect to database
		$this->db = DB::connect($this->dsn, true);

		//check error
		if (DB::isError($this->db)) {
			$this->raiseError($this->db->getMessage(), $this->error_class->FATAL);
		}

		// SET 'max_allowed_packet' (only possible for mysql version 4)
		$this->setMaxAllowedPacket();

		return true;
	} //end constructor

	/**
	* destructor
	*/
	function _ilDBx() {
		//$this->db->disconnect();
	} //end destructor

	/**
	* disconnect from database
	*/
	function disconnect()
	{
//		$this->db->disconnect();
	}

	/**
	* query 
	* 
	* this is the wrapper itself. query a string, and return the resultobject,
	* or in case of an error, jump to errorpage
	* 
	* @param string
	* @return object DB
	*/
	function query($sql)
	{
		$r = $this->db->query($sql);

		if (DB::isError($r))
		{
			$this->raiseError($r->getMessage()."<br><font size=-1>SQL: ".$sql."</font>", $this->error_class->FATAL);
		}
		else
		{
			return $r;
		}
	} //end function


	/**
	* wrapper for quote method
	*/
	function quote($a_query)
	{
		// maybe quoteSmart should be used in the future
		return $this->db->quote($a_query);
	}


	/**
	* getrow
	*
	* this is the wrapper itself. query a string, and return the resultobject,
	* or in case of an error, jump to errorpage
	*
	* @param string
	* @return object DB
	*/
	function getRow($sql,$mode = DB_FETCHMODE_OBJECT)
	{
		$r = $this->db->getrow($sql,$mode);

		if (DB::isError($r))
		{
			$this->raiseError($r->getMessage()."<br><font size=-1>SQL: ".$sql."</font>", $this->error_class->FATAL);
		}
		else
		{
			return $r;
		}
	} //end function


	/**
	* get last insert id
	*/
	function getLastInsertId()
	{
		$r = $this->query("SELECT LAST_INSERT_ID()");
		$row = $r->fetchRow();

		return $row[0];
	}

	/**
	* Wrapper for Pear prepare
	* @param String query
	* @return resource
	*/
	function prepare($query)
	{
		return $this->db->prepare($query);
	}

	/**
	* Wrapper for Pear executeMultiple
	* @param resource (statement from prepare)
	* @param array multidim array of data
	* @return mixed a new DB_result/DB_OK  or a DB_Error, if fail
	*/
	function executeMultiple($stmt,$data)
	{
		$res = $this->db->executeMultiple($stmt,$data);

		if (DB::isError($res))
		{
			$this->raiseError($res->getMessage()."<br><font size=-1>SQL: ".$data."</font>", $this->error_class->FATAL);
		}
		else
		{
			return $res;
		}
	}

	/**
	* Wrapper for Pear executeMultiple
	* @param resource (statement from prepare)
	* @param array multidim array of data
	* @return mixed a new DB_result/DB_OK  or a DB_Error, if fail
	*/
	function execute($stmt,$data)
	{
		$res = $this->db->execute($stmt,$data);

		if (DB::isError($res))
		{
			$this->raiseError($res->getMessage()."<br><font size=-1>SQL: ".$data."</font>", $this->error_class->FATAL);
		}
		else
		{
			return $res;
		}
	}

	function checkQuerySize($a_query)
	{
		global $lang;

		if(strlen($a_query) >= $this->max_allowed_packet_size)
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	


	// PRIVATE
	function setMaxAllowedPacket()
	{

		// GET MYSQL VERSION
		$query = "SHOW VARIABLES LIKE 'version'";
		$res = $this->db->query($query);
		if(DB::isError($res))
		{
			$this->raiseError($res->getMessage()."<br><font size=-1>SQL: ".$query."</font>", $this->error_class->FATAL);
		}
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$version = $row->Value;
		}

		// CHANG VALUE IF MYSQL VERSION > 4.0
		if(substr($version,0,1) == "4")
		{
			ini_get("post_max_size");
			$query = "SET GLOBAL max_allowed_packet = ".(int) ini_get("post_max_size") * 1024 * 1024;
			$this->db->query($query);
			if(DB::isError($res))
			{
				$this->raiseError($res->getMessage()."<br><font size=-1>SQL: ".$query."</font>", $this->error_class->FATAL);
			}
		}
		// STORE NEW max_size in member variable
		$query = "SHOW VARIABLES LIKE 'max_allowed_packet'";
		if(DB::isError($res))
		{
			$this->raiseError($res->getMessage()."<br><font size=-1>SQL: ".$query."</font>", $this->error_class->FATAL);
		}
		$res = $this->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$this->max_allowed_packet_size = $row->Value;
		}
		#var_dump("<pre>",$this->max_allowed_packet_size,"<pre>");
		return true;
	}

} //end Class
?>
