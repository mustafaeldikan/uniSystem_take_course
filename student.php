<?
//echo "<pre>"; print_r($_REQUEST); echo "</pre>";
$connect = connectoDB();
menu(); // show menu everywhere
switch ($_REQUEST['op']) {
	case 'info':
		updateForm($_REQUEST['sid']);
		break;
	case 'update':
		updateStudent($_GET['sid'], $_GET['fname'], $_GET['lname'], $_GET['bno']);
		updateForm($_REQUEST['sid']);
		break;
	case 'studentSchedule':
		scheduleStudent($_GET['sid'], "SELECT t.sid,t.cid,c.title, r.rid,r.description, teacher.*, s.dayOfWeek, s.hourOfDay 	FROM take t, schedule s, course c, teach, teacher, room r 
	WHERE t.sid={$_GET['sid']} AND t.cid=c.cid AND c.cid=s.cid AND s.rid=r.rid AND t.cid=teach.cid AND teach.tid= teacher.tid;");
		break;
	case 'courseSchedule':
		scheduleStudent($_GET['cid'], "SELECT t.sid,t.cid,c.title, r.rid,r.description, teacher.*, s.dayOfWeek, s.hourOfDay 	FROM take t, schedule s, course c, teach, teacher, room r 
	WHERE t.cid={$_GET['cid']} AND t.cid=c.cid AND c.cid=s.cid AND s.rid=r.rid AND t.cid=teach.cid AND teach.tid= teacher.tid;");
		break;
	case 'teacherSchedule':
		scheduleStudent($_GET['sid'], "SELECT t.sid,t.cid,c.title, r.rid,r.description, teacher.*, s.dayOfWeek, s.hourOfDay 	FROM take t, schedule s, course c, teach, teacher, room r 
	WHERE teacher.tid={$_GET['tid']} AND t.cid=c.cid AND c.cid=s.cid AND s.rid=r.rid AND t.cid=teach.cid AND teach.tid=teacher.tid;");
		break;
	case 'roomSchedule':
		scheduleStudent($_GET['sid'], "SELECT t.sid,t.cid,c.title, r.rid,r.description, teacher.*, s.dayOfWeek, s.hourOfDay 	FROM take t, schedule s, course c, teach, teacher, room r 
	WHERE r.rid={$_GET['rid']} AND t.cid=c.cid AND c.cid=s.cid AND s.rid=r.rid AND t.cid=teach.cid AND teach.tid=teacher.tid;");
		break;
	case 'deleteCourse':
		deleteCourse($_GET['sid'], $_GET['cid']);
		chosenCourses($_GET['sid']);
		break;
	case 'takeCourse':
		takeCourse($_GET['sid'], $_GET['cid']);
		chosenCourses($_GET['sid']);
		break;
	case 'chosenCourses':
		chosenCourses($_GET['sid']);
		break;
	case 'chooseCourse':
		if (! $_REQUEST['sid']) { // if sid is known
			echo 'Please first choose a student from the lise below<br>';
			studentlistele($_REQUEST['sid'] ? $_REQUEST['sid'] : '', $_GET['col'] ? $_GET['col'] : 'sid', $_GET['direct'] ? $_GET['direct'] : 'ASC', $_GET['pageno'] ? $_GET['pageno'] : '1');
		} else {
			if (! $_REQUEST['did']) { // if a did is not given, then use student's did
				$sql = "SELECT * FROM student WHERE sid={$_REQUEST['sid']} LIMIT 1;";
				$rowSet = mysql_query($sql) or exit($sql . ' ' . mysql_errno() . '-' . mysql_error());
				$stdRow = mysql_fetch_assoc($rowSet);
				$studentsDid = $stdRow['did'];
			} else
				$studentsDid = $_REQUEST['did'];
			// display department select box
			selection($studentsDid, $_REQUEST['sid']);
			// display courses in the chosen department
			courseList($studentsDid, $_REQUEST['sid']);
		}
		break;
	default:
		studentlistele($_REQUEST['sid'] ? $_REQUEST['sid'] : '', $_GET['col'] ? $_GET['col'] : 'sid', $_GET['direct'] ? $_GET['direct'] : 'ASC', $_GET['pageno'] ? $_GET['pageno'] : '1');
}
mysql_close($connect);
exit;

function menu()
{ ?>
	<h4>Main Menu</h4>
	<a href='?op=list&sid=<?= $_REQUEST['sid']; ?>'>Student list</a><br>
	<a href='?op=info&sid=<?= $_REQUEST['sid']; ?>'>Student Info</a><br>
	<a href='?op=chooseCourse&sid=<?= $_REQUEST['sid']; ?>'>Take a course</A><br>
	<a href='?op=chosenCourses&sid=<?= $_REQUEST['sid']; ?>'>Courses taken</A><br>
	<a href='?op=studentSchedule&sid=<?= $_REQUEST['sid']; ?>'>Weekly Schedule</a><br><br>
<?
	if ($_REQUEST['sid']) { // if a sid is not given
		$sql = "SELECT * FROM student WHERE sid={$_REQUEST['sid']} LIMIT 1;";
		$rowSet = mysql_query($sql) or exit($sql . ' ' . mysql_errno() . '-' . mysql_error());
		$stdRow = mysql_fetch_assoc($rowSet);
		echo "<span style='color:green;'>No: {$stdRow['sid']} Name: {$stdRow['fname']} {$stdRow['lname']}</span><br>";
	}
}

function selection($studentsDid, $sid)
{
	$sql = "SELECT * FROM department;";
	$rowSet = mysql_query($sql) or exit($sql . '-' . mysql_errno() . '-' . mysql_error());
	echo "<FORM action=? method=POST>
	<SELECT name=did>";
	while ($row = mysql_fetch_assoc($rowSet))
		echo "<OPTION VALUE='{$row['did']}'" . ($row['did'] == $studentsDid ? ' selected ' : '') . ">{$row['dname']}</OPTION>";
	echo "</SELECT>
	<input type=submit name=ok value='SHOW COURSES'><br>
	<input type=hidden name=op value=chooseCourse>
	<input type=hidden name=sid value=$sid>
	</form>";
}

function courseList($did, $sid)
{
	$sql = "SELECT * FROM course WHERE did=$did;"; //echo $sql;
	$rowSet = mysql_query($sql) or exit($sql . '-' . mysql_errno() . '-' . mysql_error());
	echo "
	<table border=1><tr><th>Code</th><th>Title</th><th>Credits</th><th>Take</th></tr>";
	while ($row = mysql_fetch_assoc($rowSet))
		echo "<tr><td>{$row['cid']}</td><td>{$row['title']}</td><td>{$row['credits']}</td><td><a href='?op=takeCourse&sid={$sid}&cid={$row['cid']}'>choose</a></td></tr>";
	echo '</table>';
}

function chosenCourses($sid)
{
	$sql = "SELECT * FROM take t,course c WHERE t.sid=$sid AND t.cid=c.cid;"; //echo $sql;
	$rowSet = mysql_query($sql) or exit($sql . '-' . mysql_errno() . '-' . mysql_error());
	echo "
	<table border=1><tr><th>Code</th><th>Title</th><th>Credits</th><th>Take</th></tr>";
	while ($row = mysql_fetch_assoc($rowSet))
		echo "<tr><td>{$row['cid']}</td><td>{$row['title']}</td><td>{$row['credits']}</td><td><a href='?op=deleteCourse&sid={$sid}&cid={$row['cid']}'>delete</a></td></tr>";
	echo '</table>';
}

function updateStudent($sid, $fname, $lname, $did)
{
	$sql = "UPDATE student SET
				sid		= $sid,
				fname	= '$fname',
				lname	= '$lname',
				did		= $did 
			WHERE sid = $sid LIMIT 1;";
	mysql_query($sql) or exit(mysql_errno() . '-' . mysql_error());
	echo mysql_affected_rows() . " records updated.<br>";
}

function takeCourse($sid, $cid)
{
	$sql = "INSERT IGNORE INTO take (sid, cid) VALUES($sid, '$cid');";
	mysql_query($sql) or exit(mysql_errno() . '-' . mysql_error());
	if (mysql_affected_rows())
		echo "Course added.<br>";
	else
		echo "Unable to add the course.<br>";
}

function deleteCourse($sid, $cid)
{
	$sql = "DELETE FROM take WHERE sid=$sid AND cid=$cid LIMIT 1;";
	mysql_query($sql) or exit(mysql_errno() . '-' . mysql_error());
	if (mysql_affected_rows())
		echo "Course deleted.<br>";
	else
		echo "Unable to delete the course.<br>";
}

function updateForm($sid)
{

	$sql = "SELECT * FROM student WHERE sid=$sid LIMIT 1;";
	$rowSet = mysql_query($sql) or exit(mysql_errno() . '-' . mysql_error());
	$row = mysql_fetch_assoc($rowSet);
	echo "
	<form method=GET>
	<table>
	<tr><td colspan=2>Update Student </td></tr>
	<tr><td>Sid</td><td> <input type=text readonly name=sid value='{$row['sid']}'> </td></tr>
	<tr><td>fname</td><td> <input type=text name=fname value='{$row['fname']}'> </td></tr>
	<tr><td>lname</td><td> <input type=text name=lname value='{$row['lname']}'> </td></tr>
	<tr><td>Did</td><td> <input type=text name=bno value='{$row['bno']}'> </td></tr>
	<tr><td></td><td> <input type=submit name=gonder value=Save> </td></tr>
	</table>
	<input type=hidden name=op value=update>
	</form>";
}



function connectoDB()
{
	$servername = "localhost";
	$username = "root";
	$password = "";
	$dbname = "uni";

	$con = new mysqli($servername, $username, $password, $dbname) or die("Connection failed: " . $con->connect_error);
	return $con;
}

function studentlistele($sidChosen, $col, $dir, $pageNo)
{
	//list students from the student table
	$start = ($pageNo - 1) * 5;
	$rowSet = mysql_query("SELECT * FROM student ORDER BY $col $dir LIMIT $start,5;") or exit(mysql_errno() . '-' . mysql_error());
	// step 3: retriwve records from result set
	echo "
	<table border=1><tr><th><a href='?op=list&col=sid&direct=" . ($dir == 'ASC' ? 'DESC' : 'ASC') . "'>sid</a></th><th><a href='?op=list&col=fname&direct=" . ($dir == 'ASC' ? 'DESC' : 'ASC') . "'>fname</a></th><th><a href='?op=list&col=lname&direct=" . ($dir == 'ASC' ? 'DESC' : 'ASC') . "'>lname</a></th><th>delete</th></tr>";
	while ($row = mysql_fetch_assoc($rowSet))
		echo "<tr><td>{$row['sid']}</td><td>{$row['fname']}</td><td>{$row['lname']}</td><td>" . ($sidChosen != $row['sid'] ? "<a href='?op=chooseStudent&sid={$row['sid']}'>choose</a>" : Chosen) . "</td></tr>";
	echo '</table>';

	$rowSet = mysql_query("SELECT COUNT(*) sayi FROM student;") or exit(mysql_errno() . '-' . mysql_error());
	$row = mysql_fetch_assoc($rowSet);
	for ($p = 1; $p <= ceil($row['sayi'] / 5); $p++)
		if ($pageNo == $p)
			echo "$p ";
		else
			echo "<a href='?op=list&pageno=$p&col=$col&direct=$dir'>$p</a> ";
}

function scheduleStudent($sid, $sql)
{

	$Days = array('M' => '', 'T' => '', 'W' => '', 'H' => '', 'F' => '');
	$schedule = array('08' => $Days, '09' => $Days, '10' => $Days, '11' => $Days, '12' => $Days, '13' => $Days, '14' => $Days, '15' => $Days, '16' => $Days);
	$rowSet = mysql_query($sql) or exit($sql . '-' . mysql_errno() . '-' . mysql_error());
	//echo "<table border=1>";
	while ($row = mysql_fetch_assoc($rowSet)) {
		//echo "<tr><td>{$row['sid']}</td><td>{$row['cid']} {$row['title']} </td><td>{$row['hourOfDay']} {$row['dayOfWeek']} </td><td>{$row['rid']} {$row['description']} </td><td>{$row['tid']} {$row['fname']} {$row['lname']}</td></tr>";
		$schedule[$row['hourOfDay']][$row['dayOfWeek']] =
			"<a href=?op=courseSchedule&sid=$sid&cid={$row['cid']}>{$row['cid']} {$row['title']}</a><br>
		<a href=?op=roomSchedule&sid=$sid&rid={$row['rid']}>{$row['description']}</a><br>
		<a href=?op=teacherSchedule&sid=$sid&tid={$row['tid']}>{$row['fname']} {$row['lname']}</a>";
	}
	//echo '</table>';
	echo "<table border=1>";
	echo "<tr><td>Hour</td><td>Mon</td><td>Tue</td><td>Wed</td><td>Thu</td><td>Fri</td></tr>";
	foreach ($schedule as $hour => $days)
		echo "<tr><td>$hour</td><td>{$days['M']}&nbsp;</td><td>{$days['T']}&nbsp;</td><td>{$days['W']}&nbsp;</td><td>{$days['H']}&nbsp;</td><td>{$days['F']}&nbsp;</td></tr>";
	echo '</table>';
}
?>