<?php

class SERIA_PowerpointMSOfficeConverterLowlevel
{
	protected $ppApp = null;
	protected $ppPres = null;

	public function __construct($filename)
	{
		if (!file_exists($filename))
			throw new SERIA_Exception('File not found: '.$filename);
		$rp = realpath($filename);
		if (!$rp || !file_exists($rp))
			throw new SERIA_Exception('File not found: '.$rp.' (orig: '.$filename.')');
		$this->ppApp = new COM('Powerpoint.Application');
		$this->ppApp->Visible = true;
		$tries = 0;
		$notDone = true;
		while ($notDone) {
			try {
				sleep(5);
				$this->ppPres = $this->ppApp->Presentations->Open($rp, true, false, false);
				$notDone = false;
			} catch (com_exception $e) {
				if ($tries > 10)
					throw $e;
			}
			if ($tries > 20)
				throw new Exception('PROT');
			$tries++;
		}
	}
	public function __destruct()
	{
		if ($this->ppPres !== null) {
			$this->ppPres->Close();
			$this->ppPres = null;
		}
		$this->ppApp = null;
	}

	public function getNumberOfSlides()
	{
		return $this->ppPres->Slides->Count;
	}

	public function exportSlide($slide, $filename, $msfilter=false)
	{
		$slide = $this->ppPres->Slides[$slide];
		if ($msfilter === false) {
			$msfilter = pathinfo($filename, PATHINFO_EXTENSION);
			if (!$msfilter)
				throw new Exception('File extension required for automatic msfilter selection');
		}
		$slide->Export($filename, $msfilter);
	}
}