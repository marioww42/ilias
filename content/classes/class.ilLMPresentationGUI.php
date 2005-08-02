<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2004 ILIAS open source, University of Cologne            |
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

require_once("./content/classes/class.ilObjLearningModule.php");
require_once("./classes/class.ilMainMenuGUI.php");
require_once("./classes/class.ilObjStyleSheet.php");

/**
* Class ilLMPresentationGUI
*
* GUI class for learning module presentation
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @package content
*/
class ilLMPresentationGUI
{
	var $ilias;
	var $lm;
	var $tpl;
	var $lng;
	var $layout_doc;
	var $offline;
	var $offline_directory;

	function ilLMPresentationGUI()
	{
		global $ilias, $lng, $tpl, $rbacsystem;

		$this->ilias =& $ilias;
		$this->lng =& $lng;
		$this->tpl =& $tpl;
		$this->offline = false;
		$this->frames = array();

		$cmd = (!empty($_GET["cmd"]))
			? $_GET["cmd"]
			: "layout";

		$cmd = ($cmd == "edpost")
			? "ilCitation"
			: $cmd;

		// Todo: check lm id
		$type = $this->ilias->obj_factory->getTypeByRefId($_GET["ref_id"]);

		// TODO: WE NEED AN OBJECT FACTORY FOR GUI CLASSES
		switch($type)
		{
			case "dbk":
				include_once("./content/classes/class.ilObjDlBookGUI.php");

				$this->lm_gui = new ilObjDlBookGUI($data,$_GET["ref_id"],true,false);
				break;
			case "lm":
				include_once("./content/classes/class.ilObjLearningModuleGUI.php");

				$this->lm_gui = new ilObjLearningModuleGUI($data,$_GET["ref_id"],true,false);

				break;
		}
		$this->lm =& $this->lm_gui->object;
		
		$this->lm_tree = new ilTree($this->lm->getId());
		$this->lm_tree->setTableNames('lm_tree','lm_data');
		$this->lm_tree->setTreeTablePK("lm_id");

		
		// check, if learning module is online
		if (!$rbacsystem->checkAccess("write", $_GET["ref_id"]))
		{
			if (!$this->lm->getOnline())
			{
				$ilias->raiseError($lng->txt("permission_denied"), $ilias->error_obj->WARNING);
			}
		}


		// ### AA 03.09.01 added page access logger ###
		$this->lmAccess($this->ilias->account->getId(),$_GET["ref_id"],$_GET["obj_id"]);

		if ($cmd == "post")
		{
			$cmd = key($_POST["cmd"]);
		}
		$this->$cmd();
	}
	
	
	/**
	* set offline mode (content is generated for offline package)
	*/
	function setOfflineMode($a_offline = true)
	{
		$this->offline = $a_offline;
	}
	
	
	/**
	* checks wether offline content generation is activated 
	*/
	function offlineMode()
	{
		return $this->offline;
	}
	
	/**
	* set export format
	*
	* @param	string		$a_format		"html" / "scorm"
	*/
	function setExportFormat($a_format)
	{
		$this->export_format = $a_format;
	}

	/**
	* get export format
	*
	* @return	string		export format
	*/
	function getExportFormat()
	{
		return $this->export_format;
	}

	/**
	* this dummy function is needed for offline package creation
	*/
	function nop()
	{
	}

	// ### AA 03.09.01 added page access logger ###
	/**
	* logs access to lm objects to enable retrieval of a 'last viewed lm list' and 'return to last lm'
	* allows only ONE entry per user and lm object
	*
	* A.L. Ammerlaan / INGMEDIA FH-Aachen / 2003.09.08
	*/
	function lmAccess($usr_id,$lm_id,$obj_id)
	{
		// first check if an entry for this user and this lm already exist, when so, delete
		$q = "DELETE FROM lo_access ".
			"WHERE usr_id='".$usr_id."' ".
			"AND lm_id='".$lm_id."'";
		$this->ilias->db->query($q);
		$title = (is_object($this->lm))?$this->lm->getTitle():"- no title -";
		// insert new entry
		$pg_title = "";
		$q = "INSERT INTO lo_access ".
			"(timestamp,usr_id,lm_id,obj_id,lm_title) ".
			"VALUES ".
			"(now(),'".$usr_id."','".$lm_id."','".$obj_id."','".ilUtil::prepareDBString($title)."')";
		$this->ilias->db->query($q);
	}

    /**
    *   calls export of digilib-object
    *   at this point other lm-objects can be exported
    *
    *   @param
    *   @access public
    *   @return
    */
	function export()
	{
		switch($this->lm->getType())
		{
			case "dbk":
				$this->lm_gui->export();
				break;
		}
	}

    /**
    *   the different export types are processed here
    *
    *   @param
    *   @access public
    *   @return
    */
	function offlineexport() {

		if ($_POST["cmd"]["cancel"] != "")
		{
			ilUtil::redirect("lm_presentation.php?cmd=layout&frame=maincontent&ref_id=".$_GET["ref_id"]);
		}

		switch($this->lm->getType())
		{
			case "dbk":
				//$this->lm_gui->offlineexport();
				$_GET["frame"] = "maincontent";

				$query = "SELECT * FROM object_reference,object_data WHERE object_reference.ref_id='".
					$_GET["ref_id"]."' AND object_reference.obj_id=object_data.obj_id ";
				$result = $this->ilias->db->query($query);
				$objRow = $result->fetchRow(DB_FETCHMODE_ASSOC);
				$_GET["obj_id"] = $objRow["obj_id"];

				$query = "SELECT * FROM lm_data WHERE lm_id='".$objRow["obj_id"]."' AND type='pg' ";
				$result = $this->ilias->db->query($query);

				$page = 0;
				$showpage = 0;
				while (is_array($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) )
				{

					$page++;

					if ($_POST["pages"]=="all" || ($_POST["pages"]=="fromto" && $page>=$_POST["pagefrom"] && $page<=$_POST["pageto"] ))
                    {

						if ($showpage>0)
						{
							if($_POST["type"] == "pdf") $output .= "<hr BREAK >\n";
							if($_POST["type"] == "print") $output .= "<p style=\"page-break-after:always\" />";
							if($_POST["type"] == "html") $output .= "<br><br><br><br>";
						}
						$showpage++;

						$_GET["obj_id"] = $row["obj_id"];
						$o = $this->layout("main.xml",false);

                        $output .= "<div xmlns:xhtml=\"http://www.w3.org/1999/xhtml\" class=\"ilc_PageTitle\">".$this->lm->title."</div><p>";
						$output .= $o;

						$output .= "\n<table cellpadding=0 cellspacing=0 border=0 width=100%><tr><td valign=top align=center>- ".$page." -</td></tr></table>\n";

					}
				}

				$printTpl = new ilTemplate("tpl.print.html", true, true, true);

				if($_POST["type"] == "print")
				{
					$printTpl->touchBlock("printreq");
					$css1 = ilObjStyleSheet::getContentStylePath($this->lm->getStyleSheetId());
					$css2 = ilUtil::getStyleSheetLocation();
				}
				else
				{
					$css1 = "./css/blueshadow.css";
					$css2 = "./css/content.css";
				}
				$printTpl->setVariable("LOCATION_CONTENT_STYLESHEET", $css1 );

				$printTpl->setVariable("LOCATION_STYLESHEET", $css2);
				$printTpl->setVariable("CONTENT",$output);

				// syntax style
				$printTpl->setCurrentBlock("SyntaxStyle");
				$printTpl->setVariable("LOCATION_SYNTAX_STYLESHEET",
					ilObjStyleSheet::getSyntaxStylePath());
				$printTpl->parseCurrentBlock();


				$html = $printTpl->get();

				/**
				*	Check if export-directory exists
				*/
				$this->lm->createExportDirectory();
				$export_dir = $this->lm->getExportDirectory();

				/**
				*	create html-offline-directory
				*/
				$fileName = "offline";
				$fileName = str_replace(" ","_",$fileName);

				if (!file_exists($export_dir."/".$fileName))
				{
					@mkdir($export_dir."/".$fileName);
					@chmod($export_dir."/".$fileName, 0755);

					@mkdir($export_dir."/".$fileName."/css");
					@chmod($export_dir."/".$fileName."/css", 0755);

				}

				if($_POST["type"] == "xml")
				{
					//vd($_GET["ref_id"]);
					$tmp_obj =& $this->ilias->obj_factory->getInstanceByRefId($_GET["ref_id"]);

					if ($tmp_obj->getType() == "dbk" )
					{
						require_once "content/classes/class.ilObjDlBook.php";
						$dbk =& new ilObjDlBook($_GET["ref_id"]);
						$dbk->export();
					}

				}
				else if($_POST["type"] == "print")
				{
					echo $html;
				}
				else if ($_POST["type"]=="html")
				{

					/**
					*	copy data into dir
					*	zip all end deliver zip-file for download
					*/

					$css1 = file("./templates/default/blueshadow.css");
					$css1 = implode($css1,"");

					$fp = fopen($export_dir."/".$fileName."/css/blueshadow.css","wb");
					fwrite($fp,$css1);
					fclose($fp);

					$css2 = file("./content/content.css");
					$css2 = implode($css2,"");

					$fp = fopen($export_dir."/".$fileName."/css/content.css","wb");
					fwrite($fp,$css2);
					fclose($fp);


					$fp = fopen($export_dir."/".$fileName."/".$fileName.".html","wb");
					fwrite($fp,$html);
					fclose($fp);

					ilUtil::zip($export_dir."/".$fileName, $export_dir."/".$fileName.".zip");

                    ilUtil::deliverFile($export_dir."/".$fileName.".zip", $fileName.".zip");

				}
                else if ($_POST["type"]=="pdf")
				{

                    ilUtil::html2pdf($html, $export_dir."/".$fileName.".pdf");

                    ilUtil::deliverFile($export_dir."/".$fileName.".pdf", $fileName.".pdf");

				}

				exit;
		}

	}

    /**
    *   draws export-form on screen
    *
    *   @param
    *   @access public
    *   @return
    */
	function offlineexportform()
    {

		switch($this->lm->getType())
		{
			case "dbk":
				$this->lm_gui->offlineexportform();
				break;
		}

	}


    /**
    *   export bibinfo for download or copy/paste
    *
    *   @param
    *   @access public
    *   @return
    */
	function exportbibinfo()
	{
		$query = "SELECT * FROM object_reference,object_data WHERE object_reference.ref_id='".$_GET["ref_id"]."' AND object_reference.obj_id=object_data.obj_id ";
		$result = $this->ilias->db->query($query);

		$objRow = $result->fetchRow(DB_FETCHMODE_ASSOC);

		$filename = preg_replace('/[^a-z0-9_]/i', '_', $objRow["title"]);

		$C = $this->lm_gui->showAbstract(array(1));

		if ($_GET["print"]==1)
		{
			$printTpl = new ilTemplate("tpl.print.html", true, true, true);
			$printTpl->touchBlock("printreq");
			$css1 = ilObjStyleSheet::getContentStylePath($this->lm->getStyleSheetId());
			$css2 = ilUtil::getStyleSheetLocation();
			$printTpl->setVariable("LOCATION_CONTENT_STYLESHEET", $css1 );

			$printTpl->setVariable("LOCATION_STYLESHEET", $css2);

			// syntax style
			$printTpl->setCurrentBlock("SyntaxStyle");
			$printTpl->setVariable("LOCATION_SYNTAX_STYLESHEET",
				ilObjStyleSheet::getSyntaxStylePath());
			$printTpl->parseCurrentBlock();

			$printTpl->setVariable("CONTENT",$C);

			echo $printTpl->get();
			exit;
		}
		else
		{
			ilUtil::deliverData($C, $filename.".html");
			exit;
		}

	}




	function attrib2arr(&$a_attributes)
	{
		$attr = array();
		if(!is_array($a_attributes))
		{
			return $attr;
		}
		foreach ($a_attributes as $attribute)
		{
			$attr[$attribute->name()] = $attribute->value();
		}
		return $attr;
	}

	/**
	* get frames of current frame set
	*/
	function getCurrentFrameSet()
	{
		return $this->frames;
	}
	
	/**
	* generates frame layout
	*/
	function layout($a_xml = "main.xml", $doShow = true)
	{
		global $tpl, $ilBench;

		$ilBench->start("ContentPresentation", "layout");

		// export scorm always to 1window
		if ($this->getExportFormat() == "scorm")
		{
			$layout = "1window";
		}
		else
		{
			$layout = $this->lm->getLayout();
		}

		//$doc = xmldocfile("./layouts/lm/".$layout."/".$a_xml);

		// xmldocfile is deprecated! Use domxml_open_file instead.
		// But since using relative pathes with domxml under windows don't work,
		// we need another solution:
		$xmlfile = file_get_contents("./layouts/lm/".$layout."/".$a_xml);

		if (!$doc = domxml_open_mem($xmlfile)) { echo "ilLMPresentation: XML File invalid"; exit; }
		$this->layout_doc =& $doc;
//echo ":".htmlentities($xmlfile).":$layout:$a_xml:";

		// get current frame node
		$ilBench->start("ContentPresentation", "layout_getFrameNode");
		$xpc = xpath_new_context($doc);
		$path = (empty($_GET["frame"]) || ($_GET["frame"] == "_new"))
			? "/ilLayout/ilFrame[1]"
			: "//ilFrame[@name='".$_GET["frame"]."']";
		$result = xpath_eval($xpc, $path);
		$found = $result->nodeset;
		if (count($found) != 1) { echo "ilLMPresentation: XML File invalid"; exit; }
		$node = $found[0];

		$ilBench->stop("ContentPresentation", "layout_getFrameNode");
//echo "<br>layout 2";
		// ProcessFrameset
		// node is frameset, if it has cols or rows attribute
		$attributes = $this->attrib2arr($node->attributes());
		$this->frames = array();
		if((!empty($attributes["rows"])) || (!empty($attributes["cols"])))
		{
			$ilBench->start("ContentPresentation", "layout_processFrameset");
			$content .= $this->buildTag("start", "frameset", $attributes);
//echo "<br>A: reset frames"; flush();
			//$this->frames = array();
			$this->processNodes($content, $node);
			$content .= $this->buildTag("end", "frameset");
			$this->tpl = new ilTemplate("tpl.frameset.html", true, true, true);
			$this->tpl->setVariable("PAGETITLE", "- ".$this->lm->getTitle());
			$this->tpl->setVariable("FS_CONTENT", $content);
			$ilBench->stop("ContentPresentation", "layout_processFrameset");
			if (!$doshow)
			{
				$content = $this->tpl->get();
			}
		}
		else	// node is frame -> process the content tags
		{
			// ProcessContentTag
			$ilBench->start("ContentPresentation", "layout_processContentTag");
			//if ((empty($attributes["template"]) || !empty($_GET["obj_type"])))
			if ((empty($attributes["template"]) || !empty($_GET["obj_type"]))
				&& ($_GET["frame"] != "_new" || $_GET["obj_type"] != "MediaObject"))
			{
				// we got a variable content frame (can display different
				// object types (PageObject, MediaObject, GlossarItem)
				// and contains elements for them)

				// determine object type
				if(empty($_GET["obj_type"]))
				{
					$obj_type = "PageObject";
				}
				else
				{
					$obj_type = $_GET["obj_type"];
				}

				// get object specific node
				$childs = $node->child_nodes();
				$found = false;
				foreach($childs as $child)
				{
					if ($child->node_name() == $obj_type)
					{
						$found = true;
						$attributes = $this->attrib2arr($child->attributes());
						$node =& $child;
//echo "<br>2node:".$node->node_name();
						break;
					}
				}
				if (!$found) { echo "ilLMPresentation: No template specified for frame '".
					$_GET["frame"]."' and object type '".$obj_type."'."; exit; }
			}

			// get template
			$in_module = ($attributes["template_location"] == "module")
				? true
				: false;
			if ($in_module)
			{
				$this->tpl = new ilTemplate($attributes["template"], true, true, $in_module);
			}
			else
			{
				$this->tpl =& $tpl;
			}

			// set style sheets
			if (!$this->offlineMode())
			{
				$this->tpl->setVariable("LOCATION_STYLESHEET", ilUtil::getStyleSheetLocation());
			}
			else
			{
				$style_name = $this->ilias->account->prefs["style"].".css";;
				$this->tpl->setVariable("LOCATION_STYLESHEET","./".$style_name);
			}

			$childs = $node->child_nodes();
			foreach($childs as $child)
			{

				$child_attr = $this->attrib2arr($child->attributes());

				switch ($child->node_name())
				{
					case "ilMainMenu":
						$this->ilMainMenu();
						break;

					case "ilTOC":
						$this->ilTOC($child_attr["target_frame"]);
						break;

					case "ilPage":
						switch($this->lm->getType())
						{
							case "lm":
								unset($_SESSION["tr_id"]);
								unset($_SESSION["bib_id"]);
								unset($_SESSION["citation"]);
								$content = $this->ilPage($child);
								break;

							case "dbk":
								$this->setSessionVars();
								if((count($_POST["tr_id"]) > 1) or
								   (!$_POST["target"] and ($_POST["action"] == "show" or $_POST["action"] == "show_citation")))
								{
									$content = $this->lm_gui->showAbstract($_POST["target"]);
								}
								else if($_GET["obj_id"] or ($_POST["action"] == "show") or ($_POST["action"] == "show_citation"))
								{
									// SHOW PAGE IF PAGE WAS SELECTED
									$content = $this->ilPage($child);

									if($_SESSION["tr_id"])
									{
										$translation_content = $this->ilTranslation($child);
									}
								}
								else
								{
									// IF NO PAGE ID IS GIVEN SHOW BOOK/LE ABSTRACT
									$content = $this->lm_gui->showAbstract($_POST["target"]);
								}

								break;
						}
												break;

					case "ilGlossary":
						$content = $this->ilGlossary($child);
						break;

					case "ilLMNavigation":

						// NOT FOR ABSTRACT
						if($_GET["obj_id"] or
						   ((count($_POST["tr_id"]) < 2) and $_POST["target"] and
							($_POST["action"] == "show" or $_POST["action"] == "show_citation")) or
						   $this->lm->getType() == 'lm')
						{
							$this->ilLMNavigation();
						}
						break;

					case "ilMedia":
						$this->ilMedia();
						break;

					case "ilLocator":
						$this->ilLocator();
						break;

					case "ilLMMenu":
						$this->ilLMMenu();
						break;

					case "ilLMSubMenu":
						$this->ilLMSubMenu();
						break;
				}
			}
			$ilBench->stop("ContentPresentation", "layout_processContentTag");
		}
		$content =  $this->tpl->get();

		if ($doShow)
		{
			// (horrible) workaround for preventing template engine
			// from hiding paragraph text that is enclosed
			// in curly brackets (e.g. "{a}", see ilPageObjectGUI::showPage())
			$content =  $this->tpl->get();
			$content = str_replace("&#123;", "{", $content);
			$content = str_replace("&#125;", "}", $content);

			header('Content-type: text/html; charset=UTF-8');
			echo $content;
		}

		$ilBench->stop("ContentPresentation", "layout");

		return($content);
	}

	function fullscreen()
	{
		return $this->layout("fullscreen.xml", !$this->offlineMode());
	}

	function media()
	{
		if ($_GET["frame"] != "_new")
		{
			return $this->layout("main.xml", !$this->offlineMode());
		}
		else
		{
			return $this->layout("fullscreen.xml", !$this->offlineMode());
		}
	}

	function glossary()
	{
		if ($_GET["frame"] != "_new")
		{
			$this->layout();
		}
		else
		{
			$this->tpl = new ilTemplate("tpl.glossary_term_output.html", true, true, true);
			$this->tpl->setVariable("PAGETITLE", " - ".$this->lm->getTitle());

			// set style sheets
			if (!$this->offlineMode())
			{
				$this->tpl->setVariable("LOCATION_STYLESHEET", ilUtil::getStyleSheetLocation());
			}
			else
			{
				$style_name = $this->ilias->account->prefs["style"].".css";;
				$this->tpl->setVariable("LOCATION_STYLESHEET","./".$style_name);
			}

			$this->ilGlossary($child);
			if (!$this->offlineMode())
			{
				$this->tpl->show();
			}
			else
			{
				return $this->tpl->get();
			}
		}
	}

	/**
	* output main menu
	*/
	function ilMainMenu()
	{
		global $ilBench;

		// Determine whether the view of a learning resource should
		// be shown in the frameset of ilias, or in a separate window.
		$showViewInFrameset = $this->ilias->ini->readVariable("layout","view_target") == "frame";


		$ilBench->start("ContentPresentation", "ilMainMenu");
		if ($showViewInFrameset) {
			$menu = new ilMainMenuGUI("bottom", true);
		}
		else
		{
			$menu = new ilMainMenuGUI("_top", true);
		}
		$menu->setTemplate($this->tpl);
		$menu->addMenuBlock("CONTENT", "navigation");
		$menu->setTemplateVars();
		$ilBench->stop("ContentPresentation", "ilMainMenu");
	}

	/**
	* table of contents
	*/
	function ilTOC($a_target)
	{
		global $ilBench;


		$ilBench->start("ContentPresentation", "ilTOC");
		require_once("./content/classes/class.ilLMTOCExplorer.php");
		$exp = new ilLMTOCExplorer($this->getLink($this->lm->getRefId(), "layout", "", $a_target),$this->lm);
		$exp->setTargetGet("obj_id");
		$exp->setFrameTarget($a_target);
		$exp->addFilter("du");
		$exp->addFilter("st");
		$exp->setOfflineMode($this->offlineMode());
		if ($this->lm->getTOCMode() == "pages")
		{
			$exp->addFilter("pg");
		}
		$exp->setFiltered(true);
		$exp->setFilterMode(IL_FM_POSITIVE);

		if ($_GET["lmexpand"] == "")
		{
			$expanded = $this->lm_tree->readRootId();
		}
		else
		{
			$expanded = $_GET["lmexpand"];
		}
		$exp->setExpand($expanded);

		// build html-output
		$exp->setOutput(0);
		$output = $exp->getOutput();

		$this->tpl->setVariable("PAGETITLE", " - ".$this->lm->getTitle());

		// set style sheets
		if (!$this->offlineMode())
		{
			$this->tpl->setVariable("LOCATION_STYLESHEET", ilUtil::getStyleSheetLocation());
		}
		else
		{
			$style_name = $this->ilias->account->prefs["style"].".css";;
			$this->tpl->setVariable("LOCATION_STYLESHEET","./".$style_name);
		}

		$this->tpl->setVariable("TXT_EXPLORER_HEADER", $this->lng->txt("cont_toc"));
		$this->tpl->setVariable("EXPLORER",$output);
		$this->tpl->setVariable("ACTION",
			$this->getLink($this->lm->getRefId(), $_GET["cmd"], "", $_GET["frame"]).
			"&lmexpand=".$_GET["lmexpand"]);
		$this->tpl->parseCurrentBlock();
		$ilBench->stop("ContentPresentation", "ilTOC");
	}

	/**
	* output learning module menu
	*/
	function ilLMMenu()
	{
		$this->tpl->setVariable("MENU", $this->lm_gui->setilLMMenu($this->offlineMode()
			,$this->getExportFormat()));
	}

	/**
	* output learning module submenu
	*/
	function ilLMSubMenu()
	{
		global $rbacsystem;

		$showViewInFrameset = $this->ilias->ini->readVariable("layout","view_target") == "frame";
		if ($showViewInFrameset)
		{
			$buttonTarget = "bottom";
		}
		else
		{
			$buttonTarget = "_top";
		}


		include_once("./classes/class.ilTemplate.php");
		$tpl_menu =& new ilTemplate("tpl.lm_menu.html", true, true, true);

		// edit learning module
		if (!$this->offlineMode())
		{
			if ($rbacsystem->checkAccess("write", $_GET["ref_id"]))
			{
				$tpl_menu->setCurrentBlock("lm_menu_btn");
				$page_id = $this->getCurrentPageId();
				$tpl_menu->setVariable("BTN_LINK", ILIAS_HTTP_PATH."/ilias.php?baseClass=ilLMEditorGUI&ref_id=".$_GET["ref_id"].
					"&obj_id=".$page_id."&to_page=1");
				$tpl_menu->setVariable("BTN_TXT", $this->lng->txt("edit"));
				$tpl_menu->setVariable("BTN_TARGET", $buttonTarget);
				$tpl_menu->parseCurrentBlock();
			}

			$tpl_menu->setCurrentBlock("lm_menu_btn");
			$page_id = $this->getCurrentPageId();
			$tpl_menu->setVariable("BTN_LINK", ILIAS_HTTP_PATH.
				"/goto.php?target=pg_".$page_id."&client_id=".CLIENT_ID);
			$tpl_menu->setVariable("BTN_TXT", $this->lng->txt("cont_page_link"));
			$tpl_menu->setVariable("BTN_TARGET", "_top");
			$tpl_menu->parseCurrentBlock();

		}

		$this->tpl->setVariable("SUBMENU", $tpl_menu->get());
	}

	function ilLocator()
	{
		require_once("content/classes/class.ilStructureObject.php");

		$this->tpl->setCurrentBlock("ilLocator");


		if (empty($_GET["obj_id"]))
		{
			$a_id = $this->lm_tree->getRootId();
		}
		else
		{
			$a_id = $_GET["obj_id"];
		}

		$this->tpl->addBlockFile("STATUSLINE", "statusline", "tpl.statusline.html");
		$this->tpl->addBlockFile("LOCATOR", "locator", "tpl.locator.html");

		if($this->lm_tree->isInTree($a_id))
		{
			$path = $this->lm_tree->getPathFull($a_id);

			// this is a stupid workaround for a bug in PEAR:IT
			$modifier = 1;

			//$modifier = 0;

			$i = 0;
			foreach ($path as $key => $row)
			{
				if ($row["type"] != "pg")
				{

					if ($path[$i + 1]["type"] == "st")
					{
						$this->tpl->touchBlock("locator_separator");
					}

					$this->tpl->setCurrentBlock("locator_item");

					if($row["child"] != $this->lm_tree->getRootId())
					{
						$this->tpl->setVariable("ITEM", ilUtil::shortenText(
							ilStructureObject::_getPresentationTitle($row["child"],
							$this->lm->isActiveNumbering()),50,true));
						// TODO: SCRIPT NAME HAS TO BE VARIABLE!!!
						$this->tpl->setVariable("LINK_ITEM",
							$this->getLink($_GET["ref_id"], "layout", $row["child"], $_GET["frame"], "StructureObject"));
					}
					else
					{
						$this->tpl->setVariable("ITEM", ilUtil::shortenText($this->lm->getTitle(),50,true));
						// TODO: SCRIPT NAME HAS TO BE VARIABLE!!!
						$this->tpl->setVariable("LINK_ITEM",
							$this->getLink($_GET["ref_id"], "layout", "", $_GET["frame"]));
					}

					$this->tpl->parseCurrentBlock();
				}
				$i++;
			}

			/*
			if (isset($_GET["obj_id"]))
			{
				$obj_data =& $this->ilias->obj_factory->getInstanceByObjId($_GET["obj_id"]);

				$this->tpl->setCurrentBlock("locator_item");
				$this->tpl->setVariable("ITEM", $obj_data->getTitle());
				// TODO: SCRIPT NAME HAS TO BE VARIABLE!!!
				$this->tpl->setVariable("LINK_ITEM", "adm_object.php?ref_id=".$_GET["ref_id"]."&obj_id=".$_GET["obj_id"]);
				$this->tpl->parseCurrentBlock();
			}*/
		}
		else		// lonely page
		{
			$this->tpl->touchBlock("locator_separator");

			$this->tpl->setCurrentBlock("locator_item");
			$this->tpl->setVariable("ITEM", $this->lm->getTitle());
			$this->tpl->setVariable("LINK_ITEM",
				$this->getLink($_GET["ref_id"], "layout", "", $_GET["frame"]));
			$this->tpl->parseCurrentBlock();

			$this->tpl->setCurrentBlock("locator_item");
			require_once("content/classes/class.ilLMObjectFactory.php");
			$lm_obj =& ilLMObjectFactory::getInstance($this->lm, $a_id);
			$this->tpl->setVariable("ITEM", $lm_obj->getTitle());
			$this->tpl->setVariable("LINK_ITEM",
				$this->getLink($_GET["ref_id"], "layout", $a_id, $_GET["frame"]));
			$this->tpl->parseCurrentBlock();

			$this->tpl->setCurrentBlock("locator_item");
		}


		$this->tpl->setCurrentBlock("locator");

		if (DEBUG)
		{
			$debug = "DEBUG: <font color=\"red\">".$this->type."::".$this->id."::".$_GET["cmd"]."</font><br/>";
		}

		//$prop_name = $this->objDefinition->getPropertyName($_GET["cmd"],$this->type);


		$this->tpl->setVariable("TXT_LOCATOR",$debug.$this->lng->txt("locator"));

		$this->tpl->parseCurrentBlock();
	}

	function getCurrentPageId()
	{
		global $ilUser;

		// determine object id
		if(empty($_GET["obj_id"]))
		{
			$obj_id = $this->lm_tree->getRootId();
		}
		else
		{
			$obj_id = $_GET["obj_id"];
		}

		// obj_id not in tree -> it is a unassigned page -> return page id
		if (!$this->lm_tree->isInTree($obj_id))
		{
			return $obj_id;
		}

		$curr_node = $this->lm_tree->getNodeData($obj_id);
		
		if($curr_node["type"] == "pg")		// page in tree -> return page id
		{
			$page_id = $curr_node["obj_id"];
		}
		else 		// no page -> search for next page and return its id
		{
			$succ_node = $this->lm_tree->fetchSuccessorNode($obj_id, "pg");
			$page_id = $succ_node["obj_id"];

			if ($succ_node["type"] != "pg")
			{
				$this->tpl = new ilTemplate("tpl.main.html", true, true);
				$this->ilias->raiseError($this->lng->txt("cont_no_page"),$this->ilias->error_obj->FATAL);
				$this->tpl->show();
				exit;
			}

			// if public access get first public page in chapter
			if ($ilUser->getId() == ANONYMOUS_USER_ID and $this->lm_gui->object->getPublicAccessMode() == "selected")
			{
				$public = ilLMObject::_isPagePublic($page_id);

				while ($public === false)
				{
					$succ_node = $this->lm_tree->fetchSuccessorNode($page_id, "pg");
					$page_id = $succ_node["obj_id"];
					$public = ilLMObject::_isPagePublic($page_id);
				}
			}
		}

		return $page_id;
	}

	function mapCurrentPageId($current_page_id)
	{
		$subtree = $this->lm_tree->getSubTree($this->lm_tree->getNodeData(1));
		$node = $this->lm_tree->getNodeData($current_page_id);
		$pos = array_search($node,$subtree);

		$this->tr_obj =& $this->ilias->obj_factory->getInstanceByRefId($_SESSION["tr_id"]);

		$lmtree = new ilTree($this->tr_obj->getId());
		$lmtree->setTableNames('lm_tree','lm_data');
		$lmtree->setTreeTablePK("lm_id");

		$subtree = $lmtree->getSubTree($lmtree->getNodeData(1));

		return $subtree[$pos]["child"];
	}

	function ilTranslation(&$a_page_node)
	{
		global $ilUser;

		require_once("content/classes/Pages/class.ilPageObjectGUI.php");
		require_once("content/classes/class.ilLMPageObject.php");

		$page_id = $this->mapCurrentPageId($this->getCurrentPageId());

		if(!$page_id)
		{
			$this->tpl->setVariable("TRANSLATION_CONTENT","NO TRNSLATION FOUND");
			return false;
		}

		$page_object =& new ilPageObject($this->lm->getType(), $page_id);
		$page_object_gui =& new ilPageObjectGUI($page_object);

		// Update personal desktop items
		$this->ilias->account->setDesktopItemParameters($_SESSION["tr_id"], $this->lm->getType(),$page_id);

		// Update course items
		include_once './course/classes/class.ilCourseLMHistory.php';

		ilCourseLMHistory::_updateLastAccess($ilUser->getId(),$this->lm->getRefId(),$page_id);

		// read link targets
		$targets = $this->getLayoutLinkTargets();

		$lm_pg_obj =& new ilLMPageObject($this->lm, $page_id);
		$lm_pg_obj->setLMId($_SESSION["tr_id"]);
		//$pg_obj->setParentId($this->lm->getId());
		#$page_object_gui->setLayoutLinkTargets($targets);

		// USED FOR DBK PAGE TURNS
		$page_object_gui->setBibId($_SESSION["bib_id"]);

		// determine target frames for internal links
		//$pg_frame = $_GET["frame"];
		$page_object_gui->setLinkFrame($_GET["frame"]);
		$page_object_gui->setOutputMode("presentation");
		$page_object_gui->setOutputSubmode("translation");

		$page_object_gui->setPresentationTitle(
			ilLMPageObject::_getPresentationTitle($lm_pg_obj->getId(),
			$this->lm->getPageHeader(), $this->lm->isActiveNumbering()));
#		$page_object_gui->setLinkParams("ref_id=".$this->lm->getRefId());
		$page_object_gui->setLinkParams("ref_id=".$_SESSION["tr_id"]);
		$page_object_gui->setTemplateTargetVar("PAGE_CONTENT");
		$page_object_gui->setTemplateOutputVar("TRANSLATION_CONTENT");


		return $page_object_gui->presentation();

	}

	function ilCitation()
	{
		$page_id = $this->getCurrentPageId();
		$this->tpl = new ilTemplate("tpl.page.html",true,true,true);
		$this->ilLocator();
		$this->tpl->setVariable("MENU",$this->lm_gui->setilCitationMenu());

		include_once("content/classes/Pages/class.ilPageObject.php");

		$this->pg_obj =& new ilPageObject($this->lm->getType(),$page_id);
		$xml = $this->pg_obj->getXMLContent();
		$this->lm_gui->showCitation($xml);
		$this->tpl->show();
	}


	function getLayoutLinkTargets()
	{

		if (!is_object($this->layout_doc))
			return array ();

		$xpc = xpath_new_context($this->layout_doc);

		$path = "/ilLayout/ilLinkTargets/LinkTarget";
		$res = xpath_eval($xpc, $path);
		$targets = array();
		for ($i = 0; $i < count($res->nodeset); $i++)
		{
			$type = $res->nodeset[$i]->get_attribute("Type");
			$frame = $res->nodeset[$i]->get_attribute("Frame");
			$targets[$type] = array("Type" => $type, "Frame" => $frame);
		}
		return $targets;
	}

	/**
	* process <ilPage> content tag
	*/
	function ilPage(&$a_page_node)
	{
		global $ilBench,$ilUser;

		if ($ilUser->getId() == ANONYMOUS_USER_ID and $this->lm_gui->object->getPublicAccessMode() == "selected")
		{
			$public = ilLMObject::_isPagePublic($this->getCurrentPageId());

			if (!$public)
				return $this->showNoPublicAccess($this->getCurrentPageId());
		}

		if (!ilObjContentObject::_checkPreconditionsOfPage($this->lm->getId(), $this->getCurrentPageId()))
		{
			return $this->showPreconditionsOfPage($this->getCurrentPageId());
		}

		$ilBench->start("ContentPresentation", "ilPage");

		require_once("content/classes/Pages/class.ilPageObjectGUI.php");
		require_once("content/classes/class.ilLMPageObject.php");
		$page_id = $this->getCurrentPageId();
		$page_object =& new ilPageObject($this->lm->getType(), $page_id);
		$page_object->buildDom();
		$page_object->registerOfflineHandler($this);
		$int_links = $page_object->getInternalLinks();
		$page_object_gui =& new ilPageObjectGUI($page_object);

		// Update personal desktop items
		$this->ilias->account->setDesktopItemParameters($this->lm->getRefId(), $this->lm->getType(), $page_id);

		// Update course items
		include_once './course/classes/class.ilCourseLMHistory.php';

		ilCourseLMHistory::_updateLastAccess($ilUser->getId(),$this->lm->getRefId(),$page_id);

		// read link targets
		$link_xml = $this->getLinkXML($int_links, $this->getLayoutLinkTargets());

		$lm_pg_obj =& new ilLMPageObject($this->lm, $page_id);
		$lm_pg_obj->setLMId($this->lm->getId());
		//$pg_obj->setParentId($this->lm->getId());
		$page_object_gui->setLinkXML($link_xml);

		// USED FOR DBK PAGE TURNS
		$page_object_gui->setBibId($_SESSION["bib_id"]);
		$page_object_gui->enableCitation((bool) $_SESSION["citation"]);

		// determine target frames for internal links
		//$pg_frame = $_GET["frame"];
		$page_object_gui->setLinkFrame($_GET["frame"]);
		if (!$this->offlineMode())
		{
			$page_object_gui->setOutputMode("presentation");
		}
		else
		{
			$page_object_gui->setOutputMode("offline");
		}		
		$page_object_gui->setFileDownloadLink($this->getLink($_GET["ref_id"], "downloadFile"));
		$page_object_gui->setFullscreenLink($this->getLink($_GET["ref_id"], "fullscreen"));
		$page_object_gui->setPresentationTitle(
			ilLMPageObject::_getPresentationTitle($lm_pg_obj->getId(),
			$this->lm->getPageHeader(), $this->lm->isActiveNumbering()));

		// ADDED FOR CITATION
		$page_object_gui->setLinkParams("ref_id=".$this->lm->getRefId());
		$page_object_gui->setTemplateTargetVar("PAGE_CONTENT");
		$page_object_gui->setSourcecodeDownloadScript($this->getSourcecodeDownloadLink());

		if($_SESSION["tr_id"])
		{
			$page_object_gui->setOutputSubmode("translation");
		}

		// content style
		$this->tpl->setCurrentBlock("ContentStyle");
		if (!$this->offlineMode())
		{
			$this->tpl->setVariable("LOCATION_CONTENT_STYLESHEET",
				ilObjStyleSheet::getContentStylePath($this->lm->getStyleSheetId()));
		}
		else
		{
			$this->tpl->setVariable("LOCATION_CONTENT_STYLESHEET", "content.css");
		}
		$this->tpl->parseCurrentBlock();

		// syntax style
		$this->tpl->setCurrentBlock("SyntaxStyle");
		if (!$this->offlineMode())
		{
			$this->tpl->setVariable("LOCATION_SYNTAX_STYLESHEET",
				ilObjStyleSheet::getSyntaxStylePath());
		}
		else
		{
			$this->tpl->setVariable("LOCATION_SYNTAX_STYLESHEET",
				"syntaxhighlight.css");
		}
		$this->tpl->parseCurrentBlock();

		// track user access to page
		require_once "./tracking/classes/class.ilUserTracking.php";
		ilUserTracking::_trackAccess($this->lm->getId(), $this->lm->getType(),
			$page_id, "pg", "read");

		$ilBench->stop("ContentPresentation", "ilPage");
		
		return $page_object_gui->presentation($page_object_gui->getOutputMode());

	}

	/**
	* show preconditions of the page
	*/
	function showPreconditionsOfPage()
	{
		global $ilBench;

		$ilBench->start("ContentPresentation", "showPagePreconditions");
		$conds = ilObjContentObject::_getMissingPreconditionsOfPage($this->lm->getId(), $this->getCurrentPageId());
		$topchap = ilObjContentObject::_getMissingPreconditionsTopChapter($this->lm->getId(), $this->getCurrentPageId());

		$page_id = $this->getCurrentPageId();

		// content style
		$this->tpl->setCurrentBlock("ContentStyle");
		if (!$this->offlineMode())
		{
			$this->tpl->setVariable("LOCATION_CONTENT_STYLESHEET",
				ilObjStyleSheet::getContentStylePath($this->lm->getStyleSheetId()));
		}
		else
		{
			$this->tpl->setVariable("LOCATION_CONTENT_STYLESHEET", "content.css");
		}
		$this->tpl->parseCurrentBlock();

		$this->tpl->addBlockFile("PAGE_CONTENT", "pg_content", "tpl.page_preconditions.html", true);
		
		// list all missing preconditions
		include_once("classes/class.ilRepositoryExplorer.php");
		foreach($conds as $cond)
		{
			$obj_link = "../".ilRepositoryExplorer::buildLinkTarget($cond["trigger_ref_id"],$cond["trigger_type"]);
			$obj_frame = ilRepositoryExplorer::buildFrameTarget($cond["trigger_type"],$cond["trigger_ref_id"],$cond["trigger_obj_id"]);
			$this->tpl->setCurrentBlock("condition");
			$this->tpl->setVariable("ROWCOL", $rc = ($rc != "tblrow2") ? "tblrow2" : "tblrow1");
			$this->tpl->setVariable("VAL_ITEM", ilObject::_lookupTitle($cond["trigger_obj_id"]));
			$this->tpl->setVariable("LINK_ITEM", $obj_link);
			$this->tpl->setVariable("FRAME_ITEM", $obj_frame);
			if ($cond["operator"] == "passed")
			{
				$cond_str = $this->lng->txt("passed");
			}
			else
			{
				$cond_str = $cond["operator"];
			}
			$this->tpl->setVariable("VAL_CONDITION", $cond_str." ".$cond["value"]);
			$this->tpl->parseCurrentBlock();
		}
		$this->tpl->setCurrentBlock("pg_content");
		
		//include_once("content/classes/class.ilLMObject.php");
		$this->tpl->setVariable("TXT_MISSING_PRECONDITIONS", 
			sprintf($this->lng->txt("cont_missing_preconditions"),
			ilLMObject::_lookupTitle($topchap)));
		$this->tpl->setVariable("TXT_ITEM", $this->lng->txt("item"));
		$this->tpl->setVariable("TXT_CONDITION", $this->lng->txt("condition"));
		
		// output skip chapter link
		$parent = $this->lm_tree->getParentId($topchap);
		$childs = $this->lm_tree->getChildsByType($parent, "st");
		$next = "";
		$j=-2; $i=1; 
		foreach($childs as $child)
		{
			if ($child["child"] == $topchap)
			{
				$j = $i;
			}
			if ($i++ == ($j+1))
			{
				$succ_node = $this->lm_tree->fetchSuccessorNode($child["child"], "pg");
			}
		}
		if($succ_node != "")
		{
			$framestr = (!empty($_GET["frame"]))
				? "frame=".$_GET["frame"]."&"
				: "";
			$showViewInFrameset = $this->ilias->ini->readVariable("layout","view_target") == "frame";
			$link = "<br /><a href=\"".
				$this->getLink($this->lm->getRefId(), "layout", $succ_node["obj_id"], $_GET["frame"]).
				"\">".$this->lng->txt("cont_skip_chapter")."</a>";
			$this->tpl->setVariable("LINK_SKIP_CHAPTER", $link);
		}
		
		$this->tpl->parseCurrentBlock();
		
		$ilBench->stop("ContentPresentation", "showPagePreconditions");
	}
	
	/**
	* get xml for links
	*/
	function getLinkXML($a_int_links, $a_layoutframes)
	{
		// Determine whether the view of a learning resource should
		// be shown in the frameset of ilias, or in a separate window.
		$showViewInFrameset = $this->ilias->ini->readVariable("layout","view_target") == "frame";

		if ($a_layoutframes == "")
		{
			$a_layoutframes = array();
		}
		$link_info = "<IntLinkInfos>";
		foreach ($a_int_links as $int_link)
		{
			$target = $int_link["Target"];
			if (substr($target, 0, 4) == "il__")
			{
				$target_arr = explode("_", $target);
				$target_id = $target_arr[count($target_arr) - 1];
				$type = $int_link["Type"];
				$targetframe = ($int_link["TargetFrame"] != "")
					? $int_link["TargetFrame"]
					: "None";

				switch($type)
				{
					case "PageObject":
					case "StructureObject":
						$lm_id = ilLMObject::_lookupContObjID($target_id);
						if ($lm_id == $this->lm->getId() ||
							($targetframe != "None" && $targetframe != "New"))
						{
							$ltarget = $a_layoutframes[$targetframe]["Frame"];
							//$nframe = ($ltarget == "")
							//	? $_GET["frame"]
							//	: $ltarget;
							$nframe = ($ltarget == "")
								? ""
								: $ltarget;
							if ($ltarget == "")
							{
								if ($showViewInFrameset) {
									$ltarget="_parent";
								} else {
									$ltarget="_top";
								}
							}
							// scorm always in 1window view and link target
							// is always same frame
							if ($this->getExportFormat() == "scorm" &&
								$this->offlineMode())
							{
								$ltarget = "";
							}
							$href =
								$this->getLink($_GET["ref_id"], "layout", $target_id, $nframe, $type);
						}
						else
						{
							if ($type == "PageObject")
							{
								$href = "../goto.php?target=pg_".$target_id;
							}
							else
							{
								$href = "../goto.php?target=st_".$target_id;
							}
							$ltarget = "ilContObj".$lm_id;
						}
						break;

					case "GlossaryItem":
						if ($targetframe == "None")
						{
							$targetframe = "Glossary";
						}
						$ltarget = $a_layoutframes[$targetframe]["Frame"];
						$nframe = ($ltarget == "")
							? $_GET["frame"]
							: $ltarget;
						$href =
							$this->getLink($_GET["ref_id"], $a_cmd = "glossary", $target_id, $nframe, $type);
						break;

					case "MediaObject":
						$ltarget = $a_layoutframes[$targetframe]["Frame"];
						$nframe = ($ltarget == "")
							? $_GET["frame"]
							: $ltarget;
						$href =
							$this->getLink($_GET["ref_id"], $a_cmd = "media", $target_id, $nframe, $type);
						break;

					case "RepositoryItem":
						$obj_type = ilObject::_lookupType($target_id, true);
						$obj_id = ilObject::_lookupObjId($target_id);
						$href = "../goto.php?target=".$obj_type."_".$target_id;
						$t_frame = ilFrameTargetInfo::_getFrame("MainContent", $obj_type);
						$ltarget = $t_frame;
						break;

				}
				$link_info.="<IntLinkInfo Target=\"$target\" Type=\"$type\" ".
					"TargetFrame=\"$targetframe\" LinkHref=\"$href\" LinkTarget=\"$ltarget\" />";

				// set equal link info for glossary links of target "None" and "Glossary"
				/*
				if ($targetframe=="None" && $type=="GlossaryItem")
				{
					$link_info.="<IntLinkInfo Target=\"$target\" Type=\"$type\" ".
						"TargetFrame=\"Glossary\" LinkHref=\"$href\" LinkTarget=\"$ltarget\" />";
				}*/
			}
		}
		$link_info.= "</IntLinkInfos>";

		return $link_info;
	}


	/**
	* show glossary term
	*/
	function ilGlossary()
	{
		global $ilBench;

		$ilBench->start("ContentPresentation", "ilGlossary");

		//require_once("content/classes/Pages/class.ilPageObjectGUI.php");
		//require_once("content/classes/class.ilLMPageObject.php");

		require_once("content/classes/class.ilGlossaryTermGUI.php");
		$term_gui =& new ilGlossaryTermGUI($_GET["obj_id"]);

		// content style
		$this->tpl->setCurrentBlock("ContentStyle");
		if (!$this->offlineMode())
		{
			$this->tpl->setVariable("LOCATION_CONTENT_STYLESHEET",
				ilObjStyleSheet::getContentStylePath($this->lm->getStyleSheetId()));
		}
		else
		{
			$this->tpl->setVariable("LOCATION_CONTENT_STYLESHEET", "content.css");
		}
		$this->tpl->parseCurrentBlock();

		// syntax style
		$this->tpl->setCurrentBlock("SyntaxStyle");

		if (!$this->offlineMode())
		{
			$this->tpl->setVariable("LOCATION_SYNTAX_STYLESHEET",
				ilObjStyleSheet::getSyntaxStylePath());
		}
		else
		{
			$this->tpl->setVariable("LOCATION_SYNTAX_STYLESHEET",
				"syntaxhighlight.css");
		}
		$this->tpl->parseCurrentBlock();

		$int_links = $term_gui->getInternalLinks();
		$link_xml = $this->getLinkXML($int_links, $this->getLayoutLinkTargets());
		$term_gui->setLinkXML($link_xml);

		$term_gui->output($this->offlineMode());

		$ilBench->stop("ContentPresentation", "ilGlossary");
	}

	/**
	* output media
	*/
	function ilMedia()
	{
		global $ilBench;

		$ilBench->start("ContentPresentation", "ilMedia");

		$this->tpl->setCurrentBlock("ContentStyle");
		if (!$this->offlineMode())
		{
			$this->tpl->setVariable("LOCATION_CONTENT_STYLESHEET",
				ilObjStyleSheet::getContentStylePath($this->lm->getStyleSheetId()));
		}
		else
		{
			$this->tpl->setVariable("LOCATION_CONTENT_STYLESHEET", "content.css");
		}
		$this->tpl->parseCurrentBlock();

		$this->tpl->setVariable("PAGETITLE", " - ".$this->lm->getTitle());
		
		// set style sheets
		if (!$this->offlineMode())
		{
			$this->tpl->setVariable("LOCATION_STYLESHEET", ilUtil::getStyleSheetLocation());
		}
		else
		{
			$style_name = $this->ilias->account->prefs["style"].".css";;
			$this->tpl->setVariable("LOCATION_STYLESHEET","./".$style_name);
		}

		$this->tpl->setCurrentBlock("ilMedia");

		//$int_links = $page_object->getInternalLinks();
		$med_links = ilMediaItem::_getMapAreasIntLinks($_GET["mob_id"]);
		$link_xml = $this->getLinkXML($med_links, $this->getLayoutLinkTargets());
//echo "<br><br>".htmlentities($link_xml);
		require_once("content/classes/Media/class.ilObjMediaObject.php");
		$media_obj =& new ilObjMediaObject($_GET["mob_id"]);
		if (!empty ($_GET["pg_id"]))
		{
			require_once("content/classes/Pages/class.ilPageObject.php");
			$pg_obj =& new ilPageObject($this->lm->getType(), $_GET["pg_id"]);
			$pg_obj->buildDom();

			$xml = "<dummy>";
			// todo: we get always the first alias now (problem if mob is used multiple
			// times in page)
			$xml.= $pg_obj->getMediaAliasElement($_GET["mob_id"]);
			$xml.= $media_obj->getXML(IL_MODE_OUTPUT);
			$xml.= $link_xml;
			$xml.="</dummy>";
		}
		else
		{
			$xml = "<dummy>";
			// todo: we get always the first alias now (problem if mob is used multiple
			// times in page)
			$xml.= $media_obj->getXML(IL_MODE_ALIAS);
			$xml.= $media_obj->getXML(IL_MODE_OUTPUT);
			$xml.= $link_xml;
			$xml.="</dummy>";
		}

//echo htmlentities($xml); exit;

		// todo: utf-header should be set globally
		//header('Content-type: text/html; charset=UTF-8');

		$xsl = file_get_contents("./content/page.xsl");
		$args = array( '/_xml' => $xml, '/_xsl' => $xsl );
		$xh = xslt_create();

//echo "<b>XML:</b>".htmlentities($xml);
		// determine target frames for internal links
		//$pg_frame = $_GET["frame"];
		if (!$this->offlineMode())
		{
			$wb_path = ilUtil::getWebspaceDir("output");
		}
		else
		{
			$wb_path = ".";
		}
//		$wb_path = "../".$this->ilias->ini->readVariable("server","webspace_dir");
		$mode = ($_GET["cmd"] == "fullscreen")
			? "fullscreen"
			: "media";
		$enlarge_path = ilUtil::getImagePath("enlarge.gif", false, "output", $this->offlineMode());
		$fullscreen_link =
			$this->getLink($this->lm->getRefId(), "fullscreen");
		$params = array ('mode' => $mode, 'enlarge_path' => $enlarge_path,
			'link_params' => "ref_id=".$this->lm->getRefId(),'fullscreen_link' => $fullscreen_link,
			'ref_id' => $this->lm->getRefId(), 'pg_frame' => $pg_frame, 'webspace_path' => $wb_path);
		$output = xslt_process($xh,"arg:/_xml","arg:/_xsl",NULL,$args, $params);
		echo xslt_error($xh);
		xslt_free($xh);

		// unmask user html
		$this->tpl->setVariable("MEDIA_CONTENT", $output);

		$ilBench->stop("ContentPresentation", "ilMedia");
	}


	/**
	* inserts sequential learning module navigation
	* at template variable LMNAVIGATION_CONTENT
	*/
	function ilLMNavigation()
	{
		global $ilBench,$ilUser;

		$ilBench->start("ContentPresentation", "ilLMNavigation");

		$page_id = $this->getCurrentPageId();

		if(empty($page_id))
		{
			return;
		}

		if(!$this->lm_tree->isInTree($page_id))
		{
			return;
		}

		$ilBench->start("ContentPresentation", "ilLMNavigation_fetchSuccessor");
		$succ_node = $this->lm_tree->fetchSuccessorNode($page_id, "pg");
		$ilBench->stop("ContentPresentation", "ilLMNavigation_fetchSuccessor");

		$succ_str = ($succ_node !== false)
			? " -> ".$succ_node["obj_id"]."_".$succ_node["type"]
			: "";

		$ilBench->start("ContentPresentation", "ilLMNavigation_fetchPredecessor");
		$pre_node = $this->lm_tree->fetchPredecessorNode($page_id, "pg");
		$ilBench->stop("ContentPresentation", "ilLMNavigation_fetchPredecessor");

		$pre_str = ($pre_node !== false)
			? $pre_node["obj_id"]."_".$pre_node["type"]." -> "
			: "";

		// determine target frame
		$framestr = (!empty($_GET["frame"]))
			? "frame=".$_GET["frame"]."&"
			: "";


		// Determine whether the view of a learning resource should
		// be shown in the frameset of ilias, or in a separate window.
		$showViewInFrameset = $this->ilias->ini->readVariable("layout","view_target") == "frame";

		if($pre_node != "")
		{
			$ilBench->start("ContentPresentation", "ilLMNavigation_outputPredecessor");
			$this->tpl->setCurrentBlock("ilLMNavigation_Prev");

			// get page object
			//$ilBench->start("ContentPresentation", "ilLMNavigation_getPageObject");
			//$pre_page =& new ilLMPageObject($this->lm, $pre_node["obj_id"]);
			//$pre_page->setLMId($this->lm->getId());
			//$ilBench->stop("ContentPresentation", "ilLMNavigation_getPageObject");

			// get presentation title
			$ilBench->start("ContentPresentation", "ilLMNavigation_getPresentationTitle");
			$pre_title = ilLMPageObject::_getPresentationTitle($pre_node["obj_id"],
				$this->lm->getPageHeader(), $this->lm->isActiveNumbering());
			$prev_img = "<img src=\"".
				ilUtil::getImagePath("nav_arr_L.gif", false, "output", $this->offlineMode())."\" border=\"0\"/>";
			if (!$this->lm->cleanFrames())
			{
				$output = "<a href=\"".
					$this->getLink($this->lm->getRefId(), "layout", $pre_node["obj_id"], $_GET["frame"]).
					"\">$prev_img ".ilUtil::shortenText($pre_title, 50, true)."</a>";
			}
			else if ($showViewInFrameset)
			{
				$output = "<a href=\"".
					$this->getLink($this->lm->getRefId(), "layout", $pre_node["obj_id"]).
					"\" target=\"bottom\">$prev_img ".ilUtil::shortenText($pre_title, 50, true)."</a>";
			}
			else
			{
				$output = "<a href=\"".
					$this->getLink($this->lm->getRefId(), "layout", $pre_node["obj_id"]).
					"\" target=\"_top\">$prev_img ".ilUtil::shortenText($pre_title, 50, true)."</a>";
			}
			
			if ($ilUser->getId() == ANONYMOUS_USER_ID and ($this->lm->getPublicAccessMode() == "selected" and !ilLMObject::_isPagePublic($pre_node["obj_id"])))
			{
				$output = $this->lng->txt("msg_page_not_public");
			}
			
			$ilBench->stop("ContentPresentation", "ilLMNavigation_getPresentationTitle");

			$this->tpl->setVariable("LMNAVIGATION_PREV", $output);
			$this->tpl->parseCurrentBlock();
			$this->tpl->setCurrentBlock("ilLMNavigation_Prev2");
			$this->tpl->setVariable("LMNAVIGATION_PREV2", $output);
			$this->tpl->parseCurrentBlock();
			$ilBench->stop("ContentPresentation", "ilLMNavigation_outputPredecessor");
		}
		if($succ_node != "")
		{
			$ilBench->start("ContentPresentation", "ilLMNavigation_outputSuccessor");
			$this->tpl->setCurrentBlock("ilLMNavigation_Next");

			// get page object
			//$ilBench->start("ContentPresentation", "ilLMNavigation_getPageObject");
			//$succ_page =& new ilLMPageObject($this->lm, $succ_node["obj_id"]);
			//$ilBench->stop("ContentPresentation", "ilLMNavigation_getPageObject");
			//$succ_page->setLMId($this->lm->getId());

			// get presentation title
			$ilBench->start("ContentPresentation", "ilLMNavigation_getPresentationTitle");
			$succ_title = ilLMPageObject::_getPresentationTitle($succ_node["obj_id"],
			$this->lm->getPageHeader(), $this->lm->isActiveNumbering());
			$succ_img = "<img src=\"".
				ilUtil::getImagePath("nav_arr_R.gif", false, "output", $this->offlineMode())."\" border=\"0\"/>";
			if (!$this->lm->cleanFrames())
			{
				$output = " <a href=\"".
					$this->getLink($this->lm->getRefId(), "layout", $succ_node["obj_id"], $_GET["frame"]).
					"\">".ilUtil::shortenText($succ_title,50,true)." $succ_img</a>";
			}
			else if ($showViewInFrameset)
			{
				$output = " <a href=\"".
					$this->getLink($this->lm->getRefId(), "layout", $succ_node["obj_id"]).
					"\" target=\"bottom\">".ilUtil::shortenText($succ_title,50,true)." $succ_img</a>";
			}
			else
			{
				$output = " <a href=\"".
					$this->getLink($this->lm->getRefId(), "layout", $succ_node["obj_id"]).
					"\" target=\"_top\">".ilUtil::shortenText($succ_title,50,true)." $succ_img</a>";
			}
			
			if ($ilUser->getId() == ANONYMOUS_USER_ID and ($this->lm->getPublicAccessMode() == "selected" and !ilLMObject::_isPagePublic($succ_node["obj_id"])))
			{
				$output = $this->lng->txt("msg_page_not_public");
			}

			$ilBench->stop("ContentPresentation", "ilLMNavigation_getPresentationTitle");

			$this->tpl->setVariable("LMNAVIGATION_NEXT", $output);
			$this->tpl->parseCurrentBlock();
			$this->tpl->setCurrentBlock("ilLMNavigation_Next2");
			$this->tpl->setVariable("LMNAVIGATION_NEXT2", $output);
			$this->tpl->parseCurrentBlock();
			$ilBench->stop("ContentPresentation", "ilLMNavigation_outputSuccessor");
		}

		$ilBench->stop("ContentPresentation", "ilLMNavigation");
	}


	function processNodes(&$a_content, &$a_node)
	{
		$child_nodes = $a_node->child_nodes();
		foreach ($child_nodes as $child)
		{
			if($child->node_name() == "ilFrame")
			{
				$attributes = $this->attrib2arr($child->attributes());
				// node is frameset, if it has cols or rows attribute
				if ((!empty($attributes["rows"])) || (!empty($attrubtes["cols"])))
				{
					// if framset has name, another http request is necessary
					// (html framesets don't have names, so we need a wrapper frame)
					if(!empty($attributes["name"]))
					{
						unset($attributes["template"]);
						unset($attributes["template_location"]);
						$attributes["src"] =
							$this->getLink($this->lm->getRefId(), "layout", $_GET["obj_id"], $attributes["name"]);
						$a_content .= $this->buildTag("", "frame", $attributes);
						$this->frames[$attributes["name"]] = $attributes["name"];
//echo "<br>processNodes:add1 ".$attributes["name"];
					}
					else	// ok, no name means that we can easily output the frameset tag
					{
						$a_content .= $this->buildTag("start", "frameset", $attributes);
						$this->processNodes($a_content, $child);
						$a_content .= $this->buildTag("end", "frameset");
					}
				}
				else	// frame with
				{
					unset($attributes["template"]);
					unset($attributes["template_location"]);
					$attributes["src"] =
						$this->getLink($this->lm->getRefId(), "layout", $_GET["obj_id"], $attributes["name"]);
					$a_content .= $this->buildTag("", "frame", $attributes);
					$this->frames[$attributes["name"]] = $attributes["name"];
//echo "<br>processNodes:add2 ".$attributes["name"];
					//$a_content .= "<frame name=\"".$attributes["name"]."\" ".
					//	"src=\"lm_presentation.php?ref_id=".$this->lm->getRefId()."&cmd=layout&frame=".$attributes["name"]."&obj_id=".$_GET["obj_id"]."\" />\n";
				}
			}
		}
	}

	/**
	* generate a tag with given name and attributes
	*
	* @param	string		"start" | "end" | "" for starting or ending tag or complete tag
	* @param	string		element/tag name
	* @param	array		array of attributes
	*/
	function buildTag ($type, $name, $attr="")
	{
		$tag = "<";

		if ($type == "end")
			$tag.= "/";

		$tag.= $name;

		if (is_array($attr))
		{
			while (list($k,$v) = each($attr))
				$tag.= " ".$k."=\"$v\"";
		}

		if ($type == "")
			$tag.= "/";

		$tag.= ">\n";

		return $tag;
	}


	/**
	* table of contents
	*/
	function showTableOfContents()
	{
		global $ilBench;

		$ilBench->start("ContentPresentation", "TableOfContents");

		//$this->tpl = new ilTemplate("tpl.lm_toc.html", true, true, true);
		$this->tpl->setCurrentBlock("ContentStyle");
		if (!$this->offlineMode())
		{
			$this->tpl->setVariable("LOCATION_CONTENT_STYLESHEET",
				ilObjStyleSheet::getContentStylePath($this->lm->getStyleSheetId()));
		}
		else
		{
			$this->tpl->setVariable("LOCATION_CONTENT_STYLESHEET", "content.css");
		}
		$this->tpl->parseCurrentBlock();

		$this->tpl->setVariable("PAGETITLE", " - ".$this->lm->getTitle());

		// set style sheets
		if (!$this->offlineMode())
		{
			$this->tpl->setVariable("LOCATION_STYLESHEET", ilUtil::getStyleSheetLocation());
		}
		else
		{
			$style_name = $this->ilias->account->prefs["style"].".css";;
			$this->tpl->setVariable("LOCATION_STYLESHEET","./".$style_name);
		}

		$this->tpl->addBlockFile("CONTENT", "content", "tpl.lm_toc.html", true);

		// set title header
		$this->tpl->setVariable("HEADER", $this->lm->getTitle());


		include_once ("content/classes/class.ilLMTableOfContentsExplorer.php");
		$exp = new ilTableOfContentsExplorer(
			"lm_presentation.php?ref_id=".$_GET["ref_id"]
			, $this->lm, $this->getExportFormat());
		$exp->setTargetGet("obj_id");
		$exp->setOfflineMode($this->offlineMode());

		$tree =& $this->lm->getTree();
		if ($_GET["lmtocexpand"] == "")
		{
			$expanded = $tree->readRootId();
		}
		else
		{
			$expanded = $_GET["lmtocexpand"];
		}

		$exp->setExpand($expanded);

		// build html-output
		$exp->setOutput(0);
		$output = $exp->getOutput();

		$this->tpl->setVariable("EXPLORER", $output);
		$this->tpl->parseCurrentBlock();

		if ($this->offlineMode())
		{
			return $this->tpl->get();
		}
		else
		{
			$this->tpl->show();
		}

		$ilBench->stop("ContentPresentation", "TableOfContents");
	}

	/**
	* show selection screen for print view
	*/
	function showPrintViewSelection()
	{
		global $ilBench,$ilUser;

		include_once("content/classes/class.ilStructureObject.php");

		$ilBench->start("ContentPresentation", "PrintViewSelection");

		//$this->tpl = new ilTemplate("tpl.lm_toc.html", true, true, true);
		$this->tpl->setCurrentBlock("ContentStyle");
		if (!$this->offlineMode())
		{
			$this->tpl->setVariable("LOCATION_CONTENT_STYLESHEET",
				ilObjStyleSheet::getContentStylePath($this->lm->getStyleSheetId()));
		}
		else
		{
			$this->tpl->setVariable("LOCATION_CONTENT_STYLESHEET", "content.css");
		}
		$this->tpl->parseCurrentBlock();

		$this->tpl->setVariable("PAGETITLE", " - ".$this->lm->getTitle());
		$this->tpl->setVariable("LOCATION_STYLESHEET", ilUtil::getStyleSheetLocation());
		$this->tpl->addBlockFile("CONTENT", "content", "tpl.lm_print_selection.html", true);

		// set title header
		$this->tpl->setVariable("HEADER", $this->lm->getTitle());
		$this->tpl->setVariable("TXT_SHOW_PRINT", $this->lng->txt("cont_show_print_view"));
		$this->tpl->setVariable("TXT_BACK", $this->lng->txt("back"));
		$this->tpl->setVariable("LINK_BACK",
			"lm_presentation.php?ref_id=".$_GET["ref_id"]."&obj_id=".$_GET["obj_id"]);

		$this->tpl->setVariable("FORMACTION", "lm_presentation.php?ref_id=".$_GET["ref_id"]
			."&obj_id=".$_GET["obj_id"]."&cmd=post");

		$nodes = $this->lm_tree->getSubtree($this->lm_tree->getNodeData($this->lm_tree->getRootId()));

		if (!is_array($_POST["item"]))
		{
			if ($_GET["obj_id"] != "")
			{
				$_POST["item"][$_GET["obj_id"]] = "y";
			}
			else
			{
				$_POST["item"][1] = "y";
			}
		}

		foreach ($nodes as $node)
		{

			// indentation
			for ($i=0; $i<$node["depth"]; $i++)
			{
				$this->tpl->setCurrentBlock("indent");
				$this->tpl->setVariable("IMG_BLANK", ilUtil::getImagePath("browser/blank.gif"));
				$this->tpl->parseCurrentBlock();
			}

			// output title
			$this->tpl->setCurrentBlock("lm_item");

			switch ($node["type"])
			{
				// page
				case "pg":
					$this->tpl->setVariable("TXT_TITLE",
					ilLMPageObject::_getPresentationTitle($node["obj_id"],
					$this->lm->getPageHeader(), $this->lm->isActiveNumbering()));
					
					if ($ilUser->getId() == ANONYMOUS_USER_ID and $this->lm_gui->object->getPublicAccessMode() == "selected")
					{
						if (!ilLMObject::_isPagePublic($node["obj_id"]))
						{
							$this->tpl->setVariable("DISABLED", "disabled=\"disabled\"");
							$this->tpl->setVariable("TXT_NO_ACCESS", "(".$this->lng->txt("cont_no_access").")");
						}
					}
					$this->tpl->setVariable("IMG_TYPE", ilUtil::getImagePath("icon_pg.gif"));
					break;

				// learning module
				case "du":
					$this->tpl->setVariable("TXT_TITLE", "<b>".$this->lm->getTitle()."</b>");
					$this->tpl->setVariable("IMG_TYPE", ilUtil::getImagePath("icon_lm.gif"));
					break;

				// chapter
				case "st":
					/*
					$this->tpl->setVariable("TXT_TITLE", "<b>".
						ilStructureObject::_getPresentationTitle($node["obj_id"],
						$this->lm->getPageHeader(), $this->lm->isActiveNumbering())
						."</b>");*/
					$this->tpl->setVariable("TXT_TITLE", "<b>".
						ilStructureObject::_getPresentationTitle($node["obj_id"],
						$this->lm->isActiveNumbering())
						."</b>");
					$this->tpl->setVariable("IMG_TYPE", ilUtil::getImagePath("icon_st.gif"));

					break;
			}
			
			if (!ilObjContentObject::_checkPreconditionsOfPage($this->lm->getId(), $node["obj_id"]))
			{
				$this->tpl->setVariable("TXT_NO_ACCESS", "(".$this->lng->txt("cont_no_access").")");
			}

			$this->tpl->setVariable("ITEM_ID", $node["obj_id"]);

			if ($_POST["item"][$node["obj_id"]] == "y")
			{
				$this->tpl->setVariable("CHECKED", "checked=\"checked\"");
			}

			$this->tpl->parseCurrentBlock();
		}

		$this->tpl->show();

		$ilBench->stop("ContentPresentation", "PrintViewSelection");
	}

	/**
	* show print view
	*/
	function showPrintView()
	{
		global $ilBench,$ilUser;

		$ilBench->start("ContentPresentation", "PrintView");

		$this->tpl->setVariable("PAGETITLE", " - ".$this->lm->getTitle());
		
		// set style sheets
		if (!$this->offlineMode())
		{
			$this->tpl->setVariable("LOCATION_STYLESHEET", ilObjStyleSheet::getContentPrintStyle());
		}
		else
		{
			$style_name = $this->ilias->account->prefs["style"].".css";;
			$this->tpl->setVariable("LOCATION_STYLESHEET","./".$style_name);
		}

		// content style
		$this->tpl->setCurrentBlock("ContentStyle");
		if (!$this->offlineMode())
		{
			$this->tpl->setVariable("LOCATION_CONTENT_STYLESHEET",
				ilObjStyleSheet::getContentStylePath($this->lm->getStyleSheetId()));
		}
		else
		{
			$this->tpl->setVariable("LOCATION_CONTENT_STYLESHEET", "content.css");
		}
		$this->tpl->parseCurrentBlock();

		// syntax style
		$this->tpl->setCurrentBlock("SyntaxStyle");
		$this->tpl->setVariable("LOCATION_SYNTAX_STYLESHEET",
			ilObjStyleSheet::getSyntaxStylePath());
		$this->tpl->parseCurrentBlock();

		//$this->tpl->setVariable("LOCATION_STYLESHEET", ilUtil::getStyleSheetLocation());
		$this->tpl->addBlockFile("CONTENT", "content", "tpl.lm_print_view.html", true);

		// set title header
		$this->tpl->setVariable("HEADER", $this->lm->getTitle());

		$nodes = $this->lm_tree->getSubtree($this->lm_tree->getNodeData($this->lm_tree->getRootId()));

		include_once("content/classes/Pages/class.ilPageObjectGUI.php");
		include_once("content/classes/class.ilLMPageObject.php");
		include_once("content/classes/class.ilStructureObject.php");

		$act_level = 99999;
		$activated = false;

		$glossary_links = array();
		$output_header = false;
		$media_links = array();

		foreach ($nodes as $node)
		{

			// print all subchapters/subpages if higher chapter
			// has been selected
			if ($node["depth"] <= $act_level)
			{
				if ($_POST["item"][$node["obj_id"]] == "y")
				{
					$act_level = $node["depth"];
					$activated = true;
				}
				else
				{
					$act_level = 99999;
					$activated = false;
				}
			}
			
			if ($activated &&
				ilObjContentObject::_checkPreconditionsOfPage($this->lm->getId(), $node["obj_id"]))
			{
				// output learning module header
				if ($node["type"] == "du")
				{
					$output_header = true;
				}

				// output chapter title
				if ($node["type"] == "st")
				{
					$chap =& new ilStructureObject($this->lm, $node["obj_id"]);
					$this->tpl->setCurrentBlock("print_chapter");

					$chapter_title = $chap->_getPresentationTitle($node["obj_id"],
						$this->lm->isActiveNumbering());
					$this->tpl->setVariable("CHAP_TITLE",
						$chapter_title);

					$this->tpl->parseCurrentBlock();
					$this->tpl->setCurrentBlock("print_block");
					$this->tpl->parseCurrentBlock();
				}

				// output page
				if ($node["type"] == "pg" and ($ilUser->getId() == ANONYMOUS_USER_ID and 
											   $this->lm_gui->object->getPublicAccessMode() == "selected" and 
											   ilLMObject::_isPagePublic($node["obj_id"])))
				{
					$this->tpl->setCurrentBlock("print_item");
					$page_id = $node["obj_id"];

					$page_object =& new ilPageObject($this->lm->getType(), $page_id);
					//$page_object->buildDom();
					$page_object_gui =& new ilPageObjectGUI($page_object);

					$lm_pg_obj =& new ilLMPageObject($this->lm, $page_id);
					$lm_pg_obj->setLMId($this->lm->getId());

					// determine target frames for internal links
					$page_object_gui->setLinkFrame($_GET["frame"]);
					$page_object_gui->setOutputMode("print");

					$page_object_gui->setPresentationTitle("");
					if ($this->lm->getPageHeader() == IL_PAGE_TITLE)
					{
						$page_title = ilLMPageObject::_getPresentationTitle($lm_pg_obj->getId(),
								$this->lm->getPageHeader(), $this->lm->isActiveNumbering());

						// prevent page title after chapter title
						// that have the same content
						if ($this->lm->isActiveNumbering())
						{
							$chapter_title = trim(substr($chapter_title,
								strpos($chapter_title, " ")));
						}

						if ($page_title != $chapter_title)
						{
							$page_object_gui->setPresentationTitle($page_title);
						}
					}

					$page_content = $page_object_gui->showPage();
					if ($this->lm->getPageHeader() != IL_PAGE_TITLE)
					{
						$this->tpl->setVariable("CONTENT", $page_content);
					}
					else
					{
						$this->tpl->setVariable("CONTENT", $page_content."<br />");
					}
					$chapter_title = "";
					$this->tpl->parseCurrentBlock();
					$this->tpl->setCurrentBlock("print_block");
					$this->tpl->parseCurrentBlock();

					// get internal links
					$int_links = ilInternalLink::_getTargetsOfSource($this->lm->getType().":pg", $node["obj_id"]);

					$got_mobs = false;

					foreach ($int_links as $key => $link)
					{
						if ($link["type"] == "git" &&
							($link["inst"] == IL_INST_ID || $link["inst"] == 0))
						{
							$glossary_links[$key] = $link;
						}
						if ($link["type"] == "mob" &&
							($link["inst"] == IL_INST_ID || $link["inst"] == 0))
						{
							$got_mobs = true;
							$mob_links[$key] = $link;
						}
					}

					// this is not cool because of performance reasons
					// unfortunately the int link table does not
					// store the target frame (we want to append all linked
					// images but not inline images (i.e. mobs with no target
					// frame))
					if ($got_mobs)
					{
						$page_object->buildDom();
						$links = $page_object->getInternalLinks();
						foreach($links as $link)
						{
							if ($link["Type"] == "MediaObject"
								&& $link["TargetFrame"] != ""
								&& $link["TargetFrame"] != "None")
							{
								$media_links[] = $link;
							}
						}
					}
				}
			}
		}

		$annex_cnt = 0;
		$annexes = array();

		// glossary
		if (count($glossary_links) > 0)
		{
			include_once("content/classes/class.ilGlossaryTerm.php");
			include_once("content/classes/class.ilGlossaryDefinition.php");

			// sort terms
			$terms = array();
			
			foreach($glossary_links as $key => $link)
			{
				$term = ilGlossaryTerm::_lookGlossaryTerm($link["id"]);
				$terms[$term.":".$key] = $link;
			}
			ksort($terms);

			foreach($terms as $key => $link)
			{
				$defs = ilGlossaryDefinition::getDefinitionList($link["id"]);
				$def_cnt = 1;

				// output all definitions of term
				foreach($defs as $def)
				{
					// definition + number, if more than 1 definition
					if (count($defs) > 1)
					{
						$this->tpl->setCurrentBlock("def_title");
						$this->tpl->setVariable("TXT_DEFINITION",
							$this->lng->txt("cont_definition")." ".($def_cnt++));
						$this->tpl->parseCurrentBlock();
					}
					$page =& new ilPageObject("gdf", $def["id"]);
					$page_gui =& new ilPageObjectGUI($page);
					$page_gui->setTemplateOutput(false);
					$page_gui->setOutputMode("print");

					$this->tpl->setCurrentBlock("definition");
					$output = $page_gui->showPage();
					$this->tpl->setVariable("VAL_DEFINITION", $output);
					$this->tpl->parseCurrentBlock();
				}

				// output term
				$this->tpl->setCurrentBlock("term");
				$this->tpl->setVariable("VAL_TERM",
					$term = ilGlossaryTerm::_lookGlossaryTerm($link["id"]));
				$this->tpl->parseCurrentBlock();
			}

			// output glossary header
			$annex_cnt++;
			$this->tpl->setCurrentBlock("glossary");
			$annex_title = $this->lng->txt("cont_annex")." ".
				chr(64+$annex_cnt).": ".$this->lng->txt("glo");
			$this->tpl->setVariable("TXT_GLOSSARY", $annex_title);
			$this->tpl->parseCurrentBlock();

			$annexes[] = $annex_title;
		}

		// referenced images
		if (count($media_links) > 0)
		{
			include_once("content/classes/Media/class.ilObjMediaObject.php");
			include_once("content/classes/Media/class.ilMediaItem.php");

			foreach($media_links as $media)
			{
				if (substr($media["Target"],0,4) == "il__")
				{
					$arr = explode("_",$media["Target"]);
					$id = $arr[count($arr) - 1];
					
					$med_obj = new ilObjMediaObject($id);
					$med_item =& $med_obj->getMediaItem("Standard");
					if (is_object($med_item))
					{
						if (is_int(strpos($med_item->getFormat(), "image")))
						{
							$this->tpl->setCurrentBlock("ref_image");
							
							// image source
							if ($med_item->getLocationType() == "LocalFile")
							{
								$this->tpl->setVariable("IMG_SOURCE",
									ilUtil::getWebspaceDir("output")."/mobs/mm_".$id.
									"/".$med_item->getLocation());
							}
							else
							{
								$this->tpl->setVariable("IMG_SOURCE",
									$med_item->getLocation());								
							}
							
							if ($med_item->getCaption() != "")
							{
								$this->tpl->setVariable("IMG_TITLE", $med_item->getCaption());
							}
							else
							{
								$this->tpl->setVariable("IMG_TITLE", $med_obj->getTitle());
							}
							$this->tpl->parseCurrentBlock();
						}
					}
				}
			}
			
			// output glossary header
			$annex_cnt++;
			$this->tpl->setCurrentBlock("ref_images");
			$annex_title = $this->lng->txt("cont_annex")." ".
				chr(64+$annex_cnt).": ".$this->lng->txt("cont_ref_images");
			$this->tpl->setVariable("TXT_REF_IMAGES", $annex_title);
			$this->tpl->parseCurrentBlock();

			$annexes[] = $annex_title;
		}

		// output learning module title and toc
		if ($output_header)
		{
			$this->tpl->setCurrentBlock("print_header");
			$this->tpl->setVariable("LM_TITLE", $this->lm->getTitle());
			if ($this->lm->getDescription() != "none")
			{
				include_once("Services/MetaData/classes/class.ilMD.php");
				$md = new ilMD($this->lm->getId(), 0, $this->lm->getType());
				$md_gen = $md->getGeneral();
				foreach($md_gen->getDescriptionIds() as $id)
				{
					$md_des = $md_gen->getDescription($id);
					$description = $md_des->getDescription();
				}

				$this->tpl->setVariable("LM_DESCRIPTION",
					$description);
			}
			$this->tpl->parseCurrentBlock();

			// output toc
			$nodes2 = $nodes;
			foreach ($nodes2 as $node2)
			{
				if ($node2["type"] == "st"
					&& ilObjContentObject::_checkPreconditionsOfPage($this->lm->getId(), $node2["obj_id"]))
				{
					for ($j=1; $j < $node2["depth"]; $j++)
					{
						$this->tpl->setCurrentBlock("indent");
						$this->tpl->setVariable("IMG_BLANK", ilUtil::getImagePath("browser/blank.gif"));
						$this->tpl->parseCurrentBlock();
					}
					$this->tpl->setCurrentBlock("toc_entry");
					$this->tpl->setVariable("TXT_TOC_TITLE",
						ilStructureObject::_getPresentationTitle($node2["obj_id"],
						$this->lm->isActiveNumbering()));
					$this->tpl->parseCurrentBlock();
				}
			}

			// annexes
			foreach ($annexes as $annex)
			{
				$this->tpl->setCurrentBlock("indent");
				$this->tpl->setVariable("IMG_BLANK", ilUtil::getImagePath("browser/blank.gif"));
				$this->tpl->parseCurrentBlock();
				$this->tpl->setCurrentBlock("toc_entry");
				$this->tpl->setVariable("TXT_TOC_TITLE", $annex);
				$this->tpl->parseCurrentBlock();
			}

			$this->tpl->setCurrentBlock("toc");
			$this->tpl->setVariable("TXT_TOC", $this->lng->txt("cont_toc"));
			$this->tpl->parseCurrentBlock();

			$this->tpl->setCurrentBlock("print_start_block");
			$this->tpl->parseCurrentBlock();
		}

		$this->tpl->show(false);

		$ilBench->stop("ContentPresentation", "PrintView");
	}

	// PRIVATE METHODS
	function setSessionVars()
	{
		if($_POST["action"] == "show" or $_POST["action"] == "show_citation")
		{
			if($_POST["action"] == "show_citation")
			{
				// ONLY ONE EDITION
				if(count($_POST["target"]) != 1)
				{
					sendInfo($this->lng->txt("cont_citation_err_one"));
					$_POST["action"] = "";
					$_POST["target"] = 0;
					return false;
				}
				$_SESSION["citation"] = 1;
			}
			else
			{
				unset($_SESSION["citation"]);
			}
			if(isset($_POST["tr_id"]))
			{
				$_SESSION["tr_id"] = $_POST["tr_id"][0];
			}
			else
			{
				unset($_SESSION["tr_id"]);
			}
			if(is_array($_POST["target"]))
			{
				$_SESSION["bib_id"] = ",".implode(',',$_POST["target"]).",";
			}
			else
			{
				$_SESSION["bib_id"] = ",0,";
			}
		}
		return true;
	}

	/**
	* download file of file lists
	*/
	function downloadFile()
	{
		$file = explode("_", $_GET["file_id"]);
		require_once("classes/class.ilObjFile.php");
		$fileObj =& new ilObjFile($file[count($file) - 1], false);
		$fileObj->sendFile();
		exit;
	}

	/**
	* download source code paragraph
	*/
	function download_paragraph ()
	{
		require_once("content/classes/Pages/class.ilPageObject.php");
		$pg_obj =& new ilPageObject($this->lm->getType(), $_GET["pg_id"]);
		$pg_obj->send_paragraph ($_GET["par_id"], $_GET["downloadtitle"]);
	}
	
	/**
	* show download list
	*/
	function showDownloadList()
	{
		global $ilBench;

		//$this->tpl = new ilTemplate("tpl.lm_toc.html", true, true, true);
		$this->tpl->setCurrentBlock("ContentStyle");
		if (!$this->offlineMode())
		{
			$this->tpl->setVariable("LOCATION_CONTENT_STYLESHEET",
				ilObjStyleSheet::getContentStylePath($this->lm->getStyleSheetId()));
		}
		else
		{
			$this->tpl->setVariable("LOCATION_CONTENT_STYLESHEET", "content.css");
		}
		$this->tpl->parseCurrentBlock();

		$this->tpl->setVariable("PAGETITLE", " - ".$this->lm->getTitle());
		$this->tpl->setVariable("LOCATION_STYLESHEET", ilUtil::getStyleSheetLocation());
		$this->tpl->addBlockFile("CONTENT", "content", "tpl.lm_download_list.html", true);

		// set title header
		$this->tpl->setVariable("HEADER", $this->lm->getTitle());
		$this->tpl->setVariable("TXT_BACK", $this->lng->txt("back"));
		$this->tpl->setVariable("LINK_BACK",
			"lm_presentation.php?ref_id=".$_GET["ref_id"]."&obj_id=".$_GET["obj_id"]);

		// create table
		require_once("classes/class.ilTableGUI.php");
		$tbl = new ilTableGUI();

		// load files templates
		$this->tpl->addBlockfile("DOWNLOAD_TABLE", "download_table", "tpl.table.html");

		// load template for table content data
		$this->tpl->addBlockfile("TBL_CONTENT", "tbl_content", "tpl.download_file_row.html", true);

		$export_files = array();
		$types = array("xml", "html");
		foreach($types as $type)
		{
			if ($this->lm->getPublicExportFile($type) != "")
			{
				$dir = $this->lm->getExportDirectory($type);
				$size = filesize($this->lm->getExportDirectory($type)."/".
					$this->lm->getPublicExportFile($type));
				$export_files[] = array("type" => $type,
					"file" => $this->lm->getPublicExportFile($type),
					"size" => $size);
			}
		}
		
		$num = 0;
		
		$tbl->setTitle($this->lng->txt("download"));

		$tbl->setHeaderNames(array($this->lng->txt("cont_format"),
			$this->lng->txt("cont_file"),
			$this->lng->txt("size"), $this->lng->txt("date"),
			""));

		$cols = array("format", "file", "size", "date", "download");
		$header_params = array("ref_id" => $_GET["ref_id"], "obj_id" => $_GET["obj_id"],
			"cmd" => "showDownloadList", "cmdClass" => strtolower(get_class($this)));
		$tbl->setHeaderVars($cols, $header_params);
		$tbl->setColumnWidth(array("10%", "30%", "20%", "20%","20%"));

		// control
		$tbl->setOrderColumn($_GET["sort_by"]);
		$tbl->setOrderDirection($_GET["sort_order"]);
		$tbl->setLimit($_GET["limit"]);
		$tbl->setOffset($_GET["offset"]);
		$tbl->setMaxCount($this->maxcount);		// ???

		$this->tpl->setVariable("COLUMN_COUNTS", 5);

		// footer
		//$tbl->setFooter("tblfooter",$this->lng->txt("previous"),$this->lng->txt("next"));
		$tbl->disable("footer");

		$tbl->setMaxCount(count($export_files));
		$export_files = array_slice($export_files, $_GET["offset"], $_GET["limit"]);

		$tbl->render();
		if(count($export_files) > 0)
		{
			$i=0;
			foreach($export_files as $exp_file)
			{
				$this->tpl->setCurrentBlock("tbl_content");
				$this->tpl->setVariable("TXT_FILENAME", $exp_file["file"]);

				$css_row = ilUtil::switchColor($i++, "tblrow1", "tblrow2");
				$this->tpl->setVariable("CSS_ROW", $css_row);

				$this->tpl->setVariable("TXT_SIZE", $exp_file["size"]);
				$this->tpl->setVariable("TXT_FORMAT", strtoupper($exp_file["type"]));
				$this->tpl->setVariable("CHECKBOX_ID", $exp_file["type"].":".$exp_file["file"]);

				$file_arr = explode("__", $exp_file["file"]);
				$this->tpl->setVariable("TXT_DATE", date("Y-m-d H:i:s",$file_arr[0]));

				$this->tpl->setVariable("TXT_DOWNLOAD", $this->lng->txt("download"));
				$this->tpl->setVariable("LINK_DOWNLOAD", "lm_presentation.php?cmd=downloadExportFile&type=".
					$exp_file["type"]."&ref_id=".$_GET["ref_id"]);

				$this->tpl->parseCurrentBlock();
			}
		} //if is_array
		else
		{
			$this->tpl->setCurrentBlock("notfound");
			$this->tpl->setVariable("TXT_OBJECT_NOT_FOUND", $this->lng->txt("obj_not_found"));
			$this->tpl->setVariable("NUM_COLS", 5);
			$this->tpl->parseCurrentBlock();
		}

		$this->tpl->show();
	}

	
	/**
	* send download file (xml/html)
	*/
	function downloadExportFile()
	{
		$file = $this->lm->getPublicExportFile($_GET["type"]);
		if ($this->lm->getPublicExportFile($_GET["type"]) != "")
		{
			$dir = $this->lm->getExportDirectory($_GET["type"]);
			if (is_file($dir."/".$file))
			{
				ilUtil::deliverFile($dir."/".$file, $file);
				exit;
			}
		}
		$this->ilias->raiseError($this->lng->txt("file_not_found"),$this->ilias->error_obj->MESSAGE);
	}
	
	/**
	* handles links for learning module presentation
	*/
	function getLink($a_ref_id, $a_cmd = "", $a_obj_id = "", $a_frame = "", $a_type = "")
	{
		if ($a_cmd == "")
		{
			$a_cmd = "layout";
		}
		$script = "lm_presentation.php";
		
		// handle online links
		if (!$this->offlineMode())
		{
			$link = $script."?ref_id=".$a_ref_id;
			switch ($a_cmd)
			{
				case "fullscreen":
					$link.= "&cmd=fullscreen";
					break;
				
				default:
					$link.= "&amp;cmd=".$a_cmd;
					if ($a_frame != "")
					{
						$link.= "&amp;frame=".$a_frame;
					}
					if ($a_obj_id != "")
					{
						switch ($a_type)
						{
							case "MediaObject":
								$link.= "&amp;mob_id=".$a_obj_id;
								break;
								
							default:
								$link.= "&amp;obj_id=".$a_obj_id;
								break;
						}
					}
					if ($a_type != "")
					{
						$link.= "&amp;obj_type=".$a_type;
					}
					break;
			}
		}
		else	// handle offline links
		{
			switch ($a_cmd)
			{
				case "downloadFile":
					break;
					
				case "fullscreen":
					$link = "fullscreen.html";		// id is handled by xslt
					break;
					
				case "layout":
				
					if ($a_obj_id == "")
					{
						$a_obj_id = $this->lm_tree->getRootId();
						$pg_node = $this->lm_tree->fetchSuccessorNode($a_obj_id, "pg");
						$a_obj_id = $pg_node["obj_id"];
					}
					if ($a_type == "StructureObject")
					{
						$pg_node = $this->lm_tree->fetchSuccessorNode($a_obj_id, "pg");
						$a_obj_id = $pg_node["obj_id"];
					}
				
					if ($a_frame != "")
					{
						if ($a_frame != "toc")
						{
							$link = "frame_".$a_obj_id."_".$a_frame.".html";
						}
						else	// don't save multiple toc frames (all the same)
						{
							$link = "frame_".$a_frame.".html";
						}						
					}
					else
					{
						$link = "lm_pg_".$a_obj_id.".html";
					}
					break;
					
				case "glossary":
				$link = "term_".$a_obj_id.".html";
					break;
				
				case "media":
					$link = "media_".$a_obj_id.".html";
					break;
					
				default:
					break;
			}
		}
		
		return $link;
	}
	
	
	
	function showNoPublicAccess()
	{
		$page_id = $this->getCurrentPageId();

		// content style
		$this->tpl->setCurrentBlock("ContentStyle");
		
		if (!$this->offlineMode())
		{
			$this->tpl->setVariable("LOCATION_CONTENT_STYLESHEET",
				ilObjStyleSheet::getContentStylePath($this->lm->getStyleSheetId()));
		}
		else
		{
			$this->tpl->setVariable("LOCATION_CONTENT_STYLESHEET", "content.css");
		}
		
		$this->tpl->parseCurrentBlock();
		$this->tpl->addBlockFile("PAGE_CONTENT", "pg_content", "tpl.page_nopublicaccess.html", true);
		$this->tpl->setCurrentBlock("pg_content");
		$this->tpl->setVariable("TXT_PAGE_NO_PUBLIC_ACCESS",$this->lng->txt("msg_page_no_public_access"));
		$this->tpl->parseCurrentBlock();
	}
	
	function getSourcecodeDownloadLink() {
		if (!$this->offlineMode())
		{
			return "lm_presentation.php?".session_name()."=".session_id()."&ref_id=".$this->lm->getRefId();
		} else {
			return "";
		}
	}

	/**
	 * set offline directory to offdir
	 * 
	 * @param offdir contains diretory where to store files
	 * 
	 * current used in code paragraph
	 */	
	function setOfflineDirectory ($offdir) {
		$this->offline_directory = $offdir;
	}
	
	
	/**
	 * get offline directory
	 * @return directory where to store offline files
	 * 
	 * current used in code paragraph 
	 */
	function getOfflineDirectory () {
		return $this->offline_directory;
	}
	
	/**
	 * store paragraph into file directory
	 * files/codefile_$pg_id_$paragraph_id/downloadtitle
	 */
	function handleCodeParagraph ($page_id, $paragraph_id, $title, $text) {
		$directory = $this->getOfflineDirectory()."/codefiles/".$page_id."/".$paragraph_id;
		ilUtil::makeDirParents ($directory);
		$file = $directory."/".$title;
		if (!($fp = @fopen($file,"w+")))
		{
			die ("<b>Error</b>: Could not open \"".$file."\" for writing".
				" in <b>".__FILE__."</b> on line <b>".__LINE__."</b><br />");
		}		
		chmod($file, 0770);
		fwrite($fp, $text);
		fclose($fp);
	}
}
?>
