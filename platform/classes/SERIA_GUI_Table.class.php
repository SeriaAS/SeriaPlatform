<?php
	/**
	*	An object that represents an HTML table for use in the GUI of
	*	applications.
	*/
	class SERIA_GUI_Table extends SERIA_GUI_Widget
	{
		private $_id, $_head, $_foot, $_body, $_style, $_class;

		function __construct($id, $class="")
		{
			$this->_id = $id;
			$this->_class = $class;
		}

		function output()
		{
			$res = "<table id=\"".htmlspecialchars($this->_id)."\" name=\"".htmlspecialchars($this->_id)."\"".($this->_style?" style=\"".$this->_style."\"":"").">";

			if($this->_head)
				$res .= "<thead>".$this->_head."</thead>";

			if($this->_body)
				$res .= "<tbody>".$this->_body."</thead>";

			if($this->_foot)
				$res .= "<tfoot>".$this->_foot."</tfoot>";

			$res .= "</table>";

			return $res;
		}

		function head($head)
		{
			$this->_head = $head;
			return $this;
		}

		function body($body)
		{
			$this->_body = $body;
			return $this;
		}

		function foot($foot)
		{
			$this->_foot = $foot;
			return $this;
		}

		function style($style)
		{
			$this->_style = $style;
			return $this;
		}

		function className($style)
		{
			$this->_style = $style;
			return $this;
		}
	}
