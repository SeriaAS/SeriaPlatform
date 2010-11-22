<?php

class SERIA_MIMEEncoder
{
	public static function generateBoundary()
	{
		return '------------'.mt_rand().mt_rand().mt_rand();
	}
	/*
	 * Pass just the text/binary blocks that we will concatenate into MIME-multipart
	 */
	public static function multipartBoundary($blocks, $linesep="\r\n", &$boundary=null)
	{
		if (!$blocks)
			return '';
		do {
			$boundary = self::generateBoundary();
			$match = false;
			$data = '--'.$boundary;
			foreach ($blocks as $blk) {
				if (strpos($blk, $boundary) !== false) {
					$match = true; /* Boundary must not exist in the data */
					break; /* Retry as quickly as possible */
				}
				$data .= $linesep.$blk.$linesep.'--'.$boundary;
			}
		} while ($match);
		$data .= '--'.$linesep;
		return $data;
	}

	/*
	 * array(
	 *  item1 = array(
	 *   'headers' => array(
	 *    %HEADER% => %VALUE%
	 *    ...
	 *   ),
	 *   'content' => %CONTENT%
	 *  )
	 * )
	 */
	public static function multipartWithHeaders($items, $linesep="\r\n", &$boundary=null)
	{
		$blocks = array();
		foreach ($items as $item) {
			$headers = array();
			foreach ($item['headers'] as $nam => $val)
				$headers[] = $nam.': '.$val;
			$headers = implode($linesep, $headers);
			$blocks[] = $headers.$linesep.$linesep.$item['content'];
		}
		return self::multipartBoundary($blocks, $linesep, $boundary);
	}

	/*
	 * fields:
	 *  array(
	 *   %NAME% => %VALUE%
	 *   ...
	 *  )
	 * files:
	 *  array(
	 *   %NAME% => %FILEOBJ%
	 *  )
	 */
	public static function createMultipartPost($fields, $files, $linesep="\r\n", &$boundary=null)
	{
		$parts = array();
		foreach ($fields as $nam => $val) {
			$parts[] = array(
				'headers' => array(
					'Content-Disposition' => 'form-data; name="'.$nam.'"' /* TODO - Add name encoding !! */
				),
				'content' => $val
			);
		}
		foreach ($files as $nam => $file) {
			if (($local = $file->get('localPath')))
				$contents = file_get_contents($local);
			else
				throw new SERIA_Exception(_t('File not readable (not local).'));
			$parts[] = array(
				'headers' => array(
					'Content-Disposition' => 'form-data; name="'.$nam.'"; filename="'.$file->get('filename').'"',  /* TODO - Add name encoding !! */
					'Content-Type' => $file->get('contentType')
				),
				'content' => $contents
			);
		}
		return self::multipartWithHeaders($parts, $linesep, $boundary);
	}
}
