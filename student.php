<?php
//echo "<pre>"; print_r($_REQUEST); echo "</pre>";
$connect = connectToDB();
menu(); // show menu everywhere
$op = $_REQUEST['op'] ?? '';

switch ($op) {
	case 'info':
		updateForm($_REQUEST['sid']);
		break;
	case 'update':
		updateStudent($_REQUEST['sid'], $_REQUEST['fname'], $_REQUEST['lname'], $_REQUEST['did'], $_REQUEST['birthdate'], $_REQUEST['email']);
		updateForm($_REQUEST['sid']);
		break;
	case 'studentSchedule':
		scheduleStudent($_GET['sid'], "SELECT t.sid, t.cid, c.title, r.rid, r.description, teacher.*, s.dayOfWeek, s.hourOfDay
            FROM take t
            JOIN schedule s ON t.cid = s.cid
            JOIN course c ON t.cid = c.cid
            JOIN teach ON t.cid = teach.cid
            JOIN teacher ON teach.tid = teacher.tid
            JOIN room r ON s.rid = r.rid
            WHERE t.sid = ?");
		break;
	case 'courseSchedule':
		scheduleStudent($_GET['cid'], "SELECT t.sid, t.cid, c.title, r.rid, r.description, teacher.*, s.dayOfWeek, s.hourOfDay
            FROM take t
            JOIN schedule s ON t.cid = s.cid
            JOIN course c ON t.cid = c.cid
            JOIN teach ON t.cid = teach.cid
            JOIN teacher ON teach.tid = teacher.tid
            JOIN room r ON s.rid = r.rid
            WHERE t.cid = ?");
		break;
	case 'teacherSchedule':
		scheduleStudent($_GET['tid'], "SELECT t.sid, t.cid, c.title, r.rid, r.description, teacher.*, s.dayOfWeek, s.hourOfDay
            FROM take t
            JOIN schedule s ON t.cid = s.cid
            JOIN course c ON t.cid = c.cid
            JOIN teach ON t.cid = teach.cid
            JOIN teacher ON teach.tid = teacher.tid
            JOIN room r ON s.rid = r.rid
            WHERE teacher.tid = ?");
		break;
	case 'roomSchedule':
		scheduleStudent($_GET['rid'], "SELECT t.sid, t.cid, c.title, r.rid, r.description, teacher.*, s.dayOfWeek, s.hourOfDay
            FROM take t
            JOIN schedule s ON t.cid = s.cid
            JOIN course c ON t.cid = c.cid
            JOIN teach ON t.cid = teach.cid
            JOIN teacher ON teach.tid = teacher.tid
            JOIN room r ON s.rid = r.rid
            WHERE r.rid = ?");
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
		if (!isset($_REQUEST['sid'])) {
			echo 'Please first choose a student from the list below<br>';
			studentList($_REQUEST['sid'] ?? '', $_GET['col'] ?? 'sid', $_GET['direct'] ?? 'ASC', $_GET['pageno'] ?? '1');
		} else {
			$studentsDid = $_REQUEST['did'] ?? getStudentDid($_REQUEST['sid']);
			// display department select box
			selection($studentsDid, $_REQUEST['sid']);
			// display courses in the chosen department
			courseList($studentsDid, $_REQUEST['sid']);
		}
		break;
	case 'chooseStudent':
		if (!isset($_REQUEST['sid'])) {
			echo 'Please first choose a student from the list below<br>';
			studentList($_REQUEST['sid'] ?? '', $_GET['col'] ?? 'sid', $_GET['direct'] ?? 'ASC', $_GET['pageno'] ?? '1');
		} else {

			studentList($_REQUEST['sid'], $_GET['col'] ?? 'sid', $_GET['direct'] ?? 'ASC', $_GET['pageno'] ?? '1');
		}
		break;

	default:
		studentList($_REQUEST['sid'] ?? '', $_GET['col'] ?? 'sid', $_GET['direct'] ?? 'ASC', $_GET['pageno'] ?? '1');
}

function connectToDB()
{
	$servername = "localhost";
	$username = "root";
	$password = "";
	$dbname = "uni";

	$connect = new mysqli($servername, $username, $password, $dbname);
	if ($connect->connect_error) {
		die("Connection failed: " . $connect->connect_error);
	}
	return $connect;
}

function menu()
{
	global $connect,$op;
	$sid = $_REQUEST['sid'] ?? '1';
	$op = $_REQUEST['op'] ?? '';
	echo "
    <h4>Main Menu</h4>
    <a href='?op=list&sid=$sid'>Student list</a><br>
    <a href='?op=info&sid=$sid'>Student Info</a><br>
    <a href='?op=chooseCourse&sid=$sid'>Take a course</a><br>
    <a href='?op=chosenCourses&sid=$sid'>Courses taken</a><br>
    <a href='?op=studentSchedule&sid=$sid'>Weekly Schedule</a><br><br>";

	if ($sid) {
		// Query to join student and take tables to get the grade
		$stmt = $connect->prepare("
            SELECT student.sid, student.fname, student.lname, take.grade ,d.dname
            FROM student
            LEFT JOIN take ON student.sid = take.sid 
			LEFT JOIN department d ON student.did = d.did
            WHERE student.sid = ? 
            LIMIT 1
        ");
		$stmt->bind_param('i', $sid);
		$stmt->execute();
		$result = $stmt->get_result();
		if ($result->num_rows > 0) {
            while ($stdRow = $result->fetch_assoc()) {
                if ($op =='studentSchedule'||$op =='chooseCourse'||$op =='chosenCourses') {
                    $grade = $stdRow['grade'] ?? 'N/A'; // Default to 'N/A' if grade is not set
                    echo "<span style='color:green;'>Sid: {$stdRow['sid']} Name: {$stdRow['fname']} {$stdRow['lname']} DPT: {$stdRow['dname']} GPA: {$grade}</span><br>";
                }
            }
        } else {
            echo "No student data found.";
        }
	}
}


function selection($studentsDid, $sid)
{
	global $connect;
	$stmt = $connect->prepare("SELECT * FROM department");
	$stmt->execute();
	$result = $stmt->get_result();

	echo "<form action='?' method='POST'>
    <select name='did'>";
	while ($row = $result->fetch_assoc()) {
		echo "<option value='{$row['did']}'" . ($row['did'] == $studentsDid ? ' selected ' : '') . ">{$row['dname']}</option>";
	}
	echo "</select>
    <input type='submit' name='ok' value='SHOW COURSES'><br>
    <input type='hidden' name='op' value='chooseCourse'>
    <input type='hidden' name='sid' value='$sid'>
    </form>";
}

function courseList($did, $sid)
{
	global $connect;
	$stmt = $connect->prepare("
        SELECT c.cid, c.title, c.credits, t.fname AS teacher_fname, t.lname AS teacher_lname
        FROM course c
        JOIN teach te ON c.cid = te.cid
        JOIN teacher t ON te.tid = t.tid
        WHERE c.did = ?
    ");
	$stmt->bind_param('i', $did);
	$stmt->execute();
	$result = $stmt->get_result();

	echo "<table border='1'><tr><th>Code</th><th>Title</th><th>Credits</th><th>Teacher</th><th>Take</th></tr>";
	while ($row = $result->fetch_assoc()) {
		$teacherName = $row['teacher_fname'] . ' ' . $row['teacher_lname'];
		echo "<tr>
            <td>{$row['cid']}</td>
            <td>{$row['title']}</td>
            <td>{$row['credits']}</td>
            <td>{$teacherName}</td>
            <td><a href='?op=takeCourse&sid={$sid}&cid={$row['cid']}'>choose</a></td>
        </tr>";
	}
	echo '</table>';
}


function chosenCourses($sid)
{
	global $connect,$op;
	$op = $_REQUEST['op'] ?? '';
	$stmt = $connect->prepare("
        SELECT ta.cid, c.title, c.credits, t.fname AS teacher_fname, t.lname AS teacher_lname, SUM(c.credits) OVER() AS summ
        FROM take ta 
        JOIN course c ON ta.cid = c.cid
        JOIN teach te ON c.cid = te.cid
        JOIN teacher t ON te.tid = t.tid
        WHERE ta.sid = ?
    ");
	$stmt->bind_param('i', $sid);
	$stmt->execute();
	$result = $stmt->get_result();

	$totalCredits = 0;
	$courses = []; // Store all rows temporarily

	// Fetch the data
	while ($row = $result->fetch_assoc()) {
		$courses[] = $row; // Store each row in an array
		if ($totalCredits == 0) {
			$totalCredits = $row['summ']; // Store the total credits from the first row
		}
	}

	// Display the total credits above the table

		echo "<span style='color:green;'>Total Credits: {$totalCredits}</span>";
	
	

	// Generate the table
	echo "<table border='1'>
    <tr>
    <th>Code</th>
    <th>Title</th>
    <th>Credits</th>
    <th>Teacher</th>
    <th>Delete</th>
    </tr>";

	foreach ($courses as $row) {
		$teacherName = $row['teacher_fname'] . ' ' . $row['teacher_lname'];
		echo "<tr>
            <td>{$row['cid']}</td>
            <td>{$row['title']}</td>
            <td>{$row['credits']}</td>
            <td>{$teacherName}</td>
            <td><a href='?op=deleteCourse&sid={$sid}&cid={$row['cid']}'>delete</a></td>
        </tr>";
	}
	echo '</table>';
}




function updateStudent($sid, $fname, $lname, $did, $birthdate, $email)
{
	global $connect;

	// Update the student table
	$stmt = $connect->prepare("UPDATE student SET fname = ?, lname = ?, did = ?, birthdate = ? WHERE sid = ?");
	$stmt->bind_param('ssisi', $fname, $lname, $did, $birthdate, $sid);
	$stmt->execute();
	echo $stmt->affected_rows . " student records updated.<br>";

	// Update the department table
	$stmt = $connect->prepare("UPDATE department SET email = ? WHERE did = ?");
	$stmt->bind_param('si', $email, $did);
	$stmt->execute();
	echo $stmt->affected_rows . " department email updated.<br>";
}



function takeCourse($sid, $cid)
{
	global $connect;
	$stmt = $connect->prepare("INSERT IGNORE INTO take (sid, cid) VALUES (?, ?)");
	$stmt->bind_param('is', $sid, $cid);
	$stmt->execute();
	if ($stmt->affected_rows) {
		echo "Course added.<br>";
	} else {
		echo "Unable to add the course.<br>";
	}
}

function deleteCourse($sid, $cid)
{
	global $connect;
	$stmt = $connect->prepare("DELETE FROM take WHERE sid = ? AND cid = ? LIMIT 1");
	$stmt->bind_param('ii', $sid, $cid);
	$stmt->execute();
	if ($stmt->affected_rows) {
		echo "Course deleted.<br>";
	} else {
		echo "Unable to delete the course.<br>";
	}
}

function updateForm($sid)
{
	global $connect;

	// Fetch student information
	$stmt = $connect->prepare("SELECT student.*, department.email, department.dname, department.did 
        FROM student 
        JOIN department ON student.did = department.did 
        WHERE student.sid = ? LIMIT 1");
	$stmt->bind_param('i', $sid);
	$stmt->execute();
	$result = $stmt->get_result();
	$row = $result->fetch_assoc();

	// Fetch department list
	$deptStmt = $connect->prepare("SELECT did, dname FROM department");
	$deptStmt->execute();
	$deptResult = $deptStmt->get_result();

	// Start the form
	echo "<form method='GET'>
    <table border='1'>
        <tr>
		<td rowspan='4'  id='picture'>
                    <label for='fileInput' style='display: block; text-align: center;cursor: pointer;'>
					<img src='placeholder-image.png' alt='Picture Here' style='width: 100px; height: 100px;'></label>
                    <input type='file' id='fileInput' style='opacity: 0;position: absolute;'  name='picture' accept='image/*'>
                </td>
            <td>Firstname</td>
            <td>
                <input type='text' name='fname' value='{$row['fname']}'>
            </td>
			
		<td>Firstname</td>
            <td>
                <input type='text' name='lname' value='{$row['lname']}'>
            </td>

            
        </tr>
        <tr>
            <td>Sid</td>
            <td><input type='text'  name='sid' value='{$row['sid']}'></td>
            <td>Birthdate</td>
             <td><input type='date' name='birthdate' value='{$row['birthdate']}'></td>
        </tr>
        <tr>
            <td>Email</td>
            <td><input type='email' name='email' value='{$row['email']}'></td>
        </tr>
        <tr>
            <td>Dept</td>
            <td>
                <select name='did'>";
	// Populate the select box with departments
	while ($deptRow = $deptResult->fetch_assoc()) {
		$selected = ($deptRow['did'] == $row['did']) ? ' selected ' : '';
		echo "<option value='{$deptRow['did']}'{$selected}>{$deptRow['dname']}</option>";
	}
	echo "</select>
            </td>
            <td><input type='submit' name='gonder' value='SAVE'> <a href='?op=list'>Home</a></td>
        </tr>
    </table>
    <input type='hidden' name='op' value='update'>
</form>";
}




function studentList($sidChosen, $col, $dir, $pageNo)
{
	global $connect;
	$start = ($pageNo - 1) * 5;
	$stmt = $connect->prepare("SELECT student.*, department.dname FROM student LEFT JOIN department ON student.did = department.did ORDER BY $col $dir LIMIT ?, 5");
	$stmt->bind_param('i', $start);
	$stmt->execute();
	$result = $stmt->get_result();

	echo "<table border='1'>
    <tr>
        <th><a href='?op=list&col=sid&direct=" . ($dir == 'ASC' ? 'DESC' : 'ASC') . "&pageno=$pageNo'>sid</a></th>
        <th><a href='?op=list&col=fname&direct=" . ($dir == 'ASC' ? 'DESC' : 'ASC') . "&pageno=$pageNo'>fname</a></th>
        <th><a href='?op=list&col=lname&direct=" . ($dir == 'ASC' ? 'DESC' : 'ASC') . "&pageno=$pageNo'>lname</a></th>
        <th><a href='?op=list&col=dname&direct=" . ($dir == 'ASC' ? 'DESC' : 'ASC') . "&pageno=$pageNo'>Dept</a></th>
        <th>delete</th>
    </tr>";

	while ($row = $result->fetch_assoc()) {
		// If the current row is the chosen student, apply a different background color
		$bgColor = ($sidChosen == $row['sid']) ? "style='background-color: yellow;'" : "";
		echo "
        <tr $bgColor>
            <td>{$row['sid']}</td>
            <td>{$row['fname']}</td>
            <td>{$row['lname']}</td>
            <td>{$row['dname']}</td>
            <td>" . ($sidChosen != $row['sid'] ? "<a href='?op=chooseStudent&sid={$row['sid']}&pageno=$pageNo&col=$col&direct=$dir'>choose</a>" : "Chosen") . "</td>
        </tr>";
	}
	echo '</table>';

	$stmt = $connect->prepare("SELECT COUNT(*) AS count FROM student");
	$stmt->execute();
	$result = $stmt->get_result();
	$row = $result->fetch_assoc();
	for ($p = 1; $p <= ceil($row['count'] / 5); $p++) {
		if ($pageNo == $p) {
			echo "$p ";
		} else {
			echo "<a href='?op=list&pageno=$p&col=$col&direct=$dir'>$p</a> ";
		}
	}
}



function scheduleStudent($id, $sql)
{
    global $connect, $op;
    $op = $_REQUEST['op'] ?? '';

    $stmt = $connect->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();

    $Days = ['M' => '', 'T' => '', 'W' => '', 'H' => '', 'F' => ''];
    $schedule = array_fill_keys(range('8', '16'), $Days);

    $header = ''; // Initialize a variable to hold the header content

    while ($row = $result->fetch_assoc()) {
        $hour = (int) $row['hourOfDay'];
        $day = $row['dayOfWeek'];
        if (isset($Days[$day])) {
            $schedule[$hour][$day] =
                "<a href=?op=courseSchedule&cid={$row['cid']}>{$row['cid']} {$row['title']}</a><br>
                <a href=?op=roomSchedule&rid={$row['rid']}>{$row['description']}</a><br>
                <a href=?op=teacherSchedule&tid={$row['tid']}>{$row['fname']} {$row['lname']}</a>";
        }

        // Set the header based on the operation
        if ($op == 'courseSchedule') {
            $header = "<span style='color:green;'>Weekly Schedule for Course: {$row['cid']} {$row['title']}</span><br>";
        } elseif ($op == 'teacherSchedule') {
            $header = "<span style='color:green;'>Weekly Schedule for Instructor: {$row['fname']} {$row['lname']}</span><br>";
        } elseif ($op == 'roomSchedule') {
            $header = "<span style='color:green;'>Weekly Schedule for Room: {$row['description']}</span><br>";
        }
    }

    // Output the header
    echo $header;

    // Output the schedule table
    echo "<table border='1'>";
    echo "<tr><td>Hour</td><td>Mon</td><td>Tue</td><td>Wed</td><td>Thu</td><td>Fri</td></tr>";
    foreach ($schedule as $hour => $days) {
        echo "<tr>
        <td>$hour</td>
        <td>{$days['M']}&nbsp;</td>
        <td>{$days['T']}&nbsp;</td>
        <td>{$days['W']}&nbsp;</td>
        <td>{$days['H']}&nbsp;</td>
        <td>{$days['F']}&nbsp;</td>
        </tr>";
    }
    echo '</table>';
}


function getStudentDid($sid)
{
	global $connect;
	$stmt = $connect->prepare("SELECT did FROM student WHERE sid = ? LIMIT 1");
	$stmt->bind_param('i', $sid);
	$stmt->execute();
	$result = $stmt->get_result();
	$row = $result->fetch_assoc();
	return $row['did'] ?? '';
}

mysqli_close($connect);
?>