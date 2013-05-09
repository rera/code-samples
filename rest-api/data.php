<?php
	include_once "includes/config.php";
	include_once "includes/connections.php";
	include_once "includes/helper.php";
	
	header('Cache-Control: no-cache, must-revalidate');
	header('Content-type: application/json');
	
	// singleton conn
	$pdo = ConnectionFactory::getFactory()->getConnection();
	
	// get action param or use default
	if(!empty($_POST['action']))
		$action = $_POST['action'];
	else if(!empty($_GET['action']))
		$action = $_GET['action'];
	else
		$action = 'get';
		
	// get object param or use default
	if(!empty($_POST['object']))
		$object = $_POST['object'];
	else if(!empty($_GET['object']))
		$object = $_GET['object'];
	else
		die('ERROR: Invalid data service call.');
		
	if(empty($error)) {
		//no errors, switch object
		switch($object) {
			case 'offer':
				switch($action) {
					case 'email':
						try {
							$message = "
							<p>$_POST[first] $_POST[last] has sent you a message in regards to a service they can volunteer. 
							Their contact info is:<br />
							Phone: $_POST[phone]<br />
							E-mail: $_POST[email]</p>
							
							Their Message:
							$_POST[message]
							";
							$subject = "Someone has offered to volunteer services.";
							
							$yourEmail = "webmaster@wpb.org";
							$headers  = "MIME-Version: 1.0\r\n";
							$headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
							
							mail($yourEmail, $subject, $message, $headers);
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
				}
				break;
			case 'opportunity':
				switch($action) {
					case 'get':
						try {
							//get single
							if (isset($_POST['id'])) {
								$sql = 'SELECT * FROM opportunity WHERE opportunity_id = :oid';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':oid' => $_POST['id']
								));
							}
							//get public
							else if (isset($_POST['public'])) {
								$sql = 'SELECT o.*, COUNT(ol.location_id) AS cntloc, MAX(l.location_title) as location, d.department_title
										FROM opportunity AS o
										LEFT JOIN opportunity_location AS ol ON ( ol.opportunity_id = o.opportunity_id )
										LEFT JOIN location AS l ON ( l.location_id = ol.location_id )
										LEFT JOIN department AS d ON ( d.department_id = o.department_id )
										WHERE o.opportunity_status = 0';
								if (isset($_POST['department']))
									$sql .= ' AND ( d.department_id = :did )';										
								$sql .=' GROUP BY o.opportunity_id
										ORDER BY o.opportunity_title';
								if(isset($_POST['limit']))
									$sql .= ' LIMIT '.$_POST['limit'];
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':did' => $_POST['department']
								));
							}
							//get public with text dept name using GET
							else if (isset($_GET['public'])) {
								$sql = 'SELECT o.*, COUNT(ol.location_id) AS cntloc, MAX(l.location_title) as location, d.department_title
										FROM opportunity AS o
										LEFT JOIN opportunity_location AS ol ON ( ol.opportunity_id = o.opportunity_id )
										LEFT JOIN location AS l ON ( l.location_id = ol.location_id )
										LEFT JOIN department AS d ON ( d.department_id = o.department_id )';
								if (isset($_GET['department']))
									$sql .= 'WHERE ( d.department_title = :department )';										
								$sql .='GROUP BY o.opportunity_id';
								if(!isset($_GET['random']))
									$sql .= ' ORDER BY o.opportunity_title';
								if(isset($_GET['limit']))
									$sql .= ' LIMIT '.$_GET['limit'];
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':department' => urldecode($_GET['department'])
								));
							}
							//get all for department
							else if (isset($_POST['department'])) {
								$sql = 'SELECT * FROM opportunity WHERE department_id = :department AND (opportunity_status = 0';
								if(isset($_POST['archive']))
									$sql .= ' OR opportunity_status = 1';
								$sql .= ') ORDER BY opportunity_title';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':department' => $_POST['department']
								));
							}
							//get all
							else {
								$sql = 'SELECT * FROM opportunity WHERE (opportunity_status = 0';
								if(isset($_POST['archive']))
									$sql .= ' OR opportunity_status = 1';
								$sql .= ');';
								$stm = $pdo->prepare($sql);
								$stm->execute(array());
							}
							
							$rows = array();
							foreach($stm->fetchAll() as $row) {
								$rows[] = $row;
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
					case 'add':
						try {
							if(isset($_POST['user_id'])) {
								$sql = "INSERT INTO opportunity (opportunity_title, opportunity_desc, opportunity_req, opportunity_hours, department_id, contact_id, user_id, opportunity_status) VALUES ( '', '', '', '', 21 , 2 , :uid , 1 );";
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':uid' => $_POST['user_id']
								));
								
								$sql = 'SELECT * FROM opportunity WHERE user_id = :uid ORDER BY opportunity_id DESC LIMIT 1;';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':uid' => $_POST['user_id']
								));
							}
							
							$rows = array();
							foreach($stm->fetchAll() as $row) {
								$rows[] = $row;
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
					case 'apply':
						try {
							if(isset($_POST['opportunity_id'])) {
								$sql = 'SELECT candidate_id FROM candidate WHERE candidate_email = :email LIMIT 1';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':email' => $_POST['email']
								));
								$cid = $stm->fetchColumn();
								
								if($cid < 1) {
									$sql = 'INSERT INTO candidate (candidate_first, candidate_last, candidate_email, candidate_phone, candidate_expertise, candidate_av_sun, candidate_av_mon, candidate_av_tue, candidate_av_wed, candidate_av_thu, candidate_av_fri, candidate_av_sat) VALUES (:first, :last, :email, :phone, :exp, :sun, :mon, :tue, :wed, :thu, :fri, :sat)';
									$stm = $pdo->prepare($sql);
									$stm->execute(array(
										':first' => $_POST['first'],
										':last' => $_POST['last'],
										':email' => $_POST['email'],
										':phone' => $_POST['phone'],
										':exp' => $_POST['exp'],
										':sun' => $_POST['sun'],
										':mon' => $_POST['mon'],
										':tue' => $_POST['tue'],
										':wed' => $_POST['wed'],
										':thu' => $_POST['thu'],
										':fri' => $_POST['fri'],
										':sat' => $_POST['sat']
									));
									
									$sql = 'SELECT candidate_id FROM candidate WHERE candidate_email = :email LIMIT 1';
									$stm = $pdo->prepare($sql);
									$stm->execute(array(
										':email' => $_POST['email']
									));
									$cid = $stm->fetchColumn();
								}
								
								$sql = "INSERT INTO candidate_opportunity (opportunity_id ,candidate_id ,status_id ,cand_op_datetime) VALUES (:oid, :cid, 1, NOW())";
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':oid' => $_POST['opportunity_id'],
									':cid' => $cid
								));
								
								$sql = 'SELECT contact_email FROM `contact` WHERE contact_id = (SELECT contact_id FROM opportunity WHERE opportunity_id = :oid LIMIT 1)';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':oid' => $_POST['opportunity_id']
								));
								$yourEmail = $stm->fetchColumn();
								
								$message = "
								<p>$_POST[first] $_POST[last] has inquired about the following volunteer opportunity:<br/>
								$_POST[opportunity_title]<br/><br/>
								Their contact info is:<br />
								Phone: $_POST[phone]<br />
								E-mail: $_POST[email]<br />
								Expertise: $_POST[exp]</p>";
								$subject = "Volunteer Inquiry: $_POST[opportunity_title]";
								
								$headers  = "MIME-Version: 1.0\r\n";
								$headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
								$headers .= "Reply-To: ".$_POST['email']."\r\n";
								
								mail($yourEmail.', webmaster@wpb.org', $subject, $message, $headers);
							}
							
							$rows = array();
							foreach($stm->fetchAll() as $row) {
								$rows[] = $row;
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
					case 'update':
						try {
							if(isset($_POST['id'])) {
								$sql = 'UPDATE opportunity SET opportunity_title = :title, opportunity_hours = :hour, department_id = :did, contact_id = :cid, opportunity_desc = :desc, opportunity_req = :req, opportunity_status = :stat WHERE opportunity_id = :oid;';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':oid' => $_POST['id'],
									':title' => $_POST['title'],
									':hour' => $_POST['hour'],
									':did' => $_POST['department'],
									':cid' => $_POST['contact'],
									':desc' => $_POST['desc'],
									':req' => $_POST['req'],
									':stat' => $_POST['status']
								));
								
								// update locations
								$sql = 'DELETE FROM opportunity_location WHERE opportunity_id = :oid';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':oid' => $_POST['id']
								));
								
								$locs = (array) $_POST['locations'];
								foreach($locs as $loc) {
									$sql = 'INSERT INTO opportunity_location (opportunity_id, location_id) VALUES (:oid, :lid);';
									$stm = $pdo->prepare($sql);
									$stm->execute(array(
										':oid' => $_POST['id'],
										':lid' => $loc
									));
								}
								
								// update hours
								$sql = 'DELETE FROM hours WHERE opportunity_id = :oid';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':oid' => $_POST['id']
								));
								
								$hours = (array) $_POST['hours'];
								foreach($hours as $hour) {
									$sql = 'INSERT INTO hours (opportunity_id, hours_time) VALUES (:oid, :ht);';
									$stm = $pdo->prepare($sql);
									$stm->execute(array(
										':oid' => $_POST['id'],
										':ht' => $hour
									));
								}
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
					case 'delete':
						try {
							if(isset($_POST['id'])) {
								$sql = 'UPDATE opportunity SET opportunity_status = 1 WHERE opportunity_id = :oid;';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':oid' => $_POST['id']
								));
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
					case 'restore':
						try {
							if(isset($_POST['id'])) {
								$sql = 'UPDATE opportunity SET opportunity_status = 0 WHERE opportunity_id = :oid;';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':oid' => $_POST['id']
								));
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
				}
				break;					
			case 'contact':
				switch($action) {
					case 'get':
						try {
							//get single
							if (isset($_POST['id'])) {
								$sql = 'SELECT * FROM `contact` WHERE contact_id = :id';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':id' => $_POST['id']
								));
							}
							//get all
							else {
								$sql = 'SELECT * FROM contact WHERE (contact_status = 0';
								if(isset($_POST['archive']))
									$sql .= ' OR contact_status = 1';
								$sql .= ') ORDER BY contact_lname;';
								$stm = $pdo->prepare($sql);
								$stm->execute(array());
							}
							
							$rows = array();
							foreach($stm->fetchAll() as $row) {
								$rows[] = $row;
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
					case 'add':
						try {
							$sql = "INSERT INTO contact (contact_fname, contact_lname, contact_phone, contact_email, contact_position, contact_email_send, contact_status) VALUES ('', '', '', '', '', 1, 1);";
							$stm = $pdo->prepare($sql);
							$stm->execute();
								
							$sql = 'SELECT * FROM contact ORDER BY contact_id DESC LIMIT 1;';
							$stm = $pdo->prepare($sql);
							$stm->execute();
							
							$rows = array();
							foreach($stm->fetchAll() as $row) {
								$rows[] = $row;
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
					case 'update':
						try {
							if(isset($_POST['id'])) {
								$sql = 'UPDATE contact SET contact_fname = :fname, contact_lname = :lname, contact_phone = :phone, contact_email = :email, contact_position = :position, contact_email_send = :emailsend, contact_status = :status WHERE contact_id = :cid;';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':fname' => $_POST['fname'],
									':lname' => $_POST['lname'],
									':phone' => $_POST['phone'],
									':email' => $_POST['email'],
									':position' => $_POST['position'],
									':emailsend' => $_POST['emailsend'],
									':status' => $_POST['status'],
									':cid' => $_POST['id']
								));
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
					case 'delete':
						try {
							if(isset($_POST['id'])) {
								$sql = 'UPDATE contact SET contact_status = 1 WHERE contact_id = :cid;';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':cid' => $_POST['id']
								));
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
					case 'restore':
						try {
							if(isset($_POST['id'])) {
								$sql = 'UPDATE contact SET contact_status = 0 WHERE contact_id = :cid;';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':cid' => $_POST['id']
								));
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
				}
				break;			
			case 'location':
				switch($action) {
					case 'get':
						try {
							//get single
							if (isset($_POST['id'])) {
								$sql = 'SELECT * FROM `location` WHERE `location_id` = :lid';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':lid' => $_POST['id']
								));
							}
							//get all selected for opportunity
							else if(isset($_POST['opportunity_id'])) {
								$sql = 'SELECT location.location_id FROM location INNER JOIN opportunity_location ON opportunity_location.location_id = location.location_id WHERE opportunity_location.opportunity_id = :oid ORDER BY location.location_id;';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':oid' => $_POST['opportunity_id']
								));
							}
							//get all
							else {
								$sql = 'SELECT * FROM location WHERE (location_status = 0';
								if(isset($_POST['archive']))
									$sql .= ' OR location_status = 1';
								$sql .= ') ORDER BY location_title;';
								$stm = $pdo->prepare($sql);
								$stm->execute();
							}
							
							$rows = array();
							foreach($stm->fetchAll() as $row) {
								$rows[] = $row;
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
					case 'add':
						try {
							$sql = "INSERT INTO location (location_title, location_street, location_zip, location_coordinates, location_status) VALUES ('', '', '', '', 1);";
							$stm = $pdo->prepare($sql);
							$stm->execute();
								
							$sql = 'SELECT * FROM location ORDER BY location_id DESC LIMIT 1;';
							$stm = $pdo->prepare($sql);
							$stm->execute();
							
							$rows = array();
							foreach($stm->fetchAll() as $row) {
								$rows[] = $row;
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
					case 'update':
						try {
							if(isset($_POST['id'])) {
								$sql = 'UPDATE location SET location_title = :title, location_street = :street, location_zip = :zip, location_coordinates = :coordinates, location_status = :status WHERE location_id = :lid;';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':title' => $_POST['title'],
									':street' => $_POST['street'],
									':zip' => $_POST['zip'],
									':coordinates' => $_POST['coordinates'],
									':status' => $_POST['status'],
									':lid' => $_POST['id']
								));
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
					case 'delete':
						try {
							if(isset($_POST['id'])) {
								$sql = 'UPDATE location SET location_status = 1 WHERE location_id = :lid;';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':lid' => $_POST['id']
								));
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
					case 'restore':
						try {
							if(isset($_POST['id'])) {
								$sql = 'UPDATE location SET location_status = 0 WHERE location_id = :lid;';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':lid' => $_POST['id']
								));
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
				}
				break;		
			case 'hour':
				switch($action) {
					case 'get':
						try {
							//get all selected for opportunity
							if(isset($_POST['opportunity_id'])) {
								$sql = 'SELECT hours_time FROM hours WHERE opportunity_id = :oid;';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':oid' => $_POST['opportunity_id']
								));
							}
							
							$rows = array();
							foreach($stm->fetchAll() as $row) {
								$rows[] = $row;
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
				}
				break;	
			case 'role':
				switch($action) {
					case 'get':
						try {
							//get single
							if (isset($_POST['id'])) {
								$sql = 'SELECT * FROM `role` WHERE `role_id` = :rid LIMIT 1';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':rid' => $_POST['id']
								));
							}
							//get all for user
							else if (isset($_POST['user_id'])) {
								$sql = 'SELECT * FROM user_role LEFT JOIN role ON user_role.role_id = role.role_id WHERE user_role.user_id = :uid ORDER BY role.role_title;';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':uid' => $_POST['user_id']
								));
							}
							//get all
							else {
								$sql = 'SELECT * FROM role WHERE (role_status = 0';
								if(isset($_POST['archive']))
									$sql .= ' OR role_status = 1';
								$sql .= ') ORDER BY role_title;';
								$stm = $pdo->prepare($sql);
								$stm->execute(array());
							}
							
							$rows = array();
							foreach($stm->fetchAll() as $row) {
								$rows[] = $row;
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
					case 'add':
						try {
							$sql = "INSERT INTO `role` (`role_title`, `role_status`) VALUES ('', 1);";
							$stm = $pdo->prepare($sql);
							$stm->execute();
								
							$sql = 'SELECT * FROM role ORDER BY role_id DESC LIMIT 1;';
							$stm = $pdo->prepare($sql);
							$stm->execute();
							
							$rows = array();
							foreach($stm->fetchAll() as $row) {
								$rows[] = $row;
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
					case 'update':
						try {
							if(isset($_POST['id'])) {
								$sql = 'UPDATE role SET `role_title` = :title, `role_status` = :status WHERE role_id = :rid;';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':title' => $_POST['title'],
									':status' => $_POST['status'],
									':rid' => $_POST['id']
								));
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
					case 'delete':
						try {
							if(isset($_POST['id'])) {
								$sql = 'UPDATE role SET role_status = 1 WHERE role_id = :rid;';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':rid' => $_POST['id']
								));
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
					case 'restore':
						try {
							if(isset($_POST['id'])) {
								$sql = 'UPDATE role SET role_status = 0 WHERE role_id = :rid;';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':rid' => $_POST['id']
								));
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
				}
				break;		
			case 'user':
				switch($action) {
					case 'get':
						try {
							//get single
							if (isset($_POST['id'])) {
								$sql = 'SELECT * FROM user WHERE user_id = :uid;';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':uid' => $_POST['id']
								));
							}
							//get all
							else {
								$sql = 'SELECT * FROM user WHERE (user_status = 0';
								if(isset($_POST['archive']))
									$sql .= ' OR user_status = 1';
								$sql .= ') ORDER BY user_lname;';
								$stm = $pdo->prepare($sql);
								$stm->execute();
							}
							
							$rows = array();
							foreach($stm->fetchAll() as $row) {
								$rows[] = $row;
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
					case 'add':
						try {
							$sql = "INSERT INTO user (user_email) VALUES ('@wpb.org')";
							$stm = $pdo->prepare($sql);
							$stm->execute();
								
							$sql = 'SELECT * FROM user ORDER BY user_id DESC LIMIT 1';
							$stm = $pdo->prepare($sql);
							$stm->execute();
							
							$rows = array();
							foreach($stm->fetchAll() as $row) {
								$rows[] = $row;
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
					case 'reset':
						try {
							
							$pass = salt('password');
							$sql = "UPDATE user SET user_password = :password WHERE user_id = :uid";
							$stm = $pdo->prepare($sql);
							$stm->execute(array(
								':password' => $pass,
								':uid' => $_POST['id']
							));
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
					case 'update':
						try {
							if(isset($_POST['id'])) {								
								$sql = 'UPDATE user SET `user_fname` = :fname, `user_lname` = :lname, `user_email` = :email, `department_id` = :department, `user_status` = :status WHERE user_id = :uid;';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':fname' => $_POST['fname'],
									':lname' => $_POST['lname'],
									':email' => $_POST['email'],
									':department' => $_POST['department'],
									':status' => $_POST['status'],
									':uid' => $_POST['id']
								));
								
								// update roles
								$sql = 'DELETE FROM user_role WHERE user_id = :uid';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':uid' => $_POST['id']
								));
								
								$roles = (array) $_POST['roles'];
								foreach($roles as $role) {
									$sql = 'INSERT INTO user_role (user_id, role_id) VALUES (:uid, :rid);';
									$stm = $pdo->prepare($sql);
									$stm->execute(array(
										':uid' => $_POST['id'],
										':rid' => $role
									));
								}
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
					case 'delete':
						try {
							if(isset($_POST['id'])) {
								$sql = 'UPDATE user SET user_status = 1 WHERE user_id = :uid;';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':uid' => $_POST['id']
								));
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
					case 'restore':
						try {
							if(isset($_POST['id'])) {
								$sql = 'UPDATE user SET user_status = 0 WHERE user_id = :uid;';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':uid' => $_POST['id']
								));
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
				}
				break;		
			case 'department':
				switch($action) {
					case 'get':
						try {
							//get single
							if (isset($_POST['id'])) {
								$sql = 'SELECT * FROM department WHERE department_id = :did;';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':did' => $_POST['id']
								));
							}
							// active only
							else if (isset($_POST['active'])) {
								$sql = 'SELECT department.* FROM department
										INNER JOIN opportunity ON department.department_id = opportunity.department_id
										WHERE department_status = 0 
										GROUP BY department.department_id
										ORDER BY department.department_title;';
								$stm = $pdo->prepare($sql);
								$stm->execute();
							}
							//get all
							else {
								$sql = 'SELECT * FROM department WHERE (department_status = 0';
								if(isset($_POST['archive']))
									$sql .= ' OR department_status = 1';
								$sql .= ') ORDER BY department_title;';
								$stm = $pdo->prepare($sql);
								$stm->execute();
							}
							
							$rows = array();
							foreach($stm->fetchAll() as $row) {
								$rows[] = $row;
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
					case 'add':
						try {
							$sql = "INSERT INTO `department`(`department_title`, `department_status`) VALUES ('',1)";
							$stm = $pdo->prepare($sql);
							$stm->execute();
								
							$sql = 'SELECT * FROM department ORDER BY department_id DESC LIMIT 1;';
							$stm = $pdo->prepare($sql);
							$stm->execute();
							
							$rows = array();
							foreach($stm->fetchAll() as $row) {
								$rows[] = $row;
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
					case 'update':
						try {
							if(isset($_POST['id'])) {								
								$sql = 'UPDATE department SET `department_title` = :title, `department_status` = :status WHERE department_id = :did;';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':title' => $_POST['title'],
									':status' => $_POST['status'],
									':did' => $_POST['id']
								));
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
					case 'delete':
						try {
							if(isset($_POST['id'])) {
								$sql = 'UPDATE `department` SET `department_status`=1 WHERE `department_id`=:did';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':did' => $_POST['id']
								));
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
					case 'restore':
						try {
							if(isset($_POST['id'])) {
								$sql = 'UPDATE `department` SET `department_status`=0 WHERE `department_id`=:did';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':did' => $_POST['id']
								));
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
				}
				break;			
			case 'candidate':
				switch($action) {
					case 'get':
						try {
							//get single
							if (isset($_POST['id'])) {
								$sql = 'SELECT * FROM candidate
										LEFT JOIN candidate_opportunity ON candidate_opportunity.candidate_id = candidate.candidate_id
										LEFT JOIN opportunity ON candidate_opportunity.opportunity_id = opportunity.opportunity_id 
										LEFT JOIN status ON candidate_opportunity.status_id  = status.status_id
										WHERE candidate.candidate_id = :cid';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':cid' => $_POST['id']
								));
							}
							//get all selected for opportunity
							else if(isset($_POST['opportunity_id'])) {
								$sql = 'SELECT candidate.*, candidate_opportunity.status_id FROM candidate 
								INNER JOIN candidate_opportunity ON candidate_opportunity.candidate_id = candidate.candidate_id 
								WHERE candidate_opportunity.opportunity_id = :oid ORDER BY candidate.candidate_last;';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':oid' => $_POST['opportunity_id']
								));
							}
							//get all
							else {
								$sql = 'SELECT * FROM candidate 
										LEFT JOIN candidate_opportunity ON candidate_opportunity.candidate_id = candidate.candidate_id
										LEFT JOIN opportunity ON candidate_opportunity.opportunity_id = opportunity.opportunity_id 
										LEFT JOIN status ON candidate_opportunity.status_id  = status.status_id
										WHERE (candidate.candidate_status = 0';
								if(isset($_POST['archive']))
									$sql .= ' OR candidate.candidate_status = 1';
								$sql .= ') ORDER BY candidate.candidate_last;';
								$stm = $pdo->prepare($sql);
								$stm->execute();
							}
							
							$rows = array();
							foreach($stm->fetchAll() as $row) {
								$rows[] = $row;
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
					case 'add':
						try {
							$sql = "INSERT INTO `candidate` (`candidate_first`, `candidate_last`, `candidate_email`, `candidate_phone`, `candidate_expertise`, `candidate_av_sun`, `candidate_av_mon`, `candidate_av_tue`, `candidate_av_wed`, `candidate_av_thu`, `candidate_av_fri`, `candidate_av_sat`, `candidate_status`) VALUES ('', '', '', '', 0, 0, 0, 0, 0, 0, 0, 0, 1);";
							$stm = $pdo->prepare($sql);
							$stm->execute();
								
							$sql = 'SELECT * FROM candidate ORDER BY candidate_id DESC LIMIT 1;';
							$stm = $pdo->prepare($sql);
							$stm->execute();
							
							$rows = array();
							foreach($stm->fetchAll() as $row) {
								$rows[] = $row;
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
					case 'update':
						try {
							if(isset($_POST['id'])) {
								$sql = 'UPDATE candidate SET `candidate_first` = :first, `candidate_last` = :last, `candidate_email` = :email, `candidate_phone` = :phone, `candidate_expertise` = :expertise, `candidate_av_sun` = :sun, `candidate_av_mon` = :mon, `candidate_av_tue` = :tue, `candidate_av_wed` = :wed, `candidate_av_thu` = :thu, `candidate_av_fri` = :fri, `candidate_av_sat` = :sat, `candidate_status` = :status WHERE candidate_id = :cid;';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':first' => $_POST['first'],
									':last' => $_POST['last'],
									':email' => $_POST['email'],
									':phone' => $_POST['phone'],
									':expertise' => $_POST['expertise'],
									':sun' => $_POST['sun'],
									':mon' => $_POST['mon'],
									':tue' => $_POST['tue'],
									':wed' => $_POST['wed'],
									':thu' => $_POST['thu'],
									':fri' => $_POST['fri'],
									':sat' => $_POST['sat'],
									':status' => $_POST['status'],
									':cid' => $_POST['id']
								));
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
					case 'delete':
						try {
							if(isset($_POST['id'])) {
								$sql = 'UPDATE candidate SET candidate_status = 1 WHERE candidate_id = :cid;';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':cid' => $_POST['id']
								));
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
					case 'restore':
						try {
							if(isset($_POST['id'])) {
								$sql = 'UPDATE candidate SET candidate_status = 0 WHERE candidate_id = :cid;';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':cid' => $_POST['id']
								));
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
				}
				break;
			case 'status':
				switch($action) {
					case 'get':
						try {
							//get single
							if (isset($_POST['id'])) {
								$sql = 'SELECT * FROM `status` WHERE `status_id` = :sid LIMIT 1';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':sid' => $_POST['id']
								));
							}
							//get status for candidate_opportunity
							else if (isset($_POST['opportunity_id']) && isset($_POST['candidate_id'])) {
								$sql = 'SELECT status_id FROM candidate_opportunity WHERE opportunity_id = :oid AND candidate_id = :cid LIMIT 1';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':oid' => $_POST['opportunity_id'],
									':cid' => $_POST['candidate_id']
								));
							}
							//get all
							else {
								$sql = 'SELECT * FROM status ORDER BY status_title;';
								$stm = $pdo->prepare($sql);
								$stm->execute(array());
							}
							
							$rows = array();
							foreach($stm->fetchAll() as $row) {
								$rows[] = $row;
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
					case 'update':
						try {
							if (isset($_POST['opportunity_id']) && isset($_POST['candidate_id'])) {
								$sql = 'UPDATE candidate_opportunity SET status_id = :sid WHERE opportunity_id = :oid AND candidate_id = :cid';
								$stm = $pdo->prepare($sql);
								$stm->execute(array(
									':oid' => $_POST['opportunity_id'],
									':cid' => $_POST['candidate_id'],
									':sid' => $_POST['status_id']
								));
							}
						}
						catch(Exception $exception) {
							$error[] = 'Error: '.$exception->getMessage();
						}
						break;
				}
				break;
			//unsupported object
			default:
				$error[] = 'Error: Unsupported object';
				break;
		}
	}
	
	// if errors not empty, dump
	if(!empty($error)) {
		exit(json_encode($error));
	}
	// no errors, if rows exist, dump
	if(!empty($rows)) {
		exit(json_encode($rows));
	}
?>