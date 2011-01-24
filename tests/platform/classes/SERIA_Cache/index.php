<?php

require_once(dirname(__FILE__).'/../../../../main.php');

SERIA_Base::pageRequires('admin');

if (isset($this) && $this instanceof SERIA_MetaTemplate) {
	?>
		<s:gui title='SERIA_Cache test'>
			<h1 class='legend'>SERIA_Cache test</h1>
			<?php
				if (isset($_GET['namespace'])) {
					if (isset($_POST['read'])) {
						SERIA_ScriptLoader::loadScript('jQuery');
						?>
							<script type='text/javascript'>
								<!--
									<?php
										$cache = new SERIA_Cache($_GET['namespace']);
										$value = $cache->get($_POST['read']);
										if ($value !== null)
											$msg = 'Value of '.$_POST['read'].' is '.$value;
										else
											$msg = $_POST['read'].' is not set.';
									?>
									$(document).ready(function () {
										alert(<?php echo SERIA_Lib::toJSON($msg); ?>);
										top.location.href = <?php echo SERIA_Lib::toJSON(SERIA_Url::current()->__toString()); ?>;
									});
								-->
							</script>
							<noscript><div><?php echo $msg; ?></div></noscript>
						<?php
					} else if (isset($_POST['key']) && isset($_POST['value']) && isset($_POST['ttl'])) {
						$cache = new SERIA_Cache($_GET['namespace']);
						$cache->set($_POST['key'], $_POST['value'], $_POST['ttl']);
						SERIA_Base::redirectTo(SERIA_Url::current()->__toString());
					} else if (isset($_POST['deleteAll']) && $_POST['deleteAll'] == 'do') {
						$cache = new SERIA_Cache($_GET['namespace']);
						$cache->deleteAll();
						?>
							<p>All cache has been deleted!</p>
							<input type='button' onclick="top.location.href = <?php echo htmlspecialchars(SERIA_Lib::toJSON(SERIA_Url::current()->__toString())); ?>;" value='Continue' />
						<?php
					} else {
						?>
							<input type='button' onclick="top.location.href = <?php echo htmlspecialchars(SERIA_Lib::toJSON(SERIA_Url::current()->removeParam('namespace')->__toString())); ?>;" value="Close namespace: <?php echo htmlspecialchars($_GET['namespace']); ?>" />
							<fieldset><legend>Set cached value</legend>
								<form method='post'>
									<table>
										<tbody>
											<tr>
												<th><label for='SERIA_Cache.key'>Key (name): </label></th>
												<td><input id='SERIA_Cache.key' name='key' value='' /></td>
											</tr>
											<tr>
												<th><label for='SERIA_Cache.value'>Value: </label></th>
												<td><input id='SERIA_Cache.value' name='value' value='' /></td>
											</tr>
											<tr>
												<th><label for='SERIA_Cache.ttl'>Time to live (expiry): </label></th>
												<td><input id='SERIA_Cache.ttl' name='ttl' value='300' title='Expires in this number of seconds.' /></td>
											</tr>
										</tbody>
									</table>
									<div>
										<input type='submit' value='Set value!' />
									</div>
								</form>
							</fieldset>
							<fieldset>
								<form method='post'>
									<div>
										<label>Read value of field: <input type='text' name='read' value='' /></label>
										<div>
											<input type='submit' value='Read value!' />
										</div>
									</div>
								</form>
							</fieldset>
							<fieldset><legend>Delete all namespace cache</legend>
								<form method='post'>
									<div>
										<input type='hidden' name='deleteAll' value='do' />
										<input type='submit' value='Delete all cache!' />
									</div>
								</form>
							</fieldset>
						<?php
					}
				} else {
					?>
						<form method='get'>
							<div>
								<label>Open namespace: <input type='text' name='namespace' value='' /></label>
								<div>
									<input type='submit' value='Open' />
								</div>
							</div>
						</form>
					<?php
				}
			?>
		</s:gui>
	<?php
} else {
	$tpl = new SERIA_MetaTemplate();
	echo $tpl->parse(__FILE__);
}