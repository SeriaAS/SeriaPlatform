<?php

/**
 * 
 * This interface describes a web-location. An object that is
 * accessible as a web resource.
 * @author janespen
 *
 */
interface SERIA_IWebLocation
{
	/**
	 *
	 * Get the title for this web-location.
	 * @return string
	 */
	function getTitle();
	/**
	 *
	 * Get the url for this web-location.
	 * @return SERIA_Url
	 */
	function getUrl();
}
