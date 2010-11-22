<?php
	SERIA_Template::cssInclude(SERIA_HTTP_ROOT.'/seria/platform/widgets/SERIA_FileAttachments/css/style.css');
	SERIA_Template::jsInclude(SERIA_HTTP_ROOT.'/seria/platform/widgets/SERIA_FileAttachments/js/view_index.js');

	$idkey = mt_rand();
?>
<div class='file_attachment_view'>
	<?php
	$attachments = $this->getAttachments();
	if ($attachments) {
		?>
		<div>
			<button type='button' onclick='SERIA.FileAttachments.toggleVisible("<?php echo htmlspecialchars($this->getId()); ?>", "<?php echo htmlspecialchars($idkey); ?>");'><?php echo htmlspecialchars(_t('Show file attachments')); ?></button>
		</div>
		<div class='file_attachments_box' id='file_attachments_<?php echo htmlspecialchars($this->getId().'_'.$idkey); ?>' style='display: none;'>
			<ul class='file_attachments_box'>
				<?php
				foreach ($attachments as $attachment) {
					$file_article = $attachment->get('file_article_id');
					if ($file_article)
						$file_article = SERIA_Article::createObjectFromId($file_article);
					if ($file_article)
						$name = $file_article->get('title');
					else
						$name = false;
					if (!$name)
						$name = $attachment->get('filename');
					?>
						<li class='file_attachments_box'><a target='_blank' href='<?php echo htmlspecialchars($attachment->get('url')); ?>'><?php echo htmlspecialchars($name); ?></a></li>
					<?php
				}
				?>
			</ul>
		</div>
		<?php
	}
	?>
</div>