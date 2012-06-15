<?php

include('../../../configuration.php');
include('../../../dbconnect.php');
include('../../../includes/functions.php');
include('../../../includes/adminfunctions.php');
include('../../../includes/countries.php');
include_once('sirportly.php');

if (!isset($_SERVER['PHP_AUTH_USER']))
{
	header('WWW-Authenticate: Basic realm="Sirportly"');
	header('HTTP/1.0 401 Unauthorized');
	echo 'Unauthorized';
	exit;
}

// Check to see for valid admin credentials
$admin  = select_query('tbladmins', 'id', array('username' => $_SERVER['PHP_AUTH_USER'], 'password' => md5($_SERVER['PHP_AUTH_PW']) ));

if(mysql_num_rows($admin))
{
	$admin = mysql_fetch_array($admin);
} 
else
{
	header('HTTP/1.1 404 Not Found');
	echo 'Not Found';
	die;
}

// get client details from db
$client = select_query('tblclients', '', array('email' => $_REQUEST['data']));

// check for valid client email
if(mysql_num_rows($client))
{
	$client = mysql_fetch_array($client);
} 
else
{
	header('HTTP/1.1 404 Not Found');
	echo 'Not Found';
	die;
}

$current_fields = array();

$result = mysql_query('SELECT * FROM `sirportly`');

while($row = mysql_fetch_array($result, MYSQL_NUM))
{
	$current_fields[$row[1]][$row[2]] = $row[2];
}

// prepare return
global $CONFIG, $customadminpath;
$api_results = array();
$api_results['contact_methods']['email'] = array($client['email']);

//Loop through the tblclients, tbldomains, tblhosting and tblinvoices tables.

foreach($current_fields as $this_table => $this_table_fields)
{
	$id_field = ($this_table == 'tblclients' ? 'id' : 'userid');

	$query = select_query($this_table, implode(',', $this_table_fields), array($id_field => $client['id']));
	
	////////////////////////////////////
	//WHMCS link
	////////////////////////////////////
	if ($this_table == 'tblclients')
	{
		$api_results[sirportly_lang('tblclients')]['WHMCS URL'] = 'link:' . $CONFIG['SystemURL'] . '/' . $customadminpath . '/clientssummary.php?userid=' . $client['id'] . '|Profile';
	}
	////////////////////////////////////
	
	if(mysql_num_rows($query) == 1)
	{
		$result = mysql_fetch_array($query, MYSQL_ASSOC);
		foreach ($this_table_fields as $row_key => $row_value)
		{

			if($this_table == 'tbldomains' && $row_key == 'id')
			{	//Domains
				$api_results[sirportly_lang($this_table)][$row_key] = 'link:' . $CONFIG['SystemURL'] . '/' . $customadminpath . '/clientsdomains.php?id=' . $result[$row_key] . '|' . $result[$row_key];
			}
			elseif($this_table == 'tblhosting' && $row_key == 'id')
			{	//Service
				$api_results[sirportly_lang($this_table)][$row_key] = 'link:' . $CONFIG['SystemURL'] . '/' . $customadminpath . '/clientshosting.php?id=' . $result[$row_key] . '|' . $result[$row_key];
			}
			elseif($this_table == 'tblinvoices' && $row_key == 'id')
			{	//Invoices
				$api_results[sirportly_lang($this_table)][$row_key] = 'link:' . $CONFIG['SystemURL'] . '/' . $customadminpath . '/invoices.php?action=edit&id=' . $result[$row_key] . '|' . $result[$row_key];;
			}
			else
			{
				$api_results[sirportly_lang($this_table)][$row_key] = $result[$row_key];
			}
		}
	} 
	else
	{
		$result_block = array();
		while ($result = mysql_fetch_array($query, MYSQL_ASSOC))
		{
			$row = array();
			foreach ($this_table_fields as $row_key => $row_value)
			{
				if($this_table == 'tbldomains' && $row_key == 'id')
				{	//Domains
					$row[$row_key] = 'link:' . $CONFIG['SystemURL'] . '/' . $customadminpath . '/clientsdomains.php?id=' . $result[$row_key] . '|' . $result[$row_key];
				}
				elseif($this_table == 'tblhosting' && $row_key == 'id')
				{	//Service
					$row[$row_key] = 'link:' . $CONFIG['SystemURL'] . '/' . $customadminpath . '/clientshosting.php?id=' . $result[$row_key] . '|' . $result[$row_key];
				}
				elseif($this_table == 'tblinvoices' && $row_key == 'id')
				{	//Invoices
					$row[$row_key] = 'link:' . $CONFIG['SystemURL'] . '/' . $customadminpath . '/invoices.php?action=edit&id=' . $result[$row_key] . '|' . $result[$row_key];
				}
				else
				{
					$row[$row_key] = $result[$row_key];
				}
			}
			array_push($result_block, $row);
		}
		
		$api_results[sirportly_lang($this_table)] = $result_block;
	}
}

header('HTTP/1.1 200 OK');
echo json_encode($api_results);

?>