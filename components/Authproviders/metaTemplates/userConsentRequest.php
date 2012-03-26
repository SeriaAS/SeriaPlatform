<s:gui title="{'Your permission is required'|_t}">
	<form method='post' action="<?php echo htmlspecialchars($this->postDataObject->getPostUrl()); ?>">
		<?php
			$data = $this->postDataObject->getPostData();
			foreach ($data as $name => $value) {
				?>
					<input type='hidden' name="<?php echo htmlspecialchars($name); ?>" value="<?php echo htmlspecialchars($value); ?>" />
				<?php
			}
		?>
		<div>
			<h1 class='legend'>{{'Your permission is required'|_t}}</h1>
			<p><?php echo _t('Do you want to allow %HOST% to access your personal profile?', array('HOST' => $this->postDataObject->getHostname())); ?></p>
			<div>
				<input type='submit' value="<?php echo htmlspecialchars(_t('Yes')); ?>" />
				<a href="<?php echo htmlspecialchars($this->abortUrl); ?>"><?php echo _t('No'); ?></a>
			</div>
		</div>
	</form>
</s:gui>