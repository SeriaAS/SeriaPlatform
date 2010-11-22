<?php
class SERIA_GuiVisualize {
	/**
	*	Present a toolbar that can contain a number of buttons or links 
	*/
	public static function toolbar(array $buttons)
	{
		SERIA_Template::cssInclude(SERIA_HTTP_ROOT.'/seria/components/SERIA_Gui/SERIA_GuiVisualize.css');
		return "<div class='SERIA_GuiVisualize toolbar'><ul><li>".implode('</li><li>', $buttons)."</li></ul></div>";
	}

	/**
	*	Present a box with a number, for example to present statistical numbers or other informatino that requires extra emphasizing.
	*/
	public static function integer($caption, $count)
	{
		SERIA_Template::cssInclude(SERIA_HTTP_ROOT.'/seria/components/SERIA_Gui/SERIA_GuiVisualize.css');
		return "<div class='SERIA_GuiVisualize integer'><div class='value'>".number_format($count, 0, ",", " ")."</div><div class='caption'>".$caption."</div><div class='clear'></div></div>";
	}

	public static function notice($text)
	{
		return '<div class="ui-widget"><div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 0 .7em;"><p><span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>'.$text.'</p></div></div>';
	}
}
