<?php
 /*
   +----------------------------------------------------------------------------+
   | ILIAS open source                                                          |
   +----------------------------------------------------------------------------+
   | Copyright (c) 1998-2001 ILIAS open source, University of Cologne           |
   |                                                                            |
   | This program is free software; you can redistribute it and/or              |
   | modify it under the terms of the GNU General Public License                |
   | as published by the Free Software Foundation; either version 2             |
   | of the License, or (at your option) any later version.                     |
   |                                                                            |
   | This program is distributed in the hope that it will be useful,            |
   | but WITHOUT ANY WARRANTY; without even the implied warranty of             |
   | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the              |
   | GNU General Public License for more details.                               |
   |                                                                            |
   | You should have received a copy of the GNU General Public License          |
   | along with this program; if not, write to the Free Software                |
   | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA. |
   +----------------------------------------------------------------------------+
*/

include_once "./Modules/TestQuestionPool/classes/class.assQuestionGUI.php";
include_once "./Modules/Test/classes/inc.AssessmentConstants.php";

/**
* Numeric question GUI representation
*
* The assNumericGUI class encapsulates the GUI representation
* for numeric questions.
*
* @author		Helmut SchottmÃ¼ller <helmut.schottmueller@mac.com>
* @author   Nina Gharib <nina@wgserve.de>
* @version	$Id$
* @ingroup ModulesTestQuestionPool
*/
class assNumericGUI extends assQuestionGUI
{
	/**
	* assNumericGUI constructor
	*
	* The constructor takes possible arguments an creates an instance of the assNumericGUI object.
	*
	* @param integer $id The database id of a Numeric question object
	* @access public
	*/
	function assNumericGUI(
			$id = -1
	)
	{
		$this->assQuestionGUI();
		include_once "./Modules/TestQuestionPool/classes/class.assNumeric.php";
		$this->object = new assNumeric();
		if ($id >= 0)
		{
			$this->object->loadFromDb($id);
		}
	}

	function getCommand($cmd)
	{
		if (substr($cmd, 0, 6) == "delete")
		{
			$cmd = "delete";
		}

		return $cmd;
	}


	/**
	* Creates an output of the edit form for the question
	*
	* Creates an output of the edit form for the question
	*
	* @access public
	*/
	function editQuestion()
	{
		$javascript = "<script type=\"text/javascript\">function initialSelect() {\n%s\n}</script>";
		$this->getQuestionTemplate();
		$this->tpl->addBlockFile("QUESTION_DATA", "question_data", "tpl.il_as_qpl_numeric.html", "Modules/TestQuestionPool");
		// call to other question data i.e. estimated working time block
		$this->outOtherQuestionData();

		$internallinks = array(
			"lm" => $this->lng->txt("obj_lm"),
			"st" => $this->lng->txt("obj_st"),
			"pg" => $this->lng->txt("obj_pg"),
			"glo" => $this->lng->txt("glossary_term")
		);
		foreach ($internallinks as $key => $value)
		{
			$this->tpl->setCurrentBlock("internallink");
			$this->tpl->setVariable("TYPE_INTERNAL_LINK", $key);
			$this->tpl->setVariable("TEXT_INTERNAL_LINK", $value);
			$this->tpl->parseCurrentBlock();
		}
		
		if ($this->object->getRangeCount() == 0)
		{
			$this->object->addRange(0.0, 0.0, 0);
		}
		
		$counter = 0;
		foreach ($this->object->ranges as $range)
		{
			$this->tpl->setCurrentBlock("ranges");
			$this->tpl->setVariable("COUNTER", $counter);
			$this->tpl->setVariable("TEXT_RANGE", $this->lng->txt("range"));
			if (strlen($range->getPoints())) $this->tpl->setVariable("VALUE_POINTS", " value=\"" . $range->getPoints() . "\"");
			if (strlen($range->getLowerLimit())) $this->tpl->setVariable("VALUE_LOWER_LIMIT", " value=\"" . $range->getLowerLimit() . "\"");
			if (strlen($range->getUpperLimit())) $this->tpl->setVariable("VALUE_UPPER_LIMIT", " value=\"" . $range->getUpperLimit() . "\"");
			$this->tpl->setVariable("TEXT_RANGE_LOWER_LIMIT", $this->lng->txt("range_lower_limit"));
			$this->tpl->setVariable("TEXT_RANGE_UPPER_LIMIT", $this->lng->txt("range_upper_limit"));
			$this->tpl->setVariable("TEXT_POINTS", $this->lng->txt("points"));
			$this->tpl->parseCurrentBlock();
			$counter++;
		}
		
		$this->tpl->setCurrentBlock("question_data");
		$this->tpl->setVariable("NUMERIC_ID", $this->object->getId());
		$this->tpl->setVariable("VALUE_NUMERIC_TITLE", ilUtil::prepareFormOutput($this->object->getTitle()));
		$this->tpl->setVariable("VALUE_NUMERIC_COMMENT", ilUtil::prepareFormOutput($this->object->getComment()));
		$this->tpl->setVariable("VALUE_NUMERIC_AUTHOR", ilUtil::prepareFormOutput($this->object->getAuthor()));
		$questiontext = $this->object->getQuestion();
		$this->tpl->setVariable("VALUE_QUESTION", $this->object->prepareTextareaOutput($questiontext));
		$this->tpl->setVariable("TEXT_TITLE", $this->lng->txt("title"));
		$this->tpl->setVariable("TEXT_AUTHOR", $this->lng->txt("author"));
		$this->tpl->setVariable("TEXT_COMMENT", $this->lng->txt("description"));
		$this->tpl->setVariable("TEXT_QUESTION", $this->lng->txt("question"));
		$this->tpl->setVariable("TEXT_SOLUTION_HINT", $this->lng->txt("solution_hint"));
		$this->tpl->setVariable("TEXT_MAXCHARS", $this->lng->txt("maxchars"));
		$this->tpl->setVariable("VALUE_MAXCHARS", $this->object->getMaxChars());
		if (count($this->object->suggested_solutions))
		{
			$solution_array = $this->object->getSuggestedSolution(0);
			include_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";
			$href = assQuestion::_getInternalLinkHref($solution_array["internal_link"]);
			$this->tpl->setVariable("TEXT_VALUE_SOLUTION_HINT", " <a href=\"$href\" target=\"content\">" . $this->lng->txt("solution_hint"). "</a> ");
			$this->tpl->setVariable("BUTTON_REMOVE_SOLUTION", $this->lng->txt("remove"));
			$this->tpl->setVariable("BUTTON_ADD_SOLUTION", $this->lng->txt("change"));
			$this->tpl->setVariable("VALUE_SOLUTION_HINT", $solution_array["internal_link"]);
		}
		else
		{
			$this->tpl->setVariable("BUTTON_ADD_SOLUTION", $this->lng->txt("add"));
		}
		$this->tpl->setVariable("SAVE",$this->lng->txt("save"));
		$this->tpl->setVariable("SAVE_EDIT", $this->lng->txt("save_edit"));
		$this->tpl->setVariable("CANCEL",$this->lng->txt("cancel"));
		$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));
		$this->ctrl->setParameter($this, "sel_question_types", "assNumeric");
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TEXT_QUESTION_TYPE", $this->lng->txt("assNumeric"));
		include_once "./Services/RTE/classes/class.ilRTE.php";
		$rtestring = ilRTE::_getRTEClassname();
		include_once "./Services/RTE/classes/class.$rtestring.php";
		$rte = new $rtestring();
		$rte->addPlugin("latex");
		$rte->addButton("latex");
		include_once "./classes/class.ilObject.php";
		$obj_id = $_GET["q_id"];
		$obj_type = ilObject::_lookupType($_GET["ref_id"], TRUE);
		$rte->addRTESupport($obj_id, $obj_type, "assessment");
		
		$this->tpl->parseCurrentBlock();
		$this->tpl->setCurrentBlock("adm_content");
		// $this->tpl->setVariable("BODY_ATTRIBUTES", " onload=\"initialSelect();\""); 
		$this->tpl->parseCurrentBlock();
	}

	/**
	* check input fields
	*/
	function checkInput()
	{
		$cmd = $this->ctrl->getCmd();

		if ((!$_POST["title"]) or (!$_POST["author"]) or (!$_POST["question"]))
		{
//echo "<br>checkInput1:FALSE";
			return false;
		}
		foreach ($_POST as $key => $value)
		{
			if (preg_match("/range_(\d+)/", $key, $matches))
			{
				if (!$value)
				{
//echo "<br>checkInput2:FALSE";
					return false;
				}
			}
		}

		return true;
	}

	/**
	* Checks the range limits
	*
	* Checks the Range limits Upper and Lower for their correctness
	*
	* @return boolean 
	* @access private
	*/

	function checkRange()
	{
		if (is_numeric($_POST["rang_lower_limit"]) AND is_numeric ($_POST ["range_upper_limit"]))
		{
			if ($_POST ["rang_lower_limit"] < $_POST ["range_upper_limit"])
			{
				return true;
			}
			else 
			{
				return false;
			}
		}
		else 
		{
			return false;
		}
	}

	/**
	* Evaluates a posted edit form and writes the form data in the question object
	*
	* Evaluates a posted edit form and writes the form data in the question object
	*
	* @return integer A positive value, if one of the required fields wasn't set, else 0
	* @access private
	*/
	function writePostData()
	{
		$saved = false;
		$result = 0;
		if ((!$_POST["title"]) or (!$_POST["author"]) or (!$_POST["question"]) or (!$_POST["maxchars"]))
		{
			$result = 1;
		}

		$this->object->setTitle(ilUtil::stripSlashes($_POST["title"]));
		$this->object->setAuthor(ilUtil::stripSlashes($_POST["author"]));
		$this->object->setComment(ilUtil::stripSlashes($_POST["comment"]));
		include_once "./classes/class.ilObjAdvancedEditing.php";
		$questiontext = ilUtil::stripSlashes($_POST["question"], true, ilObjAdvancedEditing::_getUsedHTMLTagsAsString("assessment"));
		$questiontext = preg_replace("/[\n\r]+/", "<br />", $questiontext);
		$this->object->setQuestion($questiontext);
		$this->object->setSuggestedSolution($_POST["solution_hint"], 0);
		$this->object->setShuffle($_POST["shuffle"]);
		$this->object->setMaxChars($_POST["maxchars"]);
		
		// adding estimated working time
		$saved = $saved | $this->writeOtherPostData($result);

		// Delete all existing ranges and create new answers from the form data
		$this->object->flushRanges();

		// Add all answers from the form into the object

		// ...for Numeric with single response
		foreach ($_POST as $key => $value)
		{
			if (preg_match("/lowerlimit_(\d+)/", $key, $matches))
			{
				$points = $_POST["points_$matches[1]"];
				if ($points < 0)
				{
					$result = 1;
					$this->setErrorMessage($this->lng->txt("negative_points_not_allowed"));
				}
				$lowerlimit = str_replace(",", ".", $_POST["lowerlimit_".$matches[1]]);
				if (strlen($lowerlimit) == 0) $lowerlimit = 0.0;
				if (!is_numeric($lowerlimit))
				{
					$this->setErrorMessage($this->lng->txt("value_is_not_a_numeric_value"));
					$result = 1;
				}
				$upperlimit = str_replace(",", ".", $_POST["upperlimit_".$matches[1]]);
				if (strlen($upperlimit) == 0) $upperlimit = 0.0;
				if (!is_numeric($upperlimit))
				{
					$this->setErrorMessage($this->lng->txt("value_is_not_a_numeric_value"));
					$result = 1;
				}
				$this->object->addRange(
					$lowerlimit,
					$upperlimit,
					$points,
					$matches[1]
				);
			}
		}

		if ($saved)
		{
			// If the question was saved automatically before an upload, we have to make
			// sure, that the state after the upload is saved. Otherwise the user could be
			// irritated, if he presses cancel, because he only has the question state before
			// the upload process.
			$this->object->saveToDb();
			$_GET["q_id"] = $this->object->getId();
		}

		return $result;
	}

	function outQuestionForTest($formaction, $active_id, $pass = NULL, $is_postponed = FALSE, $use_post_solutions = FALSE)
	{
		$test_output = $this->getTestOutput($active_id, $pass, $is_postponed, $use_post_solutions); 
		$this->tpl->setVariable("QUESTION_OUTPUT", $test_output);
		$this->tpl->setVariable("FORMACTION", $formaction);
	}

	function getSolutionOutput($active_id, $pass = NULL, $graphicalOutput = FALSE, $result_output = FALSE)
	{
		// get the solution of the user for the active pass or from the last pass if allowed
		$solutions = array();
		if ($active_id)
		{
			$solutions =& $this->object->getSolutionValues($active_id, $pass);
		}
		else
		{
			foreach ($this->object->ranges as $key => $range)
			{
				array_push($solutions, array("value1" => sprintf($this->lng->txt("value_between_x_and_y"), $range->getLowerLimit(), $range->getUpperLimit())));
			}
		}
		
		// generate the question output
		include_once "./classes/class.ilTemplate.php";
		$template = new ilTemplate("tpl.il_as_qpl_numeric_output_solution.html", TRUE, TRUE, "Modules/TestQuestionPool");
		$solutiontemplate = new ilTemplate("tpl.il_as_tst_solution_output.html",TRUE, TRUE, "Modules/TestQuestionPool");
		if (is_array($solutions))
		{
			foreach ($solutions as $solution)
			{
				if ($active_id)
				{
					if ($graphicalOutput)
					{
						// output of ok/not ok icons for user entered solutions
						if ($this->object->getReachedPoints($active_id, $pass) == $this->object->getMaximumPoints())
						{
							$template->setCurrentBlock("icon_ok");
							$template->setVariable("ICON_OK", ilUtil::getImagePath("icon_ok.gif"));
							$template->setVariable("TEXT_OK", $this->lng->txt("answer_is_right"));
							$template->parseCurrentBlock();
						}
						else
						{
							$template->setCurrentBlock("icon_ok");
							$template->setVariable("ICON_NOT_OK", ilUtil::getImagePath("icon_not_ok.gif"));
							$template->setVariable("TEXT_NOT_OK", $this->lng->txt("answer_is_wrong"));
							$template->parseCurrentBlock();
						}
					}
				}
				$template->setVariable("NUMERIC_VALUE", $solution["value1"]);
			}
		}
		$template->setVariable("NUMERIC_SIZE", $this->object->getMaxChars());
		$questiontext = $this->object->getQuestion();
		$template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($questiontext, TRUE));
		$questionoutput = $template->get();
		$solutiontemplate->setVariable("SOLUTION_OUTPUT", $questionoutput);

		return $solutiontemplate->get();
	}
	
	function getPreview()
	{
		// generate the question output
		include_once "./classes/class.ilTemplate.php";
		$template = new ilTemplate("tpl.il_as_qpl_numeric_output.html", TRUE, TRUE, "Modules/TestQuestionPool");
		$template->setVariable("NUMERIC_SIZE", $this->object->getMaxChars());
		$questiontext = $this->object->getQuestion();
		$questiontext = $this->object->getQuestion();
		$template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($questiontext, TRUE));
		$questionoutput = $template->get();
		$questionoutput = preg_replace("/\<div[^>]*?>(.*)\<\/div>/is", "\\1", $questionoutput);
		return $questionoutput;
	}
	
	function getTestOutput($active_id, $pass = NULL, $is_postponed = FALSE, $use_post_solutions = FALSE)
	{
		// get page object output
		$pageoutput = $this->outQuestionPage("", $is_postponed, $active_id);

		// get the solution of the user for the active pass or from the last pass if allowed
		if ($active_id)
		{
			$solutions = NULL;
			include_once "./Modules/Test/classes/class.ilObjTest.php";
			if (!ilObjTest::_getUsePreviousAnswers($active_id, true))
			{
				if (is_null($pass)) $pass = ilObjTest::_getPass($active_id);
			}
			$solutions =& $this->object->getSolutionValues($active_id, $pass);
		}
		
		// generate the question output
		include_once "./classes/class.ilTemplate.php";
		$template = new ilTemplate("tpl.il_as_qpl_numeric_output.html", TRUE, TRUE, "Modules/TestQuestionPool");
		if (is_array($solutions))
		{
			foreach ($solutions as $solution)
			{
				$template->setVariable("NUMERIC_VALUE", " value=\"".$solution["value1"]."\"");
			}
		}
		$template->setVariable("NUMERIC_SIZE", $this->object->getMaxChars());
		$questiontext = $this->object->getQuestion();
		$template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($questiontext, TRUE));
		$questionoutput = $template->get();
		$questionoutput = preg_replace("/(\<div( xmlns:xhtml\=\"http:\/\/www.w3.org\/1999\/xhtml\"){0,1} class\=\"ilc_Question\">\<\/div>)/ims", $questionoutput, $pageoutput);
		return $questionoutput;
	}
	
	function addSuggestedSolution()
	{
		$_SESSION["subquestion_index"] = 0;
		if ($_POST["cmd"]["addSuggestedSolution"])
		{
			if ($this->writePostData())
			{
				sendInfo($this->getErrorMessage());
				$this->editQuestion();
				return;
			}
			if (!$this->checkInput())
			{
				sendInfo($this->lng->txt("fill_out_all_required_fields_add_answer"));
				$this->editQuestion();
				return;
			}
		}
		$this->object->saveToDb();
		$_GET["q_id"] = $this->object->getId();
		$this->tpl->setVariable("HEADER", $this->object->getTitle());
		$this->getQuestionTemplate();
		parent::addSuggestedSolution();
	}

	/**
	* Saves the feedback for a single choice question
	*
	* Saves the feedback for a single choice question
	*
	* @access public
	*/
	function saveFeedback()
	{
		include_once "./classes/class.ilObjAdvancedEditing.php";
		$this->object->saveFeedbackGeneric(0, ilUtil::stripSlashes($_POST["feedback_incomplete"], TRUE, ilObjAdvancedEditing::_getUsedHTMLTagsAsString("assessment")));
		$this->object->saveFeedbackGeneric(1, ilUtil::stripSlashes($_POST["feedback_complete"], TRUE, ilObjAdvancedEditing::_getUsedHTMLTagsAsString("assessment")));
		$this->object->cleanupMediaObjectUsage();
		$this->feedback();
	}

	/**
	* Creates the output of the feedback page for a single choice question
	*
	* Creates the output of the feedback page for a single choice question
	*
	* @access public
	*/
	function feedback()
	{
		$this->tpl->addBlockFile("ADM_CONTENT", "feedback", "tpl.il_as_qpl_numeric_feedback.html", "Modules/TestQuestionPool");
		$this->tpl->setVariable("FEEDBACK_TEXT", $this->lng->txt("feedback"));
		$this->tpl->setVariable("FEEDBACK_COMPLETE", $this->lng->txt("feedback_complete_solution"));
		$this->tpl->setVariable("VALUE_FEEDBACK_COMPLETE", $this->object->prepareTextareaOutput($this->object->getFeedbackGeneric(1)), FALSE);
		$this->tpl->setVariable("FEEDBACK_INCOMPLETE", $this->lng->txt("feedback_incomplete_solution"));
		$this->tpl->setVariable("VALUE_FEEDBACK_INCOMPLETE", $this->object->prepareTextareaOutput($this->object->getFeedbackGeneric(0)), FALSE);
		$this->tpl->setVariable("SAVE", $this->lng->txt("save"));
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));

		include_once "./Services/RTE/classes/class.ilRTE.php";
		$rtestring = ilRTE::_getRTEClassname();
		include_once "./Services/RTE/classes/class.$rtestring.php";
		$rte = new $rtestring();
		$rte->addPlugin("latex");
		$rte->addButton("latex");
		include_once "./classes/class.ilObject.php";
		$obj_id = $_GET["q_id"];
		$obj_type = ilObject::_lookupType($_GET["ref_id"], TRUE);
		$rte->addRTESupport($obj_id, $obj_type, "assessment");
	}
}
?>
