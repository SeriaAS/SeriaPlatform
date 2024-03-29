<?php
	/**
	*	Serves as an entry point for the {{Meta}} variable in templates.
	*	Provides:
	*	{{Meta.urls}}		Returns urls for all manifest based components and their pages.
	*	{{Meta.SERIA_Video}}	Returns a MetaQuery for all SERIA_Video. Can be used in loops:
	*		<s:loop trough="{Meta.SERIA_Video}" as="{$video}">
	*			Id: {{$video.id}}
	*		</s:loop>
	*/
	class SERIA_MetaTemplateVariable implements ArrayAccess
	{
		public function offsetExists($offset) {
			return $this->offsetGet($offset) ? true : false;
		}
		public function offsetGet($offset) {
			switch($offset) {
				case "urls" :
					/**
					* {{Meta.urls.controlpanel}} gives the home url of the control panel.
					* {{Meta.urls.seriawebtv.videos}} gives the video page of the Seria WebTV application
					*/
					return new SERIA_MetaTemplateUrlsVariable();
					break;
				default :
					/**
					* {{Meta.SERIA_Video}} gives all videos from the seriawebtv application.
					* {{Meta.SERIA_User}} gives all users (once SERIA_User is SERIA_MetaObject).
					*/
					if(class_exists($offset) && is_subclass_of($offset, 'SERIA_MetaObject'))
						return new SERIA_MetaTemplateMetaQueryVariable($offset);
					break;
			}
			return false;
		}
		public function offsetSet($offset, $value) { throw new SERIA_Exception('Unable to assign values to Meta.', SERIA_Exception::INCORRECT_USAGE);}
		public function offsetUnset($offset) { throw new SERIA_Exception('Unable to unset values on Meta.', SERIA_Exception::INCORRECT_USAGE);}
	}
