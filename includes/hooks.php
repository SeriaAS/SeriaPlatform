<?php
	/**
	*	Constants identifying all hooks available trough the Core Seria Platform
	*/

	/**
	*	Invoked every minute, or whenever maintain is run, sometimes more often than every minute.
	*/
	define('SERIA_MAINTAIN_HOOK', 'seria_maintain');

	/**
	*	Invoked every minute, or whenever maintain is run, but never more often than every minute.
	*/
	define('SERIA_MAINTAIN_1_MINUTE_HOOK', 'seria_maintain_1_minute');

	/**
	*	Invoked every five minutes, or whenever maintain is run but never more often than every five minutes.
	*/
	define('SERIA_MAINTAIN_5_MINUTES_HOOK', 'seria_maintain_5_minutes');

	/**
	*	Invoked every fifteen minutes, or whenever maintain is run but never more often than every 15 minutes.
	*/
	define('SERIA_MAINTAIN_15_MINUTES_HOOK', 'seria_maintain_15_minutes');

	/**
	*	Invoked every 30 minutes, or whenever maintain is run but never more often than every 30 minutes.
	*/
	define('SERIA_MAINTAIN_30_MINUTES_HOOK', 'seria_maintain_30_minutes');

	/**
	*	Invoked every 60 minutes, or whenever maintain is run but never more often than every 60 minutes.
	*/
	define('SERIA_MAINTAIN_1_HOUR_HOOK', 'seria_maintain_1_hour');

	/**
	*	Invoked every night, at first possible time after 02:00
	*/
	define('SERIA_MAINTAIN_NIGHTLY_HOOK', 'SERIA_MAINTAIN_NIGHTLY_HOOK');

	/**
	*	Invoked every week
	*/
	define('SERIA_MAINTAIN_WEEKLY_HOOK', 'SERIA_MAINTAIN_WEEKLY_HOOK');

	/**
	*	Invoked on every page view that does not have a target; for example if the file does not exist. (dispatchToFirst)
	*/
	define('SERIA_ROUTER_HOOK', 'seria_router');

	/**
	*	Invoked on the output buffer after SERIA_Template have finished.
	*/
	define('SERIA_TEMPLATE_OUTPUT_HOOK', 'SERIA_TEMPLATE_OUTPUT_HOOK');

	/**
	 * Invoked when main.php returns.
	 */
	define('SERIA_PLATFORM_BOOT_COMPLETE_HOOK', 'SeriaPlatformBootComplete');

	class SERIA_PlatformHooks {
		/**
		* 	Invoked when a request is sent trough /index.php?q=some/path. Expected to integrate with the supplied $router object.
		*/
		const ROUTER_EMBED = 'SERIA_PLATFORM_ROUTER_EMBED';

		/**
		*	Invoked for unrouted pages; dispatchToFirst. Expected to render a page and then die();
		*/
		const ROUTER_FAILED = 'SERIA_PLATFORM_ROUTER_FAILED';

		/**
		*	Invoked every minute, or whenever maintain is run, sometimes more often than every minute.
		*/
		const MAINTAIN = 'seria_maintain';

	}
