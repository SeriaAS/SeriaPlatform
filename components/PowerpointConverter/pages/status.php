<?php
	/*SERIA_Base::pageRequires('admin');*/
	set_time_limit(240);
?><s:gui title="{'Powerpoint conversion status'|_t|htmlspecialchars}">
	<?php
		$this->gui->activeMenuItem('controlpanel/other/powerpoint/status');
	?>
	<h1 class='legend'>{{'Powerpoint conversion status'|_t}}</h1>
	<p>{{'The ten last conversion jobs are displayed below.'}}</p>
	<?php
		$files = array();
		$ids = array_keys($this->fileTranscodings);
		while (count($files) < 10 && $ids) {
			$id = array_pop($ids);
			$files[$id] = $this->fileTranscodings[$id];
		}
		ksort($files);
		?>
		<table class='grid'>
			<thead>
				<tr>
					<th style='width: 50px;'>{{"File ID"|_t}}</th>
					<th>{{"Filename"|_t}}</th>
					<th style='width: 100px;'>{{"Status"|_t}}</th>
					<th style='width: 50px;'>{{"Progress"|_t}}</th>
					<th>{{"Description"|_t}}</th>
				</tr>
			</thead>
			<tfoot>
			</tfoot>
			<tbody>
				<?php
					foreach ($files as $id => $cdata) {
						$file_id = $cdata[0];
						$status = $cdata[1];
						try {
							$file = SERIA_File::createObject($file_id);
							$filename = $file->get('filename');
						} catch (Exception $e) {
							$filename = '-';
						}
						$statusCodes = array();
						$statusCodes[SERIA_FileTranscoder::STATUS_COMPLETED] = _t('Completed');
						$statusCodes[SERIA_FileTranscoder::STATUS_FAILED] = _t('Failed');
						$statusCodes[SERIA_FileTranscoder::STATUS_QUEUED] = _t('In queue');
						$statusCodes[SERIA_FileTranscoder::STATUS_RESTART] = _t('Can be restarted');
						$statusCodes[SERIA_FileTranscoder::STATUS_TRANSCODING] = _t('Processing');
						if (isset($statusCodes[$status]))
							$status = $statusCodes[$status];
						try {
							$info = new PowerpointConverterJobInformation($id);
							$percent = $info->get('progress');
							$description = $info->get('description');
						} catch (Exception $e) {
							$percent = 0;
							$description = _t('Can\'t get info: %EXC%', array('EXC' => $e->getMessage()));
						}
						?>
							<tr>
								<td>{{$file_id}}</td>
								<td>{{$filename}}</td>
								<td>{{$status}}</td>
								<td><?php echo _t('%PERCENT%%', array('PERCENT' => $percent)); ?></td>
								<td>{{$description}}</td>
							</tr>
						<?php
					}
				?>
			</tbody>
		</table>
		<?php
	?>
</s:gui>