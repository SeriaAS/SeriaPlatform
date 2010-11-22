<div id='evalmaster_content'>
<?php

SERIA_Template::cssInclude(SERIA_HTTP_ROOT.'/seria/platform/widgets/SERIA_EventSubscription/templates/evaluation.css');

if ($form !== null) {
	?>
	<h2><?php echo htmlspecialchars(_t('%EVENT%: Please evaluate this event', array('EVENT' => $article->get('title')))); ?></h2>
	<p><?php echo htmlspecialchars(_t('Thank you for taking the time to evaluate this event. Please fill in values below and submit the form.')); ?></p>
	<?php
	echo $form->output(dirname(__FILE__).'/evaluation/form.php');
} else {
	?>
	<h2><?php echo htmlspecialchars(_t('%EVENT%: Event evalutation', array('EVENT' => $article->get('title')))); ?></h2>
	<p><?php echo htmlspecialchars(_t('Thank you very much for telling us what you think about this event. We appreciate your effort to help us improve.')); ?></p>
	<p><a href='<?php echo htmlspecialchars(SERIA_HTTP_ROOT); ?>'><?php echo htmlspecialchars(_t('Go to our front page.')); ?></a></p>
	<?php
}
?>
</div>