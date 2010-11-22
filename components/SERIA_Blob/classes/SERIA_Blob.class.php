<?php
	/**
	*	Represents a file. The following applies to special file types:
	*
	*	Images can be resized by applying parameters to the image url: <img src="'.$image.'?thumbnail=300x200">
	*	will create a scaled version to fit within 300x200 pixels and store it in dyn/images/$image_thumbnrail-300x200.jpg
	*	then rewrite the output.
	*
	*	Video files are automatically transcoded when uploaded, according to transcoding rules defined in the control
	*	panel.
	*
	*	Thumbnails of videos can be created by adding the query parameter ?thumbnail=300x200&offset=0.5
	*	where 0.5 means at 50 % of the video file, while any number 1 or larger will give a thumbnail at the keyframe closest
	*	to the second specified.
	*/
	class SERIA_Blob extends SERIA_MetaObject
	{
		public static function Meta($instance=NULL)
		{
			return array(
				'table' => '{blob}',
				'displayField' => 'name',
				'fields' => array(
					'name' => array('filename required', _t("Filename")),
					'type' => array('SERIA_BlobType required', _t("Filetype")),
					'filepath' => array('filepath required', _t("Physical path")),	// path relative to SERIA_FILES_ROOT
					'cdnHost' => array('SERIA_BlobCdn', _t("CDN host")),		// host serving this file, for example Amazon S3
					'cdnUrl' => array('url', _t("Off-site url")),			// when the file have been uploaded to the CDN, the url will be set here
					'derivedFrom' => array('SERIA_Blob', _t("Derived from")),	// if this file is a thumbnail
					'createdBy' => 'createdBy',
					'createdDate' => 'createdDate',
				),
			);
		}

		public static function templateFilter($buffer)
		{
			//@TODO: Identify image urls within the HTML, resize then rewrite the files.
			return $buffer;
		}
	}

	SERIA_Template::addOutputFilter(array('SERIA_Blob','templateFilter'));
