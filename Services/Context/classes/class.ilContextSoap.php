<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once "Services/Context/classes/class.ilContextBase.php";

/** 
 * Service context for soap
 * 
 * @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
 * @version $Id$
 * 
 * @ingroup ServicesContext
 */
class ilContextSoap extends ilContextBase
{			
	/**
	 * Are redirects supported?
	 * 
	 * @return bool 
	 */
	public static function supportsRedirects()
	{
		return false;
	}
	
	/**
	 * Based on user authentication?
	 *  
	 * @return bool
	 */
	public static function hasUser()
	{
		return true;
	}
	
	/**
	 * Uses HTTP aka browser 
	 * 
	 * @return bool 
	 */
	public static function usesHTTP()
	{
		return true;
	}
	
	/**
	 * Has HTML output
	 *  
	 * @return bool
	 */
	public static function hasHTML()
	{
		return false;
	}
}

?>