<?php
/**
*	New user interface class for WIPS. To be used on all new popup windows, and eventually on the next version of the main window.
*
*	Usage:
*	$gui = new SERIA_ModernGUI($pageTitle);
*	$gui->contents = 'html';
*	echo $gui->render();
*/
class SERIA_ModernGUI {

	public static $template = NULL;
	public static $defaults = array();
	public $head = array();
	public $scripts = array();
	public $title = 'No title defined';
	public $intro = NULL; // Introdution header

	public function __construct($title) {
		$this->title = $title;
	}

	public function render($template=NULL) {
		foreach(self::$defaults as $k => $v) {
			if(is_array($v)) {
				if(isset($this->$k)) {
					if(is_array($this->$k)) $this->$k = array_merge($this->$k, $v);
				} else {
					$this->$k = $v;
				}
			} else if(!isset($this->$k)) $this->$k = $v;
		}

		if($template!==NULL);
		else if(self::$template!==NULL) $template = self::$template = dirname(dirname(__FILE__)).'/templates/seria_admin.php';
		else return "No template specified. Use GUI::\$template to specify a default template, or send template filename as argument to GUI::render().";

		ob_start();

		require($template);
		$result = ob_get_contents();
		ob_end_clean();
		return $result;
	}

	public function button($caption, $url, $style='', $icon=FALSE) {
		return '<a href="'.htmlspecialchars($url, ENT_COMPAT, 'UTF-8').'" class="button" style="'.htmlspecialchars($style, ENT_COMPAT, 'UTF-8').'">'.($icon!==FALSE?'<span class="ui-icon ui-icon-'.$icon.'"></span>':'').$caption.'</a>';
	}

	public function box($title, $contents) {
		return '<div class="ui-tabs ui-widget ui-widget-content ui-corner-all"><div class="header ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-top">'.$title.'</div><div class="contents ui-widget-content ui-corner-bottom">'.$contents.'</div></div>';
	}

	/**
	*	Table is built identical to the structure of $data. First row must contain column headers.
	*	$settings = array(
	*		'widths' => array(100,200,NULL,200),
	*		'onclicks' => array(
	*			[row] => 'javascript',
	*		),
	*	);
	*/
	public function table($data, $settings=array()) {
		ob_start();
		echo '<table class="grid" style="border-collapse:collapse;"><thead><tr>';

		if(isset($settings['widths'])) {
			foreach($data[0] as $i => $th) {
				echo "<th style='".((isset($settings['widths']) && isset($settings['widths'][$i]) && $settings['widths'][$i])?"width: ".$settings['widths'][$i]."px":"")."'>".$th."</th>";
			}
			echo "</tr></thead><tbody>";
		} else {
			echo '<th>'.implode('</th><th>', $data[0]).'</th></tr></thead><tbody>';
		}
		$l = sizeof($data);
		for($i = 1; $i < $l; $i++) {
			if(isset($settings['onclicks'])) {
				echo '<tr class="clickable" data-onclick="'.htmlspecialchars($settings['onclicks'][$i-1], ENT_COMPAT, 'UTF-8').'"><td>'.implode('</td><td>', $data[$i]).'</td></tr>';
			} else {
				echo '<tr><td>'.implode('</td><td>', $data[$i]).'</td></tr>';
			}
		}
		echo "</tbody></table>";
		$result = ob_get_contents();
		ob_end_clean();
		return $result;
	}
}
