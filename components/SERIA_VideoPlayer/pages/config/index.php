<?php
	SERIA_Base::pageRequires('admin');
	SERIA_Base::viewMode('admin');

	if($_POST['removeCustomPlayer']) {
		if(file_exists(SERIA_DYN_ROOT.'/SERIA_VideoPlayer/SeriaPlayer.swf')) {
			$backupFiles = glob(SERIA_DYN_ROOT.'/SERIA_VideoPlayer/SeriaPlayer_'.date("Y-m-d").'*');
			$nextBackupInt = sizeof($backupFiles);

			if($nextBackupInt == 0) {
				if(copy(SERIA_DYN_ROOT."/SERIA_VideoPlayer/SeriaPlayer.swf", SERIA_DYN_ROOT."/SERIA_VideoPlayer/SeriaPlayer_".date("Y-m-d").".swf"))
					unlink(SERIA_DYN_ROOT.'/SERIA_VideoPlayer/SeriaPlayer.swf');
			} else {
				if(copy(SERIA_DYN_ROOT.'/SERIA_VideoPlayer/SeriaPlayer.swf', SERIA_DYN_ROOT.'/SERIA_VideoPlayer/SeriaPlayer_'.date("Y-m-d").'_'.$nextBackupInt.'.swf'))
					unlink(SERIA_DYN_ROOT.'/SERIA_VideoPlayer/SeriaPlayer.swf');
			}
		}
		header("Location: ".SERIA_Meta::manifestUrl('videoplayer', 'config/index'));
	} else if($_FILES['customPlayer']) {
		mkdir(SERIA_DYN_ROOT.'/SERIA_VideoPlayer', 0700, true);
		$pi = pathinfo($_FILES['customPlayer']['name']);
		if($pi['extension'] != 'swf') {
			$fileError = '<span class="error">'._t("Requires a SWF file").'</span>';
		} else {
			$tmpName = mt_rand(0,9999999)."SeriaPlayer.".$pi['extension'];
			move_uploaded_file($_FILES['customPlayer']['tmp_name'], SERIA_TMP_ROOT.'/'.$tmpName);
			if(file_exists(SERIA_DYN_ROOT.'/SERIA_VideoPlayer/SeriaPlayer.swf')) {
				$backupFiles = glob(SERIA_DYN_ROOT.'/SERIA_VideoPlayer/SeriaPlayer_'.date("Y-m-d").'*');
				$nextBackupInt = sizeof($backupFiles);

				if($nextBackupInt == 0) {
					if(copy(SERIA_DYN_ROOT."/SERIA_VideoPlayer/SeriaPlayer.swf", SERIA_DYN_ROOT."/SERIA_VideoPlayer/SeriaPlayer_".date("Y-m-d").".swf"))
						unlink(SERIA_DYN_ROOT.'/SERIA_VideoPlayer/SeriaPlayer.swf');
					if(copy(SERIA_TMP_ROOT.'/'.$tmpName, SERIA_DYN_ROOT.'/SERIA_VideoPlayer/SeriaPlayer.swf'))
						unlink(SERIA_TMP_ROOT.'/'.$tmpName);
				} else {
					if(copy(SERIA_DYN_ROOT.'/SERIA_VideoPlayer/SeriaPlayer.swf', SERIA_DYN_ROOT.'/SERIA_VideoPlayer/SeriaPlayer_'.date("Y-m-d").'_'.$nextBackupInt.'.swf'))
						unlink(SERIA_DYN_ROOT.'/SERIA_VideoPlayer/SeriaPlayer.swf');
					if(copy(SERIA_TMP_ROOT.'/'.$tmpName, SERIA_DYN_ROOT.'/SERIA_VideoPlayer/SeriaPlayer.swf'))
						unlink(SERIA_TMP_ROOT.'/'.$tmpName);
				}

			}
		}
		header("Location: ".SERIA_Meta::manifestUrl('videoplayer', 'config/index'));
	}


?><s:gui title="{'Configure your videoplayer'|_t}">
<h1 class="legend">{{"Configure your videoplayer"|_t}}</h1>
<script type="text/javascript">
	function removeCustomPlayer()
	{
		document.getElementById("removeCustomPlayer").value = 1;
		document.getElementById("configForm").submit();
	}
</script>
<form method="post" class="SERIA_Form SERIA_ActionForm" enctype="multipart/form-data" accept-charset="UTF-8" id="configForm">
<table>
<thead></thead>
<tbody>
 <tr>
  <td style="width:554px;">
	<div style="width:100%;">
		<div style="width:230px;height:50px;border:1px solid #eee; background-color:#F5F5F5;float:left;">
		<span style="font-size:12pt;line-height:50px;margin:4px;"><?php
			if(file_exists(SERIA_DYN_ROOT.'/SERIA_VideoPlayer/SeriaPlayer.swf')) {
				$usingCustom = true;
				echo _t("Currently using %CUSTOM% player", array('CUSTOM' => '<strong>'._t("custom").'</strong>'));
			} else {
				$usingCustom = false;
				echo _t("Currently using default player");
			}
		?></span>
		</div>
		<div style="float:left;margin:4px;width:300px;">
			<span><label for="customPlayer">{{'Upload new custom player'|_t}}</label></span>
			<input type="file" id="customPlayer" name="customPlayer" style="width:170px;" /><?php echo $fileError; ?>
			<input type="hidden" id="removeCustomPlayer" name="removeCustomPlayer" value="0" />
			<?php if($usingCustom) echo '<br><span><a href="#" onclick="removeCustomPlayer();return false;">'._t("Remove custom player").'</a></span>'; ?>
		</div>
		<div style="clear:both;"></div>
	</div>
  </td>
  <td style="width:414px;">
	<div style="width:412px;float:left; padding:2px; border:1px solid #C3C3C3;">
		<?php
			$video = SERIA_Meta::all('SERIA_Video')->current();

			echo $video->getPlayer(412,275);
		?>
	</div>
	<div style="clear:both;"></div>
  </td>
 </tr>
</tbody>
<tfoot>
 <tr>
  <td colspan="2">
   <input type="submit" class="submit" value="<?php echo _t("Save"); ?>" />
  </td>
  </tr>
</tfoot>
</table>
</form>
</s:gui>
