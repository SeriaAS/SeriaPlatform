<?php
	/**
	*	An interface for classes that assist in storage of blobs to various backends,
	*	such as the local file system or Amazon S3.
	*/
	interface SERIA_IBlobBackend {
		/**
		*	Constructor responsibilities:
		*
		*	If $filename!==NULL means that this is a new file to store:
		*		If this blob backend is asynchronous, that is - if it must upload the file to another backend
		*		and this is done asynchronously:
		*			$blob->set("isAccessible", false);
		*			$blob->set("backendData", serialize([Some data needed by the blob backend]));
		*			[ enqueue the uploading of the file, remember to delete the original once successful and set isAccessible to true once the file can be accessed ]
		*			SERIA_Meta::save($blob);
		*		If synchronous, that is the file is available for download immediately:
		*			$blob->set("isAccessible", true);
		*			$blob->set("backendData", serialize([Some data needed by the blob backend]));
		*			[ move uploaded file to its final location ]
		*			SERIA_Meta::save($blob);
		*
		*	If no filename is given:
		*		Do nothing, except keep a reference to $blob
		*/
		public function __construct(SERIA_Blob $blob, $filename=NULL);

		/**
		*	Delete the file:
		*		If synchronous, immediately delete the file. Call SERIA_Meta::delete($blob);
		*		If async, enqueue deletion of the file. Call SERIA_Meta::delete($blob);
		*/
		public function delete();

		/**
		*	Should return a URL that can be used to access the file. Scheme is a required parameter defining
		*	what URL is wanted. Usually "http" or "https" but also "rtmp" and similar.
		*/
		public function getUrl($protocol);
	}
