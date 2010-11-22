<div>
	<form method='post'>
		<div>
			<table class='grid'>
				<thead>
					<tr>
						<th><?php echo _t('Name'); ?></th>
						<th><?php echo _t('Value'); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><label for='passwddisp'><?php echo _t('Admin password:'); ?></label></td>
						<td><input id='passwddisp' type='password' value='{{"*"|repeat(config.auth.adminpassword|strlen)}}' /></td>
					</tr><tr>
						<td><label for='passrep'><?php echo _t('Please repeat password:'); ?></label>
						<td><input id='passrep' name='password_repeat' type='password' value='' disabled='disabled' /></td>
					</tr>
					<?php
						if (isset($this->errors['auth.adminpassword'])) {
							?>
								<tr>
									<td></td>
									<td><p class='error'><?php echo $this->errors['auth.adminpassword']?></p></td>
								</tr>
							<?php
						}
					?>
					<tr>
						<td><label for='tech_name'><?php echo _t('Technical contact name:'); ?></label></td>
						<td><input type='text' id='tech_name' name='technicalcontact_name' value="{{config.technicalcontact_name|htmlspecialchars}}" /></td>
					</tr>
					<?php
						if (isset($this->errors['technicalcontact_name'])) {
							?>
								<tr>
									<td></td>
									<td><p class='error'>{{errors.technicalcontact_name}}</p></td>
								</tr>
							<?php
						}
					?>
					<tr>
						<td><label for='tech_email'><?php echo _t('Technical contact email:'); ?></label></td>
						<td><input type='text' id='tech_email' name='technicalcontact_email' value="{{config.technicalcontact_email|htmlspecialchars}}" /></td>
					</tr>
					<?php
						if (isset($this->errors['technicalcontact_email'])) {
							?>
								<tr>
									<td></td>
									<td><p class='error'>{{errors.technicalcontact_email}}</p></td>
								</tr>
							<?php
						}
					?>
					<tr>
						<td><label for='uniq_source'><?php echo _t('Unique user identifier:')?></label></td>
						<td>
							<select id='uniq_source' name='uniqueSource'>
								<option value='' <?php
									if (!$this->uniqueSource)
										echo ' selected=\'selected\''; 
								?>><?php echo _t('Not available'); ?></option>
								<?php
									foreach ($this->uniqueSources as $source) {
										?>
											<option value="<?php echo htmlspecialchars($source); ?>" <?php
												if ($source == $this->uniqueSource)
													echo 'selected=\'selected\'';
											?>><?php echo htmlspecialchars($source); ?></option>
										<?php
									}
								?>
							</select>
						</td>
					</tr>
					<?php
						if (isset($this->errors['uniqueSource'])) {
							?>
								<tr>
									<td></td>
									<td><p class='error'>{{errors.uniqueSource}}</p></td>
								</tr>
							<?php
						}
					?>
				</tbody>
			</table> 
		</div>
		<div>
			<button type='submit' id='submitbutton' disabled='disabled'>{{submitCaption}}</button>
			<button type='button' onclick="top.location.href = {{cancel|toJson|htmlspecialchars}}">{{'Cancel'|_t}}</button>
		</div>
		<script type='text/javascript'>
			<!--
				(function () {
					var submitb = document.getElementById('submitbutton');
					var passwdd = document.getElementById('passwddisp');
					var passrep = document.getElementById('passrep');
					var techname = document.getElementById('tech_name');
					var techemail = document.getElementById('tech_email');
					var uniqsource = document.getElementById('uniq_source');

					var opensave = function () {
						opensave = function () {
						}
						submitb.disabled = false;
					}
					var action = function () {
						action = function () {
						}
						passwdd.value = '';
						passwdd.setAttribute('name', 'auth.adminpassword');
						passrep.disabled = false;
						opensave();
					}
					passwdd.onfocus = passwdd.onclick = passwdd.onkeydown = passwdd.onchange = function () {
						action();
					}
					techname.onchange = techemail.onchange = uniqsource.onchange = function () {
						opensave();
					}
				})();
			-->
		</script>
	</form>
</div>
<div>
	<h2><?php echo htmlspecialchars(_t('Simplesaml system')); ?></h2>
	<p>
		<a href="{{simplesaml_base_url|htmlspecialchars}}" target='_blank' title="<?php echo htmlspecialchars(_t('Open Simplesaml Admin')); ?>"><?php echo _t('Open Simplesaml Admin'); ?></a>
	</p>
</div>