<?php
$pageTitle = "Attendance Report";
require_once '../includes/header.php';

// Auth check
if (!isLoggedIn() || (!isTeacher() && !isAdmin())) {
    setFlashMessage('You do not have permission to access that page.', 'danger');
    redirect(BASE_URL . '/login.php');
}

// Init
$academic   = new Academic();
$attendance = new Attendance();
$student    = new Student();



/* ---------------- DATA FETCH ---------------- */
$teacherClasses = isAdmin()
    ? $academic->getAllClasses()
    : $academic->getClassesByTeacher(getCurrentUserId());

if (empty($teacherClasses)) {
    setFlashMessage('No classes found.', 'warning');
    redirect(BASE_URL . '/' . (isAdmin() ? 'admin' : 'teacher') . '/dashboard.php');
}

$classId   = getQueryParam('class_id', $teacherClasses[0]['id']);
$date      = getQueryParam('date', date('Y-m-d'));
$fromDate  = getQueryParam('from_date', '');
$toDate    = getQueryParam('to_date', '');
$studentId = getQueryParam('student_id', '');

$selectedClass = null;
foreach ($teacherClasses as $c) {
    if ($c['id'] == $classId) {
        $selectedClass = $c;
        break;
    }
}
if (!$selectedClass) {
    $selectedClass = $teacherClasses[0];
    $classId = $selectedClass['id'];
}

$attendanceRecords = [];
if (!empty($fromDate) && !empty($toDate)) {
    $attendanceRecords = $attendance->getForClassDateRange($classId, $fromDate, $toDate, $studentId);
} else {
    $attendanceRecords = $attendance->getForClass($classId, $date, $studentId);
}

$allStudents = $student->getBySemester($selectedClass['semester_id']);

/* ---------------- STATS ---------------- */
$present = $absent = 0;
foreach ($attendanceRecords as $r) {
    $r['status'] === 'present' ? $present++ : $absent++;
}

$total = count($attendanceRecords);
$percentage = $total ? round(($present / $total) * 100, 2) : 0;
$isToday = empty($fromDate) && empty($toDate) && $date === date('Y-m-d');

/* ---------------- PRINT VARIABLES ---------------- */
$classNameJs = addslashes($selectedClass['name']);
$dateRangeJs = (!empty($fromDate) && !empty($toDate))
    ? formatDate($fromDate) . ' to ' . formatDate($toDate)
    : formatDate($date);

    $studentFilterJs = 'All Students';
    if ($studentId) {
        foreach ($allStudents as $s) {
            if ($s['id'] == $studentId) {
                $studentFilterJs = addslashes($s['full_name']);
                break;
            }
        }
    }

/* ---------------- PRINT ROWS ---------------- */
$printRows = '';
$count = 1;
foreach ($attendanceRecords as $r) {
    $printRows .= "
    <tr>
        <td>{$count}</td>
        <td>{$r['roll_number']}</td>
        <td>{$r['student_name']}</td>
        <td>".formatDate($r['date'])."</td>
        <td>".formatTime($r['time'])."</td>
        <td>".ucfirst($r['status'])."</td>
        <td>{$r['teacher_name']}</td>
    </tr>";
    $count++;
}
?>

<!-- ================= HTML UI ================= -->

<h2 class="mb-3">Attendance Report</h2>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-3">
                <select name="class_id" id="class_id" class="form-select">
                    <?php foreach ($teacherClasses as $c): ?>
                        <option value="<?= $c['id']; ?>" <?= $c['id']==$classId?'selected':''; ?>>
                            <?= $c['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <select name="student_id" class="form-select">
                    <option value="">All Students</option>
                    <?php foreach ($allStudents as $s): ?>
                        <option value="<?= $s['id']; ?>" <?= $studentId==$s['id']?'selected':''; ?>>
                            <?= $s['roll_number'].' - '.$s['full_name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <input type="date" name="date" class="form-control" value="<?= $date; ?>">
            </div>

            <div class="col-md-2">
                <input type="date" name="from_date" class="form-control" value="<?= $fromDate; ?>">
            </div>

            <div class="col-md-2">
                <input type="date" name="to_date" class="form-control" value="<?= $toDate; ?>">
            </div>

            <div class="col-md-12">
                <button class="btn btn-primary">Apply</button>
                <?php if ($total): ?>
                    <button type="button" class="btn btn-success" onclick="printReport()">Print</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- ================= TABLE ================= -->

<?php if ($total): ?>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>#</th><th>Roll</th><th>Name</th><th>Date</th><th>Time</th><th>Status</th><th>Teacher</th>
            <?php if ($isToday): ?><th>Action</th><?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php $i=1; foreach ($attendanceRecords as $r): ?>
        <tr>
            <td><?= $i++; ?></td>
            <td><?= $r['roll_number']; ?></td>
            <td><?= $r['student_name']; ?></td>
            <td><?= formatDate($r['date']); ?></td>
            <td><?= formatTime($r['time']); ?></td>
            <td><?= ucfirst($r['status']); ?></td>
            <td><?= $r['teacher_name']; ?></td>
            <?php if ($isToday): ?>
            <td>
                <form method="post" onsubmit="return confirm('Delete?');">
                    <?= csrfField(); ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="attendance_id" value="<?= $r['id']; ?>">
                    <button class="btn btn-danger btn-sm">Delete</button>
                </form>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<div class="alert alert-info">No attendance found.</div>
<?php endif; ?>

<?php
/* ---------------- JS ---------------- */
$extraJs = <<<JS
<script>
function printReport(){
    const html = `
    <html>
    <head>
        <title>Attendance Report</title>
        <style>
            body{font-family:Arial;margin:20px}
            table{width:100%;border-collapse:collapse}
            th,td{border:1px solid #ccc;padding:8px}
        </style>
    </head>
    <body>
        <h2>Attendance Report</h2>
        <p><b>Class:</b> {$classNameJs}</p>
        <p><b>Date:</b> {$dateRangeJs}</p>
        <p><b>Student:</b> {$studentFilterJs}</p>
        <table>
            <thead>
                <tr>
                    <th>#</th><th>Roll</th><th>Name</th><th>Date</th><th>Time</th><th>Status</th><th>Teacher</th>
                </tr>
            </thead>
            <tbody>
                {$printRows}
            </tbody>
        </table>
    </body>
    </html>`;
    const w = window.open('');
    w.document.write(html);
    w.document.close();
    w.print();
}
</script>
JS;

require_once '../includes/footer.php';
