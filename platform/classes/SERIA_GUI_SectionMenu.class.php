<?php
	class SERIA_GUI_SectionMenu {
		protected $gui;
		protected $_title = false;
		protected $_contents = false;
		protected $_links = false;
		protected $_type = false;
		protected $_items = array();
		
		private $linkMode = false;
		
		public function __construct(SERIA_GUI $gui, $title = false, $weight = 0) {
			if ($title) {
				$this->_title = $title;
			}
			$this->gui = $gui;
		}

		public function getTitle()
		{
			return $this->_title;
		}
		public function setTitle($title)
		{
			$this->_title = $title;
		}

		public function contents($contents)
		{
			if($this->_type!=='contents' && $this->_type!==false) throw new SERIA_Exception('Already populated by '.$this->_type);
			$this->_type = 'contents';
			$this->_contents = $contents;
			return $this;
		}

		public function addBlock($caption, $contents)
		{
			if($this->_type!=='html' && $this->_type!==false) 
				throw new  SERIA_Exception('Already populated by '.$this->_type);
			$this->_type = 'html';

			$this->_items[] = '<div class="sectionMenuBlock"><h5 class="legend">'.$caption.'</h5><div class="blockContents">'.$contents.'</div></div>';
			return $this;
		}

		public function addHtml($html) {
			if($this->_type!=='html' && $this->_type!==false) 
				throw new  SERIA_Exception('Already populated by '.$this->_type);
			$this->_type = 'html';

			$this->_items[] = '<div class="sectionMenuItem">'.$html.'</div>';
			return $this;
		}
		
		public function addLink($title, $location, $options = array()) {
			//if($this->_type!=='link' && $this->_type!==false) 
			//	throw new SERIA_Exception('Already populated by '.$this->_type);
			$this->_type = 'links';

			if($this->_links === false)
				$this->_links = array();

			$this->_links[] = '<li'.(isset($options['cssClass'])?' class="'.$options['cssClass'].'"':'').'><a href="' . htmlspecialchars($location) . '">' . htmlspecialchars($title) . '</a></li>';
			return $this;
		}

		public function render()
		{
			$html = '<div class="sectionMenu">';
			if($this->_title!==false)
				$html .= '<h4 class="legend">'.$this->_title.'</h4>';
			switch($this->_type)
			{
				case 'contents' : $html .= '<div class="sectionMenuContents">'.$this->_contents.'</div>';
					break;
				case 'links' : $html .= '<ul class="sectionMenuLinks">'.implode('',$this->_links).'</ul>';
					break;
				case 'html' : $html .= '<div class="sectionMenuItems">'.implode('',$this->_items).'</div>';
					break;
			}
			$html .= '</div>';
			
			return $html;
		}


// DEPRECTATED:
		public function publish() {
			throw new Exception('DEPRECATED');
			if($this->_title !== false)
				$this->addHtml('<h1 class="legend">' . $this->_title . '</h1>');

			if($this->_contents !== false)
			{
				$this->addHtml($this->_contents);
			}
			else if($this->_links !== false) 
			{
				$this->addHtml('<ul>'.implode('', $this->_links).'</ul>');
			}
			$this->gui->addHtmlToSectionMenu(implode('', $this->_items));
		}
	}
