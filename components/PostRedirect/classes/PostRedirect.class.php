<?php

class PostRedirect
{
	protected $url;
	protected $params = array();

	/**
	 * Create a new object for generating a post request or redirect.
	 *
	 * @param SERIA_Url $url Url to redirect to.
	 * @param array $params Pass post-parameters as an associative array. false to create an empty redirect. (Optional) Defaults to $_POST.
	 * @return unknown_type
	 */
	public function __construct(SERIA_Url $url, $params=null)
	{
		$this->url = $url;
		if ($params === null)
			$params = $_POST;
		if ($params !== false) {
			foreach ($params as $nam => $val)
				$this->set($nam, $val);
		}
	}

	/**
	 * Set post-parameter.
	 *
	 * @param string $name Key
	 * @param string $value Value
	 * @returns unknown_type
	 */
	public function set($name, $value)
	{
		$this->params[$name] = $value;
	}
	/**
	 * Get post-parameter.
	 *
	 * @param string $name Key
	 * @returns string The value.
	 */
	public function get($name)
	{
		return $this->params[$name];
	}

	/**
	 * Returns an associative array of post-parameters.
	 *
	 * @returns array
	 */
	public function getAll()
	{
		return $this->params;
	}

	public function getInput($name, $type='hidden')
	{
		return '<input type=\''.$type.'\' name="'.htmlspecialchars($name).'" value="'.htmlspecialchars($this->params[$name]).'" />';
	}

	/**
	 * Output as a form with hidden fields.
	 *
	 * @param string $id HTML-id of form.
	 * @returns unknown_type
	 */
	public function outputForm($id=false)
	{
		?>
			<form action="<?php echo htmlspecialchars($this->url); ?>" method='post'<?php if ($id) echo ' id="'.htmlspecialchars($id).'"'; ?>>
				<?php
					$keys = array_keys($this->params);
					foreach ($keys as $name)
						echo $this->getInput($name);
				?>
				<button type='submit'><?php echo _t('Please wait a few seconds...'); ?></button>
			</form>
		<?php
	}
	/**
	 * Output a javascript redirect.
	 *
	 * @returns unknown_type
	 */
	public function outputJavascriptRedirect()
	{
		$id = 'RedirectingWithJavascript'.mt_rand();
		$this->outputForm($id);
		?>
			<script type='text/javascript'>
				<!--
					var redirectFormId = <?php echo SERIA_Lib::toJSON($id); ?>;
					var redirectForm = document.getElementById(redirectFormId);

					redirectForm.submit();
				-->
			</script>
		<?php
	}
}