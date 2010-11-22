<?php
	SERIA_Template::jsInclude(SERIA_HTTP_ROOT.'/seria/platform/widgets/SERIA_ShareWithFriends/js/common.js');
	SERIA_Template::cssInclude(SERIA_HTTP_ROOT.'/seria/platform/widgets/SERIA_ShareWithFriends/templates/index.css');
	$drop_id = 'shareWithFriendsDrop'.mt_rand();
	if (isset($_GET['twitterfail']) && isset($_GET['twittertime']) && (intval($_GET['twittertime']) + 30) >= time()) {
		?>
			<script type='text/javascript'>
				$(document).ready(function () {
					alert("<?php echo htmlspecialchars($_GET['twitterfail']); ?>");
				});
			</script>
		<?php
		unset($_GET['twitterfail']);
	}
	$count = 0;
	foreach ($share as $val) {
		if ($val)
			$count++;
	}
	if ($count == 0)
		return;
	$twitterid = 'twittersys'.mt_rand();
	$qauth = mt_rand().mt_rand();
	if (!session_id())
		session_start();
	if (!isset($_SESSION['twitter_update_authorization']))
		$_SESSION['twitter_update_authorization'] = array();
	$_SESSION['twitter_update_authorization'][] = $qauth;
	if (count($_SESSION['twitter_update_authorization']) > 20)
		array_shift($_SESSION['twitter_update_authorization']);
?>
<div id='<?php echo $twitterid; ?>' class='twitter_submit_dialog_frame'>
	<div class='twitter_submit_dialog_inner_frame'>
		<form action='<?php echo htmlspecialchars($twitter_share_url); ?>' method='post'>
			<?php
				/*
				 * This makes it more difficult to mount cross-site session/scripting
				 * attacks on the twitter update call.
				 */
			?>
			<input id='<?php echo $twitterid; ?>_qn' type='hidden' name='queryNumber' value='0' %XHTML_CLOSE_TAG%>
			<script type='text/javascript'>
				(function () {
					var obj = document.getElementById('<?php echo $twitterid; ?>_qn');
					obj.value = "<?php echo htmlspecialchars($qauth); ?>";
				})();
			</script>
			<div class='twitter_submit_dialog'>
				<div class='twitter_submit_dialog_heading'>
					<h3><?php echo htmlspecialchars(_t('Submit status update to Twitter')); ?></h3>
				</div>
				<div>
					<label for='<?php echo $twitterid; ?>_text'><?php echo htmlspecialchars(_t('Edit your Twitter status update below (Will be truncated to maximum 140 characters):')); ?></label>
				</div>
				<div>
					<textarea id='<?php echo $twitterid; ?>_text' name='status_text' cols='50' rows='3'><?php echo htmlspecialchars(_t('Reading this: %URL%', array('URL' => $article_url))); ?></textarea>
				</div>
				<div class='twitter_dialog_buttons'>
					<button type='button' onclick='document.getElementById("<?php echo $twitterid; ?>").style.display = "none";'><?php echo htmlspecialchars(_t('Cancel'))?></button>
					<button type='submit'><?php echo htmlspecialchars(_t('Submit status update')); ?></button>
				</div>
			</div>
		</form>
	</div>
</div>
<div class='shareWithFriendsBox'>
	<div>
		<button type='button' onclick='SERIA.ShareWithFriends.toggleDropDown("<?php echo $drop_id; ?>");'><?php echo htmlspecialchars(_t('Share with friends')); ?></button>
	</div>
	<div class='shareWithFriendsDropDown' id='<?php echo $drop_id; ?>'>
		<div class='shareWithFriendsDropDownContainer'>
			<?php
			if (isset($share['twitter']) && $share['twitter']) {
				?>
					<div class='sharewithfriends_containers'>
						<a class='twitter_share' href='http://twitter.com/' onclick='SERIA.ShareWithFriends.toggleDropDown("<?php echo $drop_id; ?>"); document.getElementById("<?php echo $twitterid; ?>").style.display = "block"; return false;'><span class='twitter_icon'><img src="http://twitter-badges.s3.amazonaws.com/t_mini-a.png" alt="Twitter: " %XHTML_CLOSE_TAG%></span> <?php echo htmlspecialchars(_t('Share on Twitter')); ?></a>
					</div>
				<?php
			}
			if (isset($share['facebook']) && $share['facebook']) {
				SERIA_ScriptLoader::loadScript('Facebook-Share');
				$faceid = 'faceid'.mt_rand();
				?>
					<div class='sharewithfriends_containers'>
						<a id='<?php echo $faceid; ?>' name="fb_share" type="icon_link" share_url="<?php echo htmlspecialchars($article_url); ?>"><?php echo htmlspecialchars(_t('Share on Facebook')); ?></a>
						<script type='text/javascript'>
							<!--
								/*
								 * To hide the dropdown when a user clicks on 'Share on Facebook'
								 */
								(function () {
									var obj = document.getElementById('<?php echo $faceid; ?>');
									var superFunc = SERIA.ShareWithFriends.toggleDropDown;
									var setOnclickOverrideForTree = function(root, onclick)
									{
										var count = 0;
										if (!root.onclick)
											root.onclick = onclick;
										else {
											var superOnclick = root.onclick;
											var myOnclick = onclick;
											root.onclick = function () {
												var returnValue = superOnclick();
												myOnclick();
												return returnValue;
											}
										}
										for (var i = 0; i < root.children.length; i++)
											count += setOnclickOverrideForTree(root.children[i], onclick);
										count++;
										return count;
									}
									var onceFunc = function () {
										var nodes = setOnclickOverrideForTree(obj, function () {
											SERIA.ShareWithFriends.toggleDropDown("<?php echo $drop_id; ?>");
										});
										if (nodes > 1) {
											/*
											 * Don't do again
											 */
											onceFunc = function () {
											}
										}
									}
									SERIA.ShareWithFriends.toggleDropDown = function (id) {
										onceFunc();
										superFunc(id);
									}
								})();
							-->
						</script>
					</div>
				<?php
			}
			?>
		</div>
	</div>
</div>