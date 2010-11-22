<?php

class ProcessManagementComponent extends SERIA_Component
{
	function getId()
	{
		return 'process_management_component';
	}
	function getName()
	{
		return _t('Process Management Component');
	}
	function embed()
	{
	}
	function getInstallationPath()
	{
		return dirname(dirname(__FILE__));
	}

	/**
	 *
	 * Get registered component instance.
	 * @return ProcessManagementComponent Instance
	 */
	public static function getComponent()
	{
		return SERIA_Components::getComponent('process_management_component');
	}

	public static function parseCsvLine($ln, $delim=',')
	{
		$whitespace = array(
			' ',
			"\t",
			"\n",
			"\r"
		);
		$cells = array();
		while (($ln = trim($ln))) {
			$cell = '';
			if ($ln[0] == '"') {
				$ln = substr($ln, 1);
				$ok = false;
				while ($ln && strlen($ln)) {
					if ($ln[0] != '"') {
						$cell .= $ln[0];
						$ln = substr($ln, 1);
					} else {
						if (strlen($ln) <= 1 || $ln[1] != '"') {
							$ln = substr($ln, 1);
							$ok = true;
							break;
						} else {
							$ln = substr($ln, 2);
							$cell .= '"';
						}
					}
				}
				if (!$ok)
					throw new SERIA_Exception('Unterminated string in CSV.');
			} else {
				while ($ln && strlen($ln) && $ln[0] != $delim && !in_array($ln[0], $whitespace)) {
					$cell .= $ln[0];
					$ln = substr($ln, 1);
				}
			}
			$ln = trim($ln);
			if ($ln && strlen($ln) && $ln[0] != $delim)
				throw new SERIA_Exception('CSV-parsing expected a delimiter: '.$delim);
			$ln = substr($ln, 1);
			$cells[] = $cell;
		}
		return $cells;
	}
	public function getWin32ProcessList()
	{
		exec('tasklist.exe /FO CSV /NH', $plist);
		/* Just simple CSV parsing */
		foreach ($plist as &$ln) {
			$ln = self::parseCsvLine($ln);
			$ln = call_user_func_array(array('ProcessInfo', 'createWin32ProcessInfo'), $ln);
		}
		return new ProcessInfoList($plist);
	}
}
