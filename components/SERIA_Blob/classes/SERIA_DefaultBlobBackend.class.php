<?php
	/**
	*	Adds no special functionality to blobs. They are stored in a custom hierarchy below the 
	*	SERIA_FILES_ROOT:
	*	/text/txt/2011/01/17/filename.txt
	*
	*	text = the group the extension has been determined to belong to
	*	txt = the actual extension of the file. Certain extensions such as jpeg are mapped to jpg.
	*	2011/01/17 = the date the file was added, allowing storage of old files on slower storage perhaps.
	*/
	class SERIA_DefaultBlobBackend implements SERIA_IBlobBackend {
		protected $blob;

		public function __construct(SERIA_Blob $blob, $filename=NULL) {
			$this->blob = $blob;

			if($filename !== NULL)
			{
				$pi = pathinfo($filename);

				if(empty($pi['extension']))
					throw new SERIA_Exception('Extensionless files are not supported', SERIA_Exception::NOT_IMPLEMENTED);

				$blob->set('originalName', $pi['basename']);
				$blob->set('filesize', filesize($filename));
				$blob->set('isAccessible', true);
				$blob->set('backendClass', "SERIA_DefaultBlobBackend");

				$pathPrefix = self::filenameToPath($filename);
				$i = 1;
				$newFilename = $filename;
				while(file_exists(SERIA_FILES_ROOT.'/'.$pathPrefix.'/'.$newFilename))
				{
					$newFilename = $pi['filename'].'-'.$i++.'.'.$pi['extension'];
				}

				$data = array(
					'localPath' => $pathPrefix.'/'.$newFilename,
				);

				$mask = umask(0);
				mkdir(SERIA_FILES_ROOT.'/'.$pathPrefix, 0755, true);
				if(!rename($filename, SERIA_FILES_ROOT.'/'.$data['localPath']))
				{
					umask($mask);
					throw new SERIA_Exception('I was unable to move the file "'.$filename.'" to its target location.');
				}
				chmod(SERIA_FILES_ROOT.'/'.$data['localPath'], 0644);
				umask($mask);
				$blob->set('backendData', serialize($data));
			}
		}

		public function getUrl($scheme) {

			$url = SERIA_FILES_HTTP_ROOT;
			switch($scheme)
			{
				case 'http' :
					if(substr($url,0,5)!=='http:')
						$url = 'http://'.substr(SERIA_FILES_HTTP_ROOT, 8);
					break;
				case 'https' :
					if(substr($url,0,6)!=='https:')
						$url = 'https://'.substr(SERIA_FILES_HTTP_ROOT, 7);
					break;
				default :
					throw new SERIA_Exception('Unsupported scheme "'.$scheme.'".');
			}
			$data = unserialize($this->blob->get('backendData'));
			return $url.'/'.$data['localPath'];
		}

		public function delete() {
			$data = unserialize($this->blob->get('backendData'));
			if(!unlink(SERIA_FILES_ROOT.'/'.$data['localPath']) && file_exists(SERIA_FILES_ROOT.'/'.$data['localPath']))
				throw new SERIA_Exception('I was unable to delete the file from its current location.');
			// if the folder is empty, no need to keep it
			$files = glob(SERIA_FILES_ROOT.'/'.dirname($data['localPath']).'/*');
			if(sizeof($files)<=2)
				rmdir(SERIA_FILES_ROOT.'/'.dirname($data['localPath']));
		}

		protected static function filenameToPath($filename)
		{
			$map = array(
				"mpe"	=>	"media",
				"f4v"	=>	"media",
				"f4p"	=>	"media",
				"m4v"	=>	"media",
				"mp4"	=>	"media",
				"mpeg4"	=>	"media",
				"264"	=>	"media",
				"m4a"	=>	"media",
				"f4a"	=>	"media",
				"f4b"	=>	"media",
				"flv"	=>	"media",
				"3gp"	=>	"media",
				"3gpp"	=>	"media",
				"rm"	=>	"media",
				"mkv"	=>	"media",
				"wmv"	=>	"media",
				"wma"	=>	"media",
				"mka"	=>	"media",
				"ogg"	=>	"media",
				"mp2"	=>	"media",
				"mp3"	=>	"media",
				"qt"	=>	"media",
				"mpg"	=>	"media",
				"mpeg"	=>	"media",
				"avi"	=>	"media",
				"mov"	=>	"media",
				"jpeg"	=>	"media/jpg",
				"jpe"	=>	"media/jpg",
				"jpg"	=>	"media/jpg",
				"ras"	=>	"media",
				"gif"	=>	"media",
				"xwd"	=>	"media",
				"pbm"	=>	"media",
				"bmp"	=>	"media",
				"tif"	=>	"media/tif",
				"tiff"	=>	"media/tif",
				"png"	=>	"media",
				"xpm"	=>	"media",
				"ai"	=>	"media",
				"ps"	=>	"media",
				"pdf"	=>	"media",
				"eps"	=>	"media",
				"sxd"	=>	"media",
				"html"	=>	"text/html",
				"xml"	=>	"text",
				"htm"	=>	"text/html",
				"xhtml"	=>	"text/html",
				"css"	=>	"text",
				"txt"	=>	"text",
				"asc"	=>	"text",
				"rtf"	=>	"text",
				"zip"	=>	"archive",
				"rar"	=>	"archive",
				"tar.gz" =>	"archive",
				"tar.bz2" =>	"archive",
				"sxw"	=>	"text",
				"doc"	=>	"text",
				"docx"	=>	"text",
				"sxw"	=>	"text",
				"csv"	=>	"spreadsheet",
				"sxc"	=>	"spreadsheet",
				"xls"	=>	"spreadsheet",
				"xlsx"	=>	"spreadsheet",
				"sxi"	=>	"presentation",
				"ppt"	=>	"presentation",
				"pptx"	=>	"presentation",
				"sxm"	=>	"other",
				"sxg"	=>	"text",
			);
			$pi = pathinfo($filename);
			if(empty($pi['extension']))
			{
				$path = "blob";
			}
			else if(isset($map[$ext = strtolower($pi['extension'])]))
			{
				$parts = explode("/", $map[$ext]);
				$path = $parts[0];
				if(empty($parts[1]))
					$path .= "/".$ext;
				else
					$path .= "/".$parts[1];
			}
			else $path = "blob";

			$path .= "/".date("Y/m/d");
			return $path;
		}
	}
