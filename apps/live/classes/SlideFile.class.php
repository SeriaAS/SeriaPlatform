<?php
	/**
	*	Represents a single slide file, for example a PDF, a PPT or a ZIP.
	*	Currently only PDF is supported.
	*/
	class SlideFile extends SERIA_MetaObject {
		public static function Meta($instance=NULL) {
			return array(
				'table' => 'live_slidefiles',
				'fields' => array(
					'name' => array('name required unique(webcastId)', _t("Name")),		// name used to identify this webcast within the webcast
					'webcastId' => array('integer required', _t("Webcast")),	// the webcast
					'originalName' => array('name', _t("Original filename")),		// the filename that this file was uploaded from
					'path' => array('filepath', _t("Path")),					// path relative to SERIA_FILES_ROOT/SERIA_FILES_HTTP_ROOT
					'createdBy' => 'createdBy',
					'createdDate' => 'createdDate',
					'modifiedBy' => 'modifiedBy',
					'modifiedDate' => 'modifiedDate',
				),
			);
		}

		public function getSlides($width) {
			$slides = SERIA_Meta::all('Slide')->where('slideFile=:id AND width='.intval($width), $this)->order('num');
			return $slides;
		}

		public function MetaIsInvalid() {
			if(!file_exists('/usr/bin/convert'))
				return array('path' => _t("I am unable to convert this file type since it requires imagemagick to be installed in /usr/bin/convert"));
			if(!file_exists(SERIA_FILES_ROOT.'/'.$this->get('path')))
				return array('path' => _t("The file must exist in this location (%PATH%)", array("PATH" => SERIA_FILES_ROOT.'/'.$this->get('path') )));
			return false;
		}

		public function MetaAfterSave()
		{ // transcode the files and create Slide objects
// todo: make sure not converting when already converted.

			if(!file_exists(SERIA_FILES_ROOT.'/serialive/'.$this->get('webcastId').'/pres/'.$this->get('id')))
			{
				$mask = umask(0);
				mkdir(SERIA_FILES_ROOT.'/serialive/'.$this->get('webcastId').'/pres/'.$this->get('id'), 0777, true);
				umask($mask);
			}

//			shell_exec('/usr/bin/convert -colorspace RGB -resize 1280 -interlace none -density 300 -quality 80 '.escapeshellarg(SERIA_FILES_ROOT.'/'.$this->get('path')).' '.escapeshellarg(SERIA_FILES_ROOT.'/serialive/'.$this->get('webcastId').'/pres/'.$this->get('id').'/%d-1280.jpg'));
			shell_exec('/usr/bin/convert -colorspace RGB -resize 800 -interlace none -density 300 -quality 80 '.escapeshellarg(SERIA_FILES_ROOT.'/'.$this->get('path')).' '.escapeshellarg(SERIA_FILES_ROOT.'/serialive/'.$this->get('webcastId').'/pres/'.$this->get('id').'/%d-800.jpg'));
//			shell_exec('/usr/bin/convert -colorspace RGB -resize 400 -interlace none -density 300 -quality 80 '.escapeshellarg(SERIA_FILES_ROOT.'/'.$this->get('path')).' '.escapeshellarg(SERIA_FILES_ROOT.'/serialive/'.$this->get('webcastId').'/pres/'.$this->get('id').'/%d-400.jpg'));
//			shell_exec('/usr/bin/convert -colorspace RGB -resize 150 -interlace none -density 300 -quality 80 '.escapeshellarg(SERIA_FILES_ROOT.'/'.$this->get('path')).' '.escapeshellarg(SERIA_FILES_ROOT.'/serialive/'.$this->get('webcastId').'/pres/'.$this->get('id').'/%d-150.jpg'));

			$files = glob(SERIA_FILES_ROOT.'/serialive/'.$this->get('webcastId').'/pres/'.$this->get('id').'/*.jpg');
			foreach($files as $file)
			{
				$pi = pathinfo($file);
				list($i, $width) = explode("-", $pi['filename']);
				$slide = new Slide();
				$slide->set('num', $i+1);
				$slide->set('width', $width);
				$slide->set('slideFile', $this);
				$slide->set('path', 'serialive/'.$this->get('webcastId').'/pres/'.$this->get('id').'/'.$pi['basename']);
				SERIA_Meta::save($slide);
			}
		}

		public static function loadByName(SERIA_LiveArticle $webcast, $name)
		{
			$rs = SERIA_Meta::all('SlideFile')->where('webcastId=:webcastId AND name=:name', array(
				'webcastId' => $webcast->get('id'),
				'name' => $name,
			));
			return $rs->current();
		}

		public function create(SERIA_LiveArticle $webcast, $name, $path, $originalFileName=NULL) {
			if(self::loadByName($webcast, $name))
				throw new SERIA_Exception('This slideset already exists for this webcast. Must delete it first.');

			$pi = pathinfo($path);
			if($originalFileName!==NULL)
			{
				$opi = pathinfo($originalFileName);
				$pi['extension'] = $opi['extension'];
				$pi['filename'] = $opi['filename'];
				$pi['basename'] = $opi['basename'];
			}
			if(strtolower($pi['extension']) != 'pdf')
				throw new SERIA_Exception('Illegal extension "'.$pi['extension'].'". Use PDF-files please.');

			$mask = umask(0);
			if(!file_exists(SERIA_FILES_ROOT.'/serialive/'.$webcast->get('id').'/pres'))
				mkdir(SERIA_FILES_ROOT.'/serialive/'.$webcast->get('id').'/pres', 0777, true);

			$targetName = $pi['basename'];
			$i = 0;
			while(file_exists(SERIA_FILES_ROOT.'/serialive/'.$webcast->get('id').'/pres/'.$targetName))
			{
				$i++;
				$targetName = $pi['filename'].'-'.$i.'.'.$pi['extension'];
			}
			copy($path, SERIA_FILES_ROOT.'/serialive/'.$webcast->get('id').'/pres/'.$targetName);
			$slideFile = new SlideFile();
			$slideFile->set('name', $name);
			$slideFile->set('webcastId', $webcast->get('id'));
			$slideFile->set('originalName', $pi['basename']);
			$slideFile->set('path', 'serialive/'.$webcast->get('id').'/pres/'.$targetName);
			SERIA_Meta::save($slideFile);
			umask($mask);
			return $slideFile;
		}
	}
