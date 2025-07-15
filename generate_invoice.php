<?php
// --- START OUTPUT BUFFERING ---
// این دستور تمام خروجی های بعدی (حتی خطاها) را در حافظه موقت نگه می‌دارد
ob_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Include TCPDF library and configuration file
require_once('tcpdf/tcpdf.php');
require_once('config.php');

// --- Database Operations ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // بهتر است خروجی بافر را پاک کرده و بعد پیام خطا را نمایش دهیم
    ob_end_clean();
    die("Error: Invalid user ID.");
}
$userId = (int)$_GET['id'];
$stmt_user = $conn->prepare("SELECT * FROM employees WHERE id = ?");
$stmt_user->bind_param("i", $userId);
$stmt_user->execute();
$userResult = $stmt_user->get_result();
$user = $userResult->fetch_assoc();
if (!$user) {
    ob_end_clean();
    die("Error: No user found with this ID.");
}
$stmt_costs = $conn->prepare("SELECT * FROM employee_costs WHERE employee_id = ?");
$stmt_costs->bind_param("i", $userId);
$stmt_costs->execute();
$costsResult = $stmt_costs->get_result();
$costs = $costsResult->fetch_all(MYSQLI_ASSOC);
$conn->close();


// 2. Custom PDF Class for Header and Footer
class MYPDF extends TCPDF {
    public function Header() {
        $image_file = __DIR__ . '/img/logo.png';
        if (file_exists($image_file)) {
            $this->Image($image_file, 170, 10, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        } else {
            $this->SetFont('helvetica', 'B', 20);
            $this->Cell(0, 15, 'Spring Beauty Clinic', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        }
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// 3. Create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Clinic Name');
$pdf->SetTitle('Patient Invoice');
$pdf->setPrintHeader(true);
$pdf->setPrintFooter(true);

// **IMPORTANT: Add and set the Farsi font**
$fontPath = __DIR__ . '/fonts/Vazirmatn-Medium.ttf';
if (!file_exists($fontPath)) {
    ob_end_clean();
    die('ERROR: Font file not found at: ' . $fontPath);
}
$fontname = TCPDF_FONTS::addTTFfont($fontPath, 'TrueTypeUnicode', '', 32);
if ($fontname === false) {
    ob_end_clean();
    die('ERROR: Could not add the font. Please check that the tcpdf/fonts directory is writable.');
}

$pdf->SetFont($fontname, '', 12, '', true);
$pdf->setRTL(true);
$pdf->AddPage();
$pdf->Ln(20);

// 4. Patient Information
$pdf->SetFont($fontname, 'B', 14);
$pdf->Cell(0, 10, 'مشخصات بیمار', 0, 1, 'R');
$pdf->SetFont($fontname, '', 12);
$pdf->Cell(40, 7, 'نام و نام خانوادگی:', 0, 0, 'R');
$pdf->Cell(140, 7, $user['first_name'] . ' ' . $user['last_name'], 0, 1, 'R');
$pdf->Cell(40, 7, 'کد ملی:', 0, 0, 'R');
$pdf->Cell(140, 7, $user['national_code'], 0, 1, 'R');
$pdf->Cell(40, 7, 'شماره موبایل:', 0, 0, 'R');
$pdf->Cell(140, 7, $user['mobile'], 0, 1, 'R');

$pdf->Ln(10); // Spacing

// 5. Costs Table
$pdf->SetFont($fontname, 'B', 12);
// Table Header
$pdf->Cell(85, 8, 'شرح خدمات/کالا', 1, 0, 'C');
$pdf->Cell(25, 8, 'تعداد', 1, 0, 'C');
$pdf->Cell(40, 8, 'مبلغ واحد (تومان)', 1, 0, 'C');
$pdf->Cell(40, 8, 'مبلغ کل (تومان)', 1, 1, 'C');

// Table Rows
$pdf->SetFont($fontname, '', 11);
$grandTotal = 0;
foreach ($costs as $cost) {
    $itemTotal = $cost['quantity'] * $cost['price'];
    $grandTotal += $itemTotal;
    $pdf->Cell(85, 7, $cost['item_name'], 1, 0, 'R');
    $pdf->Cell(25, 7, $cost['quantity'], 1, 0, 'C');
    $pdf->Cell(40, 7, number_format($cost['price']), 1, 0, 'C');
    $pdf->Cell(40, 7, number_format($itemTotal), 1, 1, 'C');
}

// Grand Total
$pdf->SetFont($fontname, 'B', 12);
$pdf->Cell(150, 8, 'جمع کل قابل پرداخت:', 1, 0, 'L');
$pdf->Cell(40, 8, number_format($grandTotal) . ' تومان', 1, 1, 'C'); // Changed "Toman" to Farsi

$pdf->Ln(25);

// 6. Signature Area
$pdf->SetFont($fontname, 'B', 12);
$pdf->Cell(95, 15, 'امضای بیمار', 0, 0, 'C');
$pdf->Cell(95, 15, 'مهر و امضای کلینیک', 0, 1, 'C');
$pdf->Line(25, $pdf->GetY(), 90, $pdf->GetY());
$pdf->Line(120, $pdf->GetY(), 185, $pdf->GetY());


// 7. Final PDF Output
// --- CLEAN THE BUFFER ---
// هر خروجی‌ای که در بافر ذخیره شده بود (مثل خطاهای PHP) را پاک می‌کند
ob_end_clean();

$pdf->Output('invoice-' . $user['national_code'] . '.pdf', 'I');
?>