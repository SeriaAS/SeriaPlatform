#!/usr/bin/php
<?php 
	require(dirname(__FILE__).'/../main.php');
	require(SERIA_ROOT.'/seria/includes/commandline.php');

	outputerr("This function will generate a PHP-file that displays a grid based on a MetaObject.\n\n");

	if(file_exists($argv[1]))
		runtime_error(2, 'The file already exists');
	if(!is_dir(dirname($argv[1])))
		runtime_error(3, 'The path does not exist');
	if(strpos(realpath(dirname($argv[1])), realpath(SERIA_ROOT))!==0)
		runtime_error(6, 'The file must exist within the SERIA_ROOT configured location. This is because we need to include the main.php file.');
	$relativePath = trim(substr(realpath(dirname($argv[1])), strlen(realpath(SERIA_ROOT))), "/")."/".basename($argv[1]);

	// Contains page structure for displaying to user:
	$structure = array();

/**
*	Page settings
*/
	do {
		echo "Title for your page: ";
		$pageTitle = input();
		$structure[] = array('type' => 'h1.legend', 'value' => $pageTitle);
	} while(!trim($pageTitle));

	echo "Optional description to display below the page title (html allowed): ";
	$description = input();
	if($description)
		$structure[] = array('type' => 'p.description', 'value' => $description);

/**
*	Menu locations
*/
	echo "Select the active menu location:\n";
	$gui = new SERIA_Gui('Page Builder');
	$location = false;
	do
	{
		$items = $gui->getMenuItems($location);
		$newItems = array();
		foreach($items as $key => $i)
			$newItems[basename($key)] = basename($key);
		$items = $newItems;
		do 
		{
			if($location===false)
			{
				echo "Menu items to choose from:\n";
			}
			else
			{
				echo "Your location is ".$location.", choose:\n";
			}

			foreach($items as $item)
			{
				echo "- ".$item."\n";
			}
			if($location===false)
			{
				echo "Type the location name of your page: ";
				$res = input();
			}
			else
			{
				echo "Select a sub-location or press enter: ";
				$res = input($location);
				if($res==$location) $res = '';
			}
			if($res==='')
			{
				if($location!==false)
					break 2;
			}
		} while(!isset($items[$res]));

		if($location === false)
			$location = $res;
		else
			$location .= "/".$res;
	} while(true);

	array_unshift($structure, array('type' => 'menuLocation', 'value' => $location));
	echo "Selected location: ".$location."\n";

/*****************************************************************************
* Generating script head
******************************************************************************/
	$source = "<"."?php
/**
* Automatically generated by scripts/scaffold.php. Please modify it to suit your needs.
*/
";

	// require(dirname__FILE__).'/../../main.php');
	if(strpos($relativePath, 'seria/')===0)
	{
		$count = sizeof(explode("/", $relativePath))-2;
		$includePath = "";
		for($i = 0; $i < $count; $i++)
			$includePath .= '/..';
		$includePath .= '/main.php';
	}
	else
	{
		$count = sizeof(explode("/", $relativePath))-1;
		$includePath = "";
		for($i = 0; $i < $count; $i++)
			$includePath .= '/..';
		$includePath .= '/seria/main.php';

	}
	$source .= 'require(dirname(__FILE__)."'.$includePath.'");
	SERIA_Base::pageRequires("admin");
';

	$source .= '$gui = new SERIA_Gui(_t("'.str_replace('"','\"',$pageTitle).'"));
';
	$source .= '$contents = "<h1 class=\"legend\">"._t("'.str_replace("\"","\\\"", $pageTitle).'")."</h1>";
';

	if($description)
	{
		$source .= '$contents .= "<p class=\"description\">"._t("'.str_replace("\"","\\\"",$description).'")."</p>";'."\n";
	}

	$source .= '
/**
* Build page contents
*/

';

/*****************************************************************************
* Building the rest of the page
******************************************************************************/

	while(true)
	{
		echo "What do you want your page to contain next?
1. A form.
2. A grid.
h1. A heading 1
h2. A heading 2
h3. A heading 3
p. A text paragraph.
x. SAVE AND EXIT
Select (1 or 2): ";

		$choice = select(array(1,2,'x','h1','h2','h3','p'),'x');

		switch($choice)
		{
			case 'h1' :
			case 'h2' :
			case 'h3' :
			case 'p' :
				echo "Specify text: ";
				$res = input();
				if($res)
				{
					$source .= '$contens .= "<'.$choice.'>"._t("'.str_replace('"','\"',$res).'")."</'.$choice.'>";'."\n";
					$structure[] = array('type' => $choice, 'value' => $res);
				}
				else
					echo "Nothing inserted\n";
				break;
			case 'x' : // finish
				break 2;
			case '1' : // form
				while(true)
				{
					$className = selectMetaClass();
					$spec = SERIA_Meta::_getSpec($className);
					$reflection = new ReflectionClass($className);

					$allMethods = $reflection->getMethods();
					$candidateMethods = array();
					$ok = false;
					foreach($allMethods as $method)
					{
						if($method->getDeclaringClass()->name != "SERIA_MetaObject" && $method->isPublic())
						{
							$parsed = parseComment($method->getDocComment());
							foreach($parsed as $p)
							{
								if(is_array($p) && $p[0]=='@return' && $p[1]=='SERIA_ActionForm' && isset($p[2]) && $p[2][0]=='[')
								{
									$parts = explode(",", trim(trim($p[2], '[]')));
									foreach($parts as $key => $part)
										$parts[$key] = trim($part);
									$candidateMethods[] = array('method' => $method, 'fields' => $parts);
								}
							}
						}
					}

					if(sizeof($candidateMethods)>0)
						break;

					minor_error("The class ".$className." does not have any properly implemented functions that 
return a SERIA_ActionForm. Remeber that a method that returns a SERIA_ActionForm
object must always have a phpDoc comment immediately before the function,in this 
form:

/**
* Edit action
* @return SERIA_ActionForm [name,username,password]
*/

Where [name,username,password] specifies which fields are in the SERIA_ActionForm.
");
				}

				echo "The class ".$className." has the following methods:\n"; 
				$choices = array();
				foreach($candidateMethods as $key => $info)
				{
					echo ($key+1).". ".($info['method']->isStatic() ? $className."::" : '$object->').$info['method']->name."(";
					$paramlist = array();
					$params = $info['method']->getParameters();
					foreach($params as $param)
						$paramlist[] = '$'.$param->name.($param->isOptional() && $param->getDefaultValue()?'='.$param->getDefaultValue():'');
					echo implode(", ", $paramlist);
					echo ");\n";
					$choices[$key+1] = array('method' => $info['method'], 'fields' => $info['fields']);
				}

				$choice = select(array_keys($choices), NULL, "Select which method you wish to use to fetch the SERIA_ActionForm instance: ");

				$info = $choices[$choice];

				$requiredGets = array();

				if($info['method']->isStatic())
				{ // static method, so we work trough the className
					$sourceNext = '$action = '.$className.'::'.$info['method']->name.'(';
					if(sizeof($params = $info['method']->getParameters())>0)
					{
						echo "The method ".$className."::".$info['method']->name." is static, but has parameters. This generator only supports fetching parameters
from the GET-parameters, or constant values. 
Please provide the value for the following parameters:\n";
						$values = array();
						foreach($params as $param)
						{
							if($param->isOptional())
								$choice = select(array('1','2','x'), 1, "Value for the \$".$param->name." parameter:
1. Value from query string
2. Constant value
x. Do not provide more parameters (optional params will have their default value)
Select (1, 2 or x): ");
							else
								$choice = select(array('1','2'), 1, "Value for the \$".$param->name." parameter:
1. Value from query string
2. Constant value
Select (1 or 2): ");
							if($choice==1)
							{
								$variableName = input(NULL, "Query parameter name: ");
								$values[] = '$_GET["'.$variableName.'"]';
								$requiredGets[] = $variableName;
							}
							else if($choice==2)
							{
								$value = input(NULL, "Value (enclose strings in double quotes): ");
								$values[] = $value;
							}
							else
							{
								break;
							}
						}
						$sourceNext .= implode(", ",$values).");\n";
					}
					else
					{
						$sourceNext .= ");\n";
					}
				}
				else
				{
					echo "The method you selected require an object to be instantiated. This object will be instantiated by using \$object = SERIA_Meta::load('".$className."', \$_GET['id']).\n";
					echo "You can modify the source file afterwards if you want.\n";
					$sourceNext = '$object = SERIA_Meta::load("'.$className.'", $_GET["id"]);'."\n";
					$requiredGets[] = 'id';
					$sourceNext .= '$action = $object->'.$info['method']->name.'(';
					if(sizeof($params = $info['method']->getParameters())>0)
					{
						echo "The method \$object->".$info['method']->name." has parameters. This generator only supports fetching parameters
from the GET-parameters, or constant values. 
Please provide the value for the following parameters:\n";
						$values = array();
						foreach($params as $param)
						{
							if($param->isOptional())
								$choice = select(array('1','2','x'), 1, "Value for the \$".$param->name." parameter:
1. Value from query string
2. Constant value
x. Do not provide more parameters (optional params will have their default value)
Select (1, 2 or x): ");
							else
								$choice = select(array('1','2'), 1, "Value for the \$".$param->name." parameter:
1. Value from query string
2. Constant value
Select (1 or 2): ");
							if($choice==1)
							{
								$variableName = input(NULL, "Query parameter name: ");
								$values[] = '$_GET["'.$variableName.'"]';
								$requiredGets[] = $variableName;
							}
							else if($choice==2)
							{
								$value = input(NULL, "Value (enclose strings in double quotes): ");
								$values[] = $value;
							}
							else
							{
								break;
							}
						}
						$sourceNext .= implode(", ",$values).");\n";
					}
					else
					{
						$sourceNext .= ");\n";
					}
				}
				foreach($requiredGets as $get)
					$source .= 'if(!isset($_GET["'.$get.'"])) throw new SERIA_Exception("The parameter \$_GET[\"'.$get.'\"] is not set.");'."\n";

				$source .= $sourceNext;

				echo "How many columns do you want to use in your form (1 or 2): ";
				$columns = select(array(1,2), 2);

				echo "The method you have chosen has the following fields:\n";
				foreach($info['fields'] as $fieldName)
				{
					echo "- ".$fieldName."\n";
				}
				$fieldHash = array_flip($info['fields']);

				while(true)
				{
					if($columns == 1)
					{
						while(true)
						{
							echo "Specify the fields you want in the form, comma separated: ";
							if($fields = trim(input()))
							{
								$fields = explode(",", $fields);
								$errors = false;
								$leftFields = array();
								foreach($fields as $fieldName)
								{
									$fieldName = trim($fieldName);
									if(!isset($fieldHash[$fieldName]))
									{
										echo "- ".$fieldName." not found\n";
										$errors = true;
									}
									else
									{
										$errors2 = false;
										foreach($leftFields as $leftField)
										{
											if($leftField==$fieldName)
											{
												echo "- ".$fieldName." used before in this column\n";
												$errors2 = true;
												$errors = true;
											}
										}
										if(!$errors2)
											$leftFields[] = $fieldName;
									}
								}
							}
							if(!$errors) break;
						}

					}
					else if($columns == 2)
					{
						while(true)
						{
							echo "Specify the fields you want in the LEFT column, comma separated: ";
							if($fields = trim(input()))
							{
								$fields = explode(",", $fields);
								$errors = false;
								$leftFields = array();
								foreach($fields as $fieldName)
								{
									$fieldName = trim($fieldName);
									if(!isset($fieldHash[$fieldName]))
									{
										echo "- ".$fieldName." not found\n";
										$errors = true;
									}
									else
									{
										$errors2 = false;
										foreach($leftFields as $leftField)
										{
											if($leftField==$fieldName)
											{
												echo "- ".$fieldName." used before in this column\n";
												$errors2 = true;
												$errors = true;
											}
										}
										if(!$errors2)
											$leftFields[] = $fieldName;
									}
								}
							}
							if(!$errors) break;
						}

						while(true)
						{
							echo "Specify the fields you want in the RIGHT column, comma separated: ";
							if($fields = trim(input()))
							{
								$fields = explode(",", $fields);
								$errors = false;
								$rightFields = array();
								foreach($fields as $fieldName)
								{
									$fieldName = trim($fieldName);
									if(!isset($spec['fields'][$fieldName]))
									{
										echo "- ".$fieldName." not found\n";
										$errors = true;
									}
									else
									{
										$errors2 = false;
										foreach($leftFields as $leftField)
										{
											if($leftField==$fieldName)
											{
												echo "- ".$fieldName." used before in the left column\n";
												$errors2 = true;
												$errors = true;
											}
										}
										foreach($rightFields as $rightField)
										{
											if($rightField==$fieldName)
											{
												echo "- ".$fieldName." used before in this column column\n";
												$errors2 = true;
												$errors = true;
											}
										}
										$rightFields[] = $fieldName;
									}
								}
							}
							if(!$errors) break;
						}
					}

					$sourceNext = '$contents .= $form->begin()."<table><tbody>'."\n";

					if($columns == 1)
					{
						foreach($leftFields as $fieldName)
							$sourceNext .= '	<tr><th>".$form->label("'.$fieldName.'")."</th><td>".$form->field("'.$fieldName.'")."</td></tr>'."\n";


					}
					else if($columns == 2)
					{
						$sourceLeft = '	<table>';
						foreach($leftFields as $fieldName)
							$sourceLeft .= '			<tr><th>".$form->label("'.$fieldName.'")."</th><td>".$form->field("'.$fieldName.'")."</td></tr>'."\n";
						$sourceLeft .= '		</table>'."\n";

						$sourceRight = '		<table>';
						foreach($rightFields as $fieldName)
							$sourceRight .= '			<tr><th>".$form->label("'.$fieldName.'")."</th><td>".$form->field("'.$fieldName.'")."</td></tr>'."\n";
						$sourceRight .= '		</table>'."\n";

						$sourceNext .= "	<tr><td>\n".$sourceLeft."	</td><td>\n".$sourceRight."	</td></tr>\n";
					}

					$sourceNext .= '</tbody><tfoot><tr><td colspan=\"2\">".$form->submit()."</td></tr></tfoot></table>".$form->end();'."\n";
					$source .= $sourceNext;
					break;
				}

echo $source;

				break;
			case '2' : // grid
				$className = selectMetaClass();
				$spec = SERIA_Meta::_getSpec($className);
				echo "The class ".$className." has the following fields:\n";

				foreach($spec['fields'] as $fieldName => $fieldSpec)
				{
					echo "- ".$fieldName."\n";
				}

				while(true)
				{
					echo "Tip: If you want a fixed width for your field, specify that like this 'fieldName=200,anotherfield,thirdfield'\nSpecify fields here, comma separated: ";
					$newFields = array();
					$fields = explode(",", trim(input()));
					$error = false;
					foreach($fields as $key => $field)
					{
						if(strpos($field, "=")!==false)
						{
							$parts = explode("=", $field);
							$field = $parts[2];
							$width = $parts[1];
						}
						else
						{
							$width = 0;
						}
						$fields[$key] = $field = trim($field);
						if(!isset($spec['fields'][$field]))
						{
							minor_error('The field '.$field.' does not exist.');
							$error = true;
							break;
						}
						$newFields[$field] = $width;
					}
					if(!$error)
						break;
				}
				$fields = $newFields;

				echo "Specify a page size for the pager: ";
				$pageSize = intval(input('20'));

				echo "URL templates are specified with field names enclosed in '%'-symbols. For example: 'relative_path/edit.php?id=%id%'.\nPlease specify an url template, or blank if you do not want it: ";
				$urlTemplate = input('none');

				echo "Row templates are used to generate the <tr><td>...</td></tr>-html for each row. For example: '<tr><td>%name%</td></tr>'\nPleace specify a row template, or blank if you want to use the standard template: ";
				$rowTemplate = input('none');

				$source .= '$query = SERIA_Meta::all("'.$className.'");
// Customize: $query->where("your filter");
// Customize: $query->order("default ordering");
$grid = new SERIA_MetaGrid($query);
';

				if($urlTemplate!=='none')
					$source .= '$grid->rowClick("'.str_replace("\"","\\\"",$urlTemplate).'");
';

				$outputParams = array();
				foreach($fields as $fieldName => $width)
				{
					if($width)
						$outputParams[] = "\"".$field."\" => ".$width;
					else
						$outputParams[] = "\"".$field."\"";
				}
	
				$source .= '$contents .= $grid->output(array('.implode(",", $outputParams).')';

				if($rowTemplate!='none')
				{
					$source .= ', "'.str_replace("\"","\\\"",$rowTemplate).'", '.intval($pageSize).');'."\n";
				}
				else
				{
					$source .= ', NULL, '.intval($pageSize)."\n";
				}
				break;
		}
	}

	$source .= '
/**
* Output page to the end user
*/

';

	$source .= 'echo $gui->contents($contents)->output();
';

	file_put_contents($argv[1], $source);




function selectMetaClass()
{
	while(true)
	{
		echo "Enter a SERIA_MetaObject classname: ";
		$className = input();
		if(class_exists($className))
		{
			if(is_subclass_of($className, 'SERIA_MetaObject'))
			{
				return $className;
			}
			else
				outputerr('Class '.$className.' does not extend SERIA_MetaObject.');
		}
		else
			outputerr('Class '.$className.' not found.');
	}
}

function parseComment($comment)
{
/**
                *       @return SERIA_ActionForm [name,proxy,secret]
                */

	$result = array();
	$comment = str_replace("\r","",$comment);
	$lines = explode("\n", $comment);
	foreach($lines as $line)
	{
		$line = trim($line, "\t\n */");
		if($line[0]=="@")
		{
			if($text!==NULL)
			{
				$result[] = $text;
				$text = NULL;
			}
			$fixed = str_replace(array("\t","\n","\r"), array(" "," "," "), $line);
			for($i = 0; $i < 30; $i++)
			{
				$fixed = str_replace("  "," ", $fixed);
			}
			$result[] = explode(" ", $fixed);
		}
		else
		{
			if($text===NULL) $text = $line;
			else $text .= $line;
		}
	}
	if($text) $result[] = $text;
	return $result;
}

function findFirst($string, array $chars)
{
	$firstPos = strlen($string);
	$firstChar = NULL;
	foreach($chars as $char)
	{
		if(($p = strpos($string, $char))!==false)
		{
			if($p < $firstPos)
			{
				$firstPos = $p;
				$firstChar = substr($string, $p, 1);
			}
		}
	}

	return array($firstPos, $firstChar);
}
