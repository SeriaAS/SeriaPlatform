<?php

class SERIA_BMLTemplateLayout
{
	protected $layoutImg = '';
	protected $generatorType = false;
	protected $templateId = 0; /* multiple templates can be cached with different settings */

	protected $contentAreas = array('CONTENTS'); /* override to set %%<field-names>%% that should be specified for template */

	public function serializeParams()
	{
		$settings = array(
			'img' => $this->layoutImg,
			'generatorType' => $this->generatorType,
			'templateId' => $this->templateId
		);
		return serialize($settings);
	}
	public function setParams($settings)
	{
		if (isset($settings['img']))
			$this->layoutImg = $settings['img'];
		if (isset($settings['templateId']))
			$this->templateId = $settings['templateId'];
	}
	public function unserializeParams($params)
	{
		$this->setParams(unserialize($params));
	}

	protected function __construct($layoutImg)
	{
			$this->layoutImg = $layoutImg;
	}

	public function getCacheDir()
	{
		if (defined('SERIA_AUTOTEMPLATE_CACHEDIR'))
			$cacheDir = SERIA_AUTOTEMPLATE_CACHEDIR;
		else
			$cacheDir = SERIA_ROOT.'/autotemplate';
		$cacheDir .= '/'.$this->templateId;
		if (!file_exists($cacheDir))
			mkdir($cacheDir, 0755);
		return $cacheDir;
	}
	public function getCacheHTTPDir()
	{
		if (defined('SERIA_AUTOTEMPLATE_CACHEHTTP'))
			$cacheHttpDir = SERIA_AUTOTEMPLATE_CACHEHTTP;
		else
			$cacheHttpDir = SERIA_HTTP_ROOT.'/autotemplate';
		$cacheHttpDir .= '/'.$this->templateId;
		return $cacheHttpDir;
	}

	public static function createObject($layoutImg, $generatorType='Default')
	{
		$className = get_class();
		$className .= $generatorType;
		return new $className($layoutImg);
	}

	public function outputByteInHex($bt)
	{
		$dig0 = ($bt >> 4) & 0x0F;
		$dig1 = $bt & 0x0F;
		if ($dig0 > 9)
			$dig0 = substr('ABCDEF', $dig0 - 10, 1);
		if ($dig1 > 9)
			$dig1 = substr('ABCDEF', $dig1 - 10, 1);
		return $dig0.$dig1;
	}
	protected function generate()
	{
		throw new Exception('Load a layout class by calling the static createObject method for construct. This generator is not armed.');
	}

	public function getData()
	{
		static $settings = false;
		static $object = false;

		$setar = $this->serializeParams();
		if ($setar !== $settings) {
			$settings = $setar;
			$object = $this->generate();
		}
		return $object;
	}

	public function updateStorage()
	{
		$cacheDir = $this->getCacheDir();

		$ident = false;
		if (file_exists($cacheDir.'/ident.txt')) {
			$identFile = fopen($cacheDir.'/ident.txt', 'r');
			try {
				if (flock($identFile, LOCK_SH)) {
					$ident = fread($identFile, 16384);
				} else
					throw new Exception('File lock failed');
			} catch (Exception $e) {
				fclose($identFile);
				throw $e;
			}
			fclose($identFile);
		}
		if ($ident == $this->serializeParams())
			return;

		/*
		 * Do update the storage..
		 */
		$data = $this->getData();
		$identFile = fopen($cacheDir.'/ident.txt', 'w+');
		if ($identFile === false)
			throw new Exception('Unable to create cache ident file. Cachedir: '.$cacheDir);
		try {
			if (flock($identFile, LOCK_EX)) {
				ftruncate($identFile, 0);
				fwrite($identFile, $this->serializeParams());

				/*
				 * Create html file..
				 */
				$files = array(
					'index.html' => $data['html'],
					'style.css' => $data['css'],
					'print.css' => "@media print { \n".$data['print_css']."\n}\n"
				);
				if (isset($data['ie8css']))
					$files['ie8.css'] = $data['ie8css'];
				foreach ($files as $filename => $output) {
					$outputFile = fopen($cacheDir.'/'.$filename, 'w');
					while ($output !== false) {
						$len = fwrite($outputFile, $output);
						if (!$len)
							throw new Exception('Write failure on autotemplate '.$filename);
						$output = substr($output, $len);
						if (strlen($output) == 0)
							$output = false;
					}
					fclose($outputFile);
				}
				foreach ($data['images'] as $filename => $imageobj) {
					if ($imageobj !== false)
						imagepng($imageobj, $cacheDir.'/'.$filename, 9, PNG_NO_FILTER);
				}
				foreach ($data['copy'] as $filename => $destfilename) {
					if ($filename !== false && $destfilename !== false)
						copy($filename, $cacheDir.'/'.$destfilename);
				}
			}
		} catch (Exception $e) {
			fclose($identFile);
			throw $e;
		}
		fclose($identFile);
	}

	public function output($contents=array())
	{
		foreach ($this->contentAreas as $contentArea) {
			if (!isset($contents[$contentArea]))
				$contents[$contentArea] = '';
		}

		$cacheDir = $this->getCacheDir();
		$cacheHttpDir = $this->getCacheHTTPDir();		

		$this->updateStorage();
		SERIA_Template::cssInclude($cacheHttpDir.'/style.css');
		SERIA_Template::cssInclude($cacheHttpDir.'/print.css');
		if (file_exists($cacheDir.'/ie8.css'))
			SERIA_Template::headEnd($cacheDir.'/ie8.css', seria_bml_iecond('IE 8')->addChild(
				seria_bml('link', array('rel' => 'stylesheet', 'type' => 'text/css', 'href' => ($cacheHttpDir.'/ie8.css'), 'title' => ''))
			)->output());
		$indexFile = fopen($cacheDir.'/index.html', 'r');
		$output = '';
		while (($data = fread($indexFile, 16384)))
			$output .= $data;
		foreach ($contents as $name => $value)
			$output = str_replace('%%'.$name.'%%', $value, $output);
		return $output;
	}
}

?>