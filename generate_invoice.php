<?php
// --- START OUTPUT BUFFERING ---
// این دستور تمام خروجی های بعدی (حتی خطاها) را در حافظه موقت نگه می‌دارد
ob_start();

// نمایش تمام خطاها برای دیباگ آسان‌تر
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Include TCPDF library and configuration file
require_once('tcpdf/tcpdf.php');
require_once('config.php');

// --- Database Operations ---
// بررسی و دریافت شناسه کاربر از URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    ob_end_clean();
    die("Error: Invalid user ID.");
}
$userId = (int)$_GET['id'];

// دریافت اطلاعات کارمند/بیمار از دیتابیس
$stmt_employee = $conn->prepare("SELECT * FROM employees WHERE id = ?");
$stmt_employee->bind_param("i", $userId);
$stmt_employee->execute();
$employeeResult = $stmt_employee->get_result();
$employee = $employeeResult->fetch_assoc();
if (!$employee) {
    ob_end_clean();
    die("Error: No employee found with this ID.");
}

// دریافت لیست هزینه‌ها از دیتابیس
$stmt_costs = $conn->prepare("SELECT * FROM employee_costs WHERE employee_id = ?");
$stmt_costs->bind_param("i", $userId);
$stmt_costs->execute();
$costsResult = $stmt_costs->get_result();
$costs = $costsResult->fetch_all(MYSQLI_ASSOC);
$conn->close();


// 2. Custom PDF Class with a new attractive design
class MYPDF extends TCPDF {
    private $clinicName = 'کلینیک زیبایی بهار';
    private $clinicAddress = 'تهران، خیابان ولیعصر، بالاتر از پارک ساعی، پلاک ۱۲۳۴';
    private $PDF_THEME_COLOR = array(44, 130, 194); // A nice blue color

    // --- Redesigned Header ---
    public function Header() {
        // Logo
        $image_file = __DIR__ . '/img/logo.png';
        if (file_exists($image_file)) {
            $this->Image($image_file, 15, 10, 30, '', 'PNG', '', 'T', false, 300, 'L', false, false, 0, false, false, false);
        }
        
        // Set Farsi Font for Header
        $fontPath = __DIR__ . '/fonts/Vazirmatn-Medium.ttf';
        $fontname = TCPDF_FONTS::addTTFfont($fontPath, 'TrueTypeUnicode', '', 32);
        $this->SetFont($fontname, '', 9);
        $this->setRTL(true);

        // Clinic Information on the right
        $this->SetX(110); // Move to the right side
        $this->SetFont($fontname, 'B', 13);
        $this->Cell(90, 7, $this->clinicName, 0, 1, 'R');
        $this->SetFont($fontname, '', 9);
        $this->MultiCell(90, 5, $this->clinicAddress, 0, 'R', 0, 1, '', '', true);
        $this->Cell(90, 5, 'تلفن: ۰۲۱-۸۸۷۷۶۶۵۵', 0, 1, 'R');
        $this->Cell(90, 5, 'وبسایت: www.springclinic.com', 0, 1, 'R');
        
        // Header bottom border
        $this->SetY($this->GetY() + 5);
        $this->SetLineStyle(array('width' => 0.5, 'color' => $this->PDF_THEME_COLOR));
        $this->Line(15, $this->GetY(), 195, $this->GetY());
    }

    // --- Redesigned Footer ---
    public function Footer() {
        $this->SetY(-20); // Position at 2cm from bottom

        // Footer top border
        $this->SetLineStyle(array('width' => 0.5, 'color' => $this->PDF_THEME_COLOR));
        $this->Line(15, $this->GetY(), 195, $this->GetY());

        // Set Farsi Font
        $fontPath = __DIR__ . '/fonts/Vazirmatn-Medium.ttf';
        $fontname = TCPDF_FONTS::addTTFfont($fontPath, 'TrueTypeUnicode', '', 32);
        $this->SetFont($fontname, '', 8);
        $this->setRTL(true);
        $this->SetY($this->GetY() + 2);

        // Footer Content
        $this->Cell(0, 10, 'از انتخاب شما سپاسگزاریم | ' . $this->clinicName, 0, false, 'C', 0, '', 0, false, 'T', 'M');
        $this->Ln(4);
        $this->Cell(0, 10, 'صفحه ' . $this->getAliasNumPage() . ' از ' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
    
    // --- Helper function for styled table ---
    public function CreateStyledTable($header, $data) {
        // Header
        $this->SetFillColor($this->PDF_THEME_COLOR[0], $this->PDF_THEME_COLOR[1], $this->PDF_THEME_COLOR[2]);
        $this->SetTextColor(255);
        $this->SetDrawColor(255, 255, 255);
        $this->SetFont('', 'B', 11);
        $this->SetLineWidth(0);
        
        $w = array(90, 25, 40, 35); // Column widths
        for($i = 0; $i < count($header); ++$i) {
            $this->Cell($w[$i], 9, $header[$i], 1, 0, 'C', 1);
        }
        $this->Ln();

        // Data rows with alternating colors
        $this->SetFillColor(240, 240, 240);
        $this->SetTextColor(0);
        $this->SetFont('');
        $fill = 0;
        $grandTotal = 0;
        
        foreach($data as $row) {
            $itemTotal = $row['quantity'] * $row['price'];
            $grandTotal += $itemTotal;
            
            $this->Cell($w[0], 8, $row['item_name'], 'LR', 0, 'R', $fill);
            $this->Cell($w[1], 8, $row['quantity'], 'LR', 0, 'C', $fill);
            $this->Cell($w[2], 8, number_format($row['price']), 'LR', 0, 'C', $fill);
            $this->Cell($w[3], 8, number_format($itemTotal), 'LR', 1, 'C', $fill);
            $fill = !$fill;
        }
        $this->Cell(array_sum($w), 0, '', 'T'); // Bottom line of the table
        return $grandTotal;
    }

    /**
     * A public "getter" method to access the private theme color property.
     * @return array The RGB color array.
     */
    public function getThemeColor() {
        return $this->PDF_THEME_COLOR;
    }
}


// 3. Create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('کلینیک زیبایی بهار');
$pdf->SetTitle('فاکتور بیمار - ' . $employee['first_name'] . ' ' . $employee['last_name']);
$pdf->setPrintHeader(true);
$pdf->setPrintFooter(true);
$pdf->SetMargins(15, 45, 15); // Left, Top, Right
$pdf->SetAutoPageBreak(TRUE, 30); // Margin from bottom

// **Add and set the Farsi font**
$fontPath = __DIR__ . '/fonts/Vazirmatn-Medium.ttf';
$fontname = TCPDF_FONTS::addTTFfont($fontPath, 'TrueTypeUnicode', '', 32);

$pdf->SetFont($fontname, '', 10, '', true);
$pdf->setRTL(true);
$pdf->AddPage();

// 4. Invoice and Patient Information Blocks
$pdf->SetFont($fontname, 'B', 12);
$pdf->Cell(90, 7, 'مشخصات بیمار', 0, 0, 'R');
$pdf->Cell(90, 7, 'اطلاعات فاکتور', 0, 1, 'R');

$pdf->SetFont($fontname, '', 10);
$pdf->SetFillColor(245, 245, 245);
$pdf->SetDrawColor(220, 220, 220);

// Patient Info Box (Right)
$y_start = $pdf->GetY();
$patient_info = '<b>نام:</b> ' . $employee['first_name'] . ' ' . $employee['last_name'] . '<br>';
$patient_info .= '<b>کد ملی:</b> ' . $employee['national_code'] . '<br>';
$patient_info .= '<b>موبایل:</b> ' . $employee['mobile'];
$pdf->writeHTMLCell(90, 0, '', $y_start, $patient_info, 1, 1, true, true, 'R', true);

// Invoice Info Box (Left)
$invoice_info = '<b>شماره فاکتور:</b> INV-' . str_pad($employee['id'], 5, '0', STR_PAD_LEFT) . '<br>';
$invoice_info .= '<b>تاریخ صدور:</b> ' . date('Y/m/d') . '<br>';
$invoice_info .= '<b>تاریخ سررسید:</b> ' . date('Y/m/d');
$pdf->writeHTMLCell(90, 0, 15, $y_start, $invoice_info, 1, 0, true, true, 'R', true);

$pdf->Ln(20);

// 5. Costs Table
$header = array('شرح خدمات/کالا', 'تعداد', 'مبلغ واحد', 'مبلغ کل');
$grandTotal = $pdf->CreateStyledTable($header, $costs);

$pdf->Ln(5);

// 6. Grand Total Section (Corrected with LTR temporary switch)
// --- راه حل: خروج موقت از حالت راست‌به‌چپ برای تراز بندی دقیق ---
$pdf->setRTL(false);

$pdf->SetFont($fontname, '', 11);
$total_box_width = 85; 
$start_x = 195 - $total_box_width; // محاسبه نقطه شروع از سمت چپ صفحه

// Subtotal
$pdf->SetX($start_x);
$pdf->Cell($total_box_width - 40, 8, ':جمع جزء', 0, 0, 'L');
$pdf->Cell(40, 8, number_format($grandTotal), 0, 1, 'R');

// Tax (Example)
$pdf->SetX($start_x);
$tax = $grandTotal * 0.00; // مثال: مالیات صفر در نظر گرفته شده
$pdf->Cell($total_box_width - 40, 8, ':مالیات (۰٪)', 0, 0, 'L');
$pdf->Cell(40, 8, number_format($tax), 0, 1, 'R');
$pdf->Ln(2);

// Grand Total (Styled)
// خط جداکننده
$pdf->SetX($start_x);
$pdf->SetLineWidth(0.2);
$pdf->Cell($total_box_width, 1, '', 'T', 1);
$pdf->Ln(1);

// دریافت رنگ اصلی از متد کلاس
$themeColor = $pdf->getThemeColor(); 
$pdf->SetFillColor($themeColor[0], $themeColor[1], $themeColor[2]);
$pdf->SetTextColor(255);
$pdf->SetFont('', 'B');

// رسم کادر مبلغ نهایی
$pdf->SetX($start_x);
// استفاده از MultiCell برای کنترل بهتر متن و جلوگیری از به هم ریختگی
$pdf->MultiCell($total_box_width - 40, 10, 'مبلغ نهایی (تومان)', 0, 'C', true, 0, '', '', true, 0, false, true, 10, 'M');
$pdf->SetX($start_x + $total_box_width - 40); // جابجایی دقیق به ستون دوم
$pdf->MultiCell(40, 10, number_format($grandTotal + $tax), 0, 'C', true, 1, '', '', true, 0, false, true, 10, 'M');

// --- بازگشت به حالت راست‌به‌چپ برای ادامه سند ---
$pdf->setRTL(true);

$pdf->SetTextColor(0);
$pdf->Ln(25);

// 7. Signature Area
$pdf->SetFont($fontname, 'B', 11);
$pdf->SetLineStyle(array('width' => 0.2, 'dash' => '2,2', 'color' => array(150, 150, 150)));
$pdf->Cell(95, 15, 'امضای بیمار', 0, 0, 'C');
$pdf->Cell(95, 15, 'مهر و امضای کلینیک', 0, 1, 'C');
$y_line = $pdf->GetY();
$pdf->Line(25, $y_line, 90, $y_line);
$pdf->Line(120, $y_line, 185, $y_line);

// 8. Final PDF Output
ob_end_clean(); // Clean the buffer before output
$pdf->Output('invoice-' . $employee['national_code'] . '.pdf', 'I');
?>