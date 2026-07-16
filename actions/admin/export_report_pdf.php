<?php
ob_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/auth_check.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/vendor/autoload.php');
ob_end_clean();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    exit('Unauthorized access.');
}

use Dompdf\Dompdf;
use Dompdf\Options;

// Ensure error reporting doesn't output into PDF
error_reporting(0);

// Helper function
function exp_count($conn, $sql) {
    $res = mysqli_query($conn, $sql);
    if ($res && $row = mysqli_fetch_assoc($res)) {
        return (int)$row['c'];
    }
    return 0;
}

// ── Date Filters ─────────────────────────────────────────
$default_to   = date('Y-m-d');
$default_from = date('Y-m-d', strtotime('-1 month'));

$date_from = isset($_GET['from']) ? $_GET['from'] : $default_from;
$date_to   = isset($_GET['to'])   ? $_GET['to']   : $default_to;

$display_from = date('d-M-Y', strtotime($date_from));
$display_to   = date('d-M-Y', strtotime($date_to));
$display_range = $display_from . ' to ' . $display_to;
$generated_on  = date('d-M-Y H:i:s');

// SQL safe boundaries
$sql_from = mysqli_real_escape_string($conn, $date_from . ' 00:00:00');
$sql_to   = mysqli_real_escape_string($conn, $date_to   . ' 23:59:59');
$date_where = "created_at BETWEEN '$sql_from' AND '$sql_to'";

// ── 1. Counts ────────────────────────────
$total_reports    = exp_count($conn, "SELECT COUNT(*) AS c FROM REPORT_T WHERE $date_where");
$chat_reports     = exp_count($conn, "SELECT COUNT(*) AS c FROM REPORT_T WHERE $date_where AND reported_message_id IS NOT NULL");
$course_reports   = exp_count($conn, "SELECT COUNT(*) AS c FROM REPORT_T WHERE $date_where AND reported_marketplace_course_id IS NOT NULL");
$resolved_count   = exp_count($conn, "SELECT COUNT(*) AS c FROM REPORT_T WHERE $date_where AND report_status IN ('resolved','reviewed','rejected')");

// ── 2. Recent Reports Log ──────────────────────────────────
$recent_reports_sql = "
    SELECT
        created_at,
        CASE
            WHEN reported_message_id IS NOT NULL THEN 'Global Chat Report'
            WHEN reported_marketplace_course_id  IS NOT NULL THEN 'Course Report'
            ELSE 'Other'
        END AS source,
        reason
    FROM REPORT_T
    WHERE $date_where
    ORDER BY created_at DESC
    LIMIT 10
";
$recent_reports_result = mysqli_query($conn, $recent_reports_sql);
$recent_reports = [];
if ($recent_reports_result) {
    while ($row = mysqli_fetch_assoc($recent_reports_result)) {
        $recent_reports[] = $row;
    }
}

// ── 3. HTML Generation ─────────────────────────────────
// DomPDF requires strict, simple HTML/CSS
$html = '
<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: "Helvetica", "Arial", sans-serif;
            color: #1f2937;
            line-height: 1.5;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            color: #111827;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0 0 0;
            color: #6b7280;
            font-size: 14px;
        }
        .meta-info {
            width: 100%;
            margin-bottom: 30px;
            font-size: 12px;
        }
        .meta-info td {
            padding: 4px 0;
        }
        .meta-label {
            font-weight: bold;
            color: #4b5563;
            width: 120px;
        }
        .section-title {
            font-size: 18px;
            color: #111827;
            margin-top: 30px;
            margin-bottom: 15px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
        }
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .stats-table th, .stats-table td {
            border: 1px solid #d1d5db;
            padding: 12px;
            text-align: center;
        }
        .stats-table th {
            background-color: #f3f4f6;
            font-size: 13px;
            color: #374151;
        }
        .stats-table td {
            font-size: 18px;
            font-weight: bold;
            color: #111827;
        }
        
        .log-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .log-table th {
            background-color: #f9fafb;
            text-align: left;
            padding: 10px;
            border-bottom: 2px solid #d1d5db;
            color: #4b5563;
        }
        .log-table td {
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
            color: #374151;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Implose Ecosystem</h1>
        <p>User Reporting Summary Report</p>
    </div>

    <table class="meta-info">
        <tr>
            <td class="meta-label">Date Range:</td>
            <td>' . htmlspecialchars($display_range) . '</td>
            <td class="meta-label" style="text-align:right;">Generated On:</td>
            <td style="text-align:right;">' . $generated_on . '</td>
        </tr>
    </table>

    <div class="section-title">1. Report Overview</div>
    <table class="stats-table">
        <tr>
            <th>Total Reports</th>
            <th>Chat Reports</th>
            <th>Course Reports</th>
            <th>Resolved / Done</th>
        </tr>
        <tr>
            <td>' . $total_reports . '</td>
            <td>' . $chat_reports . '</td>
            <td>' . $course_reports . '</td>
            <td>' . $resolved_count . '</td>
        </tr>
    </table>

    <div class="section-title">2. Recent Reports Log</div>
    <table class="log-table">
        <thead>
            <tr>
                <th width="20%">Date</th>
                <th width="25%">Source</th>
                <th width="55%">Reason Snippet</th>
            </tr>
        </thead>
        <tbody>';

if (count($recent_reports) > 0) {
    foreach ($recent_reports as $report) {
        $formatted_date = date('M d, Y', strtotime($report['created_at']));
        $reason = htmlspecialchars(mb_strimwidth($report['reason'], 0, 80, "..."));
        
        $html .= '<tr>
            <td>' . $formatted_date . '</td>
            <td>' . htmlspecialchars($report['source']) . '</td>
            <td><em>"' . $reason . '"</em></td>
        </tr>';
    }
} else {
    $html .= '<tr><td colspan="3" style="text-align:center;">No recent reports found in this date range.</td></tr>';
}

$html .= '
        </tbody>
    </table>

    <div class="footer">
        Confidential Report - Generated automatically by Implose.gg System
    </div>

</body>
</html>
';

// ── 4. Generate PDF ─────────────────────────────────
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);

// (Optional) Setup the paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render the HTML as PDF
$dompdf->render();

$pdf_data = $dompdf->output();


// ── 5. Save the PDF on server first ─────────────────
$save_folder = $_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/uploads/reports';

if (!is_dir($save_folder)) {
    mkdir($save_folder, 0775, true);
}

$filename  = "Implose_Report_" . date('Ymd', strtotime($date_from)) . "_to_" . date('Ymd', strtotime($date_to)) . "_" . date('His') . ".pdf";
$save_path = $save_folder . '/' . $filename;

file_put_contents($save_path, $pdf_data);


// ── 6. Send the saved file to the user as download ──
if (ob_get_length()) ob_end_clean();

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($save_path));

readfile($save_path);
exit;

?>
