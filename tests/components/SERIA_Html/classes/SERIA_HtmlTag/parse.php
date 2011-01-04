<?php

if (!isset($this)) {
	require_once(dirname(__FILE__).'/../../../../../main.php');
	$tpl = new SERIA_MetaTemplate();
	die($tpl->parse(__FILE__));
}


?><s:gui title="Test SERIA_HtmlTag parser.">
	<h1 class='legend'>{{'Test SERIA_HtmlTag parser.'}}</h1>
	<?php
		if (count($_POST)) {
			$html = $_POST['htmlcode'];
			while ($html) {
				$pos = strpos($html, '<');
				if ($pos === 0) {
					$tag = new SERIA_HtmlTag($html);
					$html = substr($html, $tag->getBytesConsumed());
					$tags[] = $tag;
				} else {
					if ($pos === false)
						$pos = strlen($html);
					$text = substr($html, 0, $pos);
					$html = substr($html, $pos);
					$tags[] = $text;
				}
			}
			
			ob_start();
			print_r($tags);
			$data = ob_get_clean();
			?>
				<p>{{$data|htmlspecialchars|nl2br}}</p>
			<?php
		}
	?>
	<form method='post'>
		<div>
			<textarea cols='80' rows='25' name='htmlcode'></textarea>
		</div>
		<div>
			<input type='submit' value="Parse" />
		</div>
	</form>
</s:gui>