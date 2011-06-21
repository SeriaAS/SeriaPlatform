<?php
class SERIA_GuiVisualize {
	public static function barChart($keyValues, $keyLabels)
	{
		SERIA_Template::cssInclude(SERIA_HTTP_ROOT.'/seria/platform/components/SERIA_Gui/SERIA_GuiVisualize.css');

		// analyze
		$minValue = current($keyValues);
		$maxValue = current($keyValues);
		foreach($keyValues as $key => $value)
		{
			if($value < $minValue) $minValue = $value;
			if($value > $maxValue) $maxValue = $value;
		}
		$range = $maxValue - $minValue;

		$html = "<table class='SERIA_GuiVisualize barChart'><tbody>";

		foreach($keyValues as $key => $value)
		{
			$html .= "\n\t<tr><td class='label'><label>".htmlspecialchars($keyLabels[$key])."</label></td>";
			$flooredValue = $value - $minValue;
			$percent = floor(($flooredValue / $range)*100);
			$html .= "<td><div style='width:100%'><div style='background-color: green; width: ".$percent."%;'></div></div></td></tr>";
		}

		$html .= "</tbody></table>";

		return $html;
	}

	/**
	*	Present a toolbar that can contain a number of buttons or links 
	*/
	public static function toolbar(array $buttons)
	{
		SERIA_Template::cssInclude(SERIA_HTTP_ROOT.'/seria/platform/components/SERIA_Gui/SERIA_GuiVisualize.css');
		return "<div class='SERIA_GuiVisualize toolbar'><ul><li>".implode('</li><li>', $buttons)."</li></ul></div>";
	}

	/**
	*	Present a box with a number, for example to present statistical numbers or other informatino that requires extra emphasizing.
	*/
	public static function integer($caption, $count)
	{
		SERIA_Template::cssInclude(SERIA_HTTP_ROOT.'/seria/platform/components/SERIA_Gui/SERIA_GuiVisualize.css');
		return "<div class='SERIA_GuiVisualize integer'><div class='value'>".number_format($count, 0, ",", " ")."</div><div class='caption'>".$caption."</div><div class='clear'></div></div>";
	}

	public static function notice($text)
	{
		return '<div class="ui-widget"><div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 0 .7em;"><p><span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>'.$text.'</p></div></div>';
	}

	/**
	*	Renders a list of shortcuts that a user can click to quickly navigate to often performed tasks.
	*/
	public static function shortcuts($title, $shortcuts)
	{
		SERIA_Template::cssInclude(SERIA_HTTP_ROOT.'/seria/platform/components/SERIA_Gui/SERIA_GuiVisualize.css');
		$res = "<div class='SERIA_GuiVisualize shortcuts'><h2><span class='ui-icon ui-icon-star' style='float:left;'></span>".htmlspecialchars($title)."</h2><ul>";
		foreach($shortcuts as $caption => $url)
			$res .= "<li><a href='".htmlspecialchars($url)."'>".htmlspecialchars($caption)."</a></li>";
		$res .= "</ul></div>";
		return $res;
	}

	/**
	*	Renders a box with a picture and a text, possibly clickable
	*/
	public static function imageBox($title, $image, $width, $height, $onClick=NULL)
	{
		SERIA_Template::cssInclude(SERIA_HTTP_ROOT.'/seria/platform/components/SERIA_Gui/SERIA_GuiVisualize.css');
		$res = "<div class='SERIA_GuiVisualize imageBox'".($onClick!==NULL?' onclick="'.htmlspecialchars($onClick).'"':'')." style='".($onClick!==NULL?'cursor:pointer;':'').";width:".$width."px;height:".$height."px; overflow: hidden;'><h2><span class='ui-icon ui-icon-image' style='float:left;'></span>".htmlspecialchars($title)."</h2><table style='border-collapse:collapse;margin:0;padding:0;border:1px solid #ccc;width:".($width)."px;'><tr><td class='contents' style='text-align: center;margin:0;padding:0;background-color:#eee;height:".($height-18)."px;overflow:hidden;vertical-align:middle;'><img src='$image' style='width:".($width-4)."px;max-height:".($height-20)."px;margin:0;padding:0;'></td></tr></table></div>";
		return $res;
	}

	/**
	*	Renders a list of shortcuts that a user can click to quickly navigate to often performed tasks.
	*/
	public static function box($title, $contents, $icon='bullet')
	{
		SERIA_Template::cssInclude(SERIA_HTTP_ROOT.'/seria/platform/components/SERIA_Gui/SERIA_GuiVisualize.css');
		$res = "<div class='SERIA_GuiVisualize box'><h2><span class='ui-icon ui-icon-".$icon."' style='float:left;'></span>".htmlspecialchars($title)."</h2><div class='contents'>$contents</div></div>";
		return $res;
	}

	/**
	*	Renders a jquery-ui-themeroller progressbar depending on the current jquery ui theme css file. input is either a float (0.4) or an integer (44)
	*/ 
	public static function progressbar($progress)
	{
		static $progressbarCounter;
		if(!$progressbarCounter)
			$progressbarCounter = 0;
/*
		if(is_float($progress) && $progress<1)
			$percentComplete = $progress*100;
		else if($progress == 1)
			$percentComplete = 100;
		else
*/
		$percentComplete = $progress*100;

		$res = "<div id='progressbar_".$progressbarCounter."'></div>";
		$res.= "<script type='text/javascript'>
			$(document).ready(function() {
				$('#progressbar_".$progressbarCounter."').progressbar({
					value: ".$percentComplete."
				});
			});
		</script>";
		$progressbarCounter++;
		return $res;
	}
}
