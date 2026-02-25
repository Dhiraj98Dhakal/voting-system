<?php
require_once '../includes/auth.php';
Auth::requireVoter();

// Load Composer autoload
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$db = Database::getInstance()->getConnection();
$voter_id = $_SESSION['user_id'];

// Font paths - ABSOLUTE PATHS
define('BASE_PATH', 'C:/wamp64/www/voting-system/');
define('FONT_PATH', BASE_PATH . 'fonts/');
define('NOTO_SANS_REGULAR', FONT_PATH . 'NotoSansDevanagari-Regular.ttf');
define('NOTO_SANS_BOLD', FONT_PATH . 'NotoSansDevanagari-Bold.ttf');

// Fetch voter details
$query = "SELECT v.*, p.name as province_name, p.name_nepali as province_name_np,
          d.name as district_name, d.name_nepali as district_name_np,
          c.constituency_number,
          (SELECT COUNT(*) FROM votes WHERE voter_id = v.id AND election_type = 'FPTP') as fptp_voted,
          (SELECT COUNT(*) FROM votes WHERE voter_id = v.id AND election_type = 'PR') as pr_voted
          FROM voters v 
          LEFT JOIN provinces p ON v.province_id = p.id 
          LEFT JOIN districts d ON v.district_id = d.id 
          LEFT JOIN constituencies c ON v.constituency_id = c.id 
          WHERE v.id = ?";

$stmt = $db->prepare($query);
$stmt->bind_param("i", $voter_id);
$stmt->execute();
$voter = $stmt->get_result()->fetch_assoc();

// Get voting history
$history_query = "SELECT v.*, c.candidate_name, p.party_name,
                  v.election_type, v.voted_at
                  FROM votes v
                  JOIN candidates c ON v.candidate_id = c.id
                  JOIN parties p ON c.party_id = p.id
                  WHERE v.voter_id = ?
                  ORDER BY v.voted_at DESC";
$stmt = $db->prepare($history_query);
$stmt->bind_param("i", $voter_id);
$stmt->execute();
$history = $stmt->get_result();

// Function to convert image to base64
function imageToBase64($path) {
    if (file_exists($path)) {
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
    return '';
}

// Function to register Google Noto Sans Devanagari font
function registerGoogleFont($dompdf) {
    $fontMetrics = $dompdf->getFontMetrics();
    $registered = false;
    
    // Check if fonts exist
    if (!file_exists(NOTO_SANS_REGULAR)) {
        error_log("Font not found: " . NOTO_SANS_REGULAR);
        return false;
    }
    
    // Register Regular font
    try {
        $fontMetrics->registerFont(
            ['family' => 'Noto Sans Devanagari', 'style' => 'normal', 'weight' => 'normal'],
            NOTO_SANS_REGULAR
        );
        $registered = true;
        error_log("Noto Sans Devanagari Regular registered");
    } catch (Exception $e) {
        error_log("Failed to register Regular font: " . $e->getMessage());
    }
    
    // Register Bold font (if exists)
    if (file_exists(NOTO_SANS_BOLD)) {
        try {
            $fontMetrics->registerFont(
                ['family' => 'Noto Sans Devanagari', 'style' => 'normal', 'weight' => 'bold'],
                NOTO_SANS_BOLD
            );
            error_log("Noto Sans Devanagari Bold registered");
        } catch (Exception $e) {
            error_log("Failed to register Bold font: " . $e->getMessage());
        }
    }
    
    return $registered;
}

// Create PDF options
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'Noto Sans Devanagari');
$options->set('isFontSubsettingEnabled', true);
$options->set('defaultMediaType', 'print');
$options->set('isPhpEnabled', true);
$options->set('isJavascriptEnabled', false);
$options->set('dpi', 150);
$options->set('fontHeightRatio', 1.2);
$options->set('chroot', BASE_PATH);

$dompdf = new Dompdf($options);

// Register Google Font
$fontRegistered = registerGoogleFont($dompdf);

// Fallback font if registration failed
$fontFamily = $fontRegistered ? 'Noto Sans Devanagari' : 'DejaVu Sans';

// Generate HTML for PDF
$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Voter Information - VoteNepal</title>
    <style>
        @page {
            margin: 1.5cm;
            size: A4;
        }
        
        body {
            font-family: "' . $fontFamily . '", "DejaVu Sans", Arial, sans-serif;
            line-height: 1.5;
            color: #333;
            font-size: 11pt;
            margin: 0;
            padding: 0;
        }
        
        .header {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px 10px 0 0;
            margin-bottom: 20px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 24pt;
        }
        
        .header p {
            margin: 5px 0;
            font-size: 12pt;
            opacity: 0.9;
        }
        
        .voter-id {
            font-size: 20pt;
            font-weight: bold;
            background: rgba(255,255,255,0.2);
            display: inline-block;
            padding: 8px 25px;
            border-radius: 50px;
            margin: 15px 0;
            letter-spacing: 1px;
            font-family: monospace;
        }
        
        .photo-section {
            text-align: center;
            margin: 20px 0;
        }
        
        .photo {
            max-width: 120px;
            max-height: 120px;
            border-radius: 50%;
            border: 4px solid #667eea;
            object-fit: cover;
        }
        
        .grid-3col {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
            page-break-inside: avoid;
            margin-bottom: 15px;
        }
        
        .section-title {
            color: #667eea;
            font-weight: 600;
            font-size: 14pt;
            margin: 0 0 15px 0;
            padding-bottom: 5px;
            border-bottom: 2px solid #667eea;
        }
        
        .info-item {
            margin-bottom: 8px;
            break-inside: avoid;
        }
        
        .label {
            font-weight: 600;
            color: #666;
            font-size: 9pt;
            text-transform: uppercase;
            display: block;
        }
        
        .value {
            font-size: 10pt;
            color: #333;
            font-weight: 500;
            word-break: break-word;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 9pt;
            font-weight: 600;
        }
        
        .verified {
            background: #d1fae5;
            color: #065f46;
        }
        
        .pending {
            background: #fee2e2;
            color: #991b1b;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 9pt;
        }
        
        th {
            background: #667eea;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: 600;
        }
        
        td {
            padding: 6px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .footer {
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            font-size: 8pt;
            color: #666;
            border-top: 2px solid #667eea;
        }
        
        .watermark {
            position: fixed;
            bottom: 20px;
            right: 20px;
            opacity: 0.1;
            font-size: 40pt;
            color: #667eea;
            z-index: -1;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .header {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            th {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="watermark">🗳️ VoteNepal</div>

    <div class="header">
        <h1>🗳️ VoteNepal</h1>
        <p>Voter Information | मतदाता जानकारी</p>
        <div class="voter-id">' . $voter['voter_id'] . '</div>
    </div>';

// Profile Photo
if ($voter['profile_photo'] && file_exists('../assets/uploads/voters/' . $voter['profile_photo'])) {
    $photo_base64 = imageToBase64('../assets/uploads/voters/' . $voter['profile_photo']);
    $html .= '<div class="photo-section">
                <img src="' . $photo_base64 . '" class="photo" alt="Profile Photo">
              </div>';
}

// Personal Information
$html .= '
    <div class="section">
        <h2 class="section-title">📋 Personal Information / व्यक्तिगत जानकारी</h2>
        <div class="grid-3col">
            <div class="info-item">
                <span class="label">Name / नाम</span>
                <span class="value">' . htmlspecialchars($voter['name']) . '</span>
            </div>
            <div class="info-item">
                <span class="label">DOB / जन्म मिति</span>
                <span class="value">' . date('d M Y', strtotime($voter['dob'])) . '</span>
            </div>
            <div class="info-item">
                <span class="label">Age / उमेर</span>
                <span class="value">' . calculateAge($voter['dob']) . ' years</span>
            </div>
            <div class="info-item">
                <span class="label">Citizenship / नागरिकता</span>
                <span class="value">' . htmlspecialchars($voter['citizenship_number']) . '</span>
            </div>
            <div class="info-item">
                <span class="label">Father / बुबा</span>
                <span class="value">' . htmlspecialchars($voter['father_name']) . '</span>
            </div>
            <div class="info-item">
                <span class="label">Mother / आमा</span>
                <span class="value">' . htmlspecialchars($voter['mother_name']) . '</span>
            </div>
        </div>
    </div>';

// Contact & Location
$html .= '
    <div class="section">
        <h2 class="section-title">📞 Contact & Location / सम्पर्क र स्थान</h2>
        <div class="grid-3col">
            <div class="info-item">
                <span class="label">Phone / फोन</span>
                <span class="value">' . htmlspecialchars($voter['phone']) . '</span>
            </div>
            <div class="info-item">
                <span class="label">Email / ईमेल</span>
                <span class="value">' . htmlspecialchars($voter['email']) . '</span>
            </div>
            <div class="info-item">
                <span class="label">Province / प्रदेश</span>
                <span class="value">' . htmlspecialchars($voter['province_name']) . '</span>
            </div>
            <div class="info-item">
                <span class="label">District / जिल्ला</span>
                <span class="value">' . htmlspecialchars($voter['district_name']) . '</span>
            </div>
            <div class="info-item">
                <span class="label">Constituency / क्षेत्र</span>
                <span class="value">' . $voter['constituency_number'] . '</span>
            </div>
            <div class="info-item">
                <span class="label">Address / ठेगाना</span>
                <span class="value">' . htmlspecialchars($voter['address']) . '</span>
            </div>
        </div>
    </div>';

// Status
$status_class = $voter['is_verified'] ? 'verified' : 'pending';
$status_text = $voter['is_verified'] ? '✓ Verified / प्रमाणित' : '⏳ Pending / प्रतीक्षा';
$fptp_text = $voter['fptp_voted'] ? '✓ Voted / मतदान गरियो' : '✗ Not Voted / मतदान गरिएन';
$pr_text = $voter['pr_voted'] ? '✓ Voted / मतदान गरियो' : '✗ Not Voted / मतदान गरिएन';

$html .= '
    <div class="section">
        <h2 class="section-title">✅ Status / स्थिति</h2>
        <div class="grid-3col">
            <div class="info-item">
                <span class="label">Verification / प्रमाणीकरण</span>
                <span class="value"><span class="status-badge ' . $status_class . '">' . $status_text . '</span></span>
            </div>
            <div class="info-item">
                <span class="label">FPTP Vote / प्रत्यक्ष</span>
                <span class="value">' . $fptp_text . '</span>
            </div>
            <div class="info-item">
                <span class="label">PR Vote / समानुपातिक</span>
                <span class="value">' . $pr_text . '</span>
            </div>
        </div>
    </div>';

// Voting History
if ($history->num_rows > 0) {
    $html .= '
    <div class="section">
        <h2 class="section-title">📜 Voting History / मतदान इतिहास</h2>
        <table>
            <thead>
                <tr>
                    <th>Date / मिति</th>
                    <th>Type / प्रकार</th>
                    <th>Candidate / उम्मेदवार</th>
                    <th>Party / दल</th>
                </tr>
            </thead>
            <tbody>';
    
    while($vote = $history->fetch_assoc()) {
        $html .= '
                <tr>
                    <td>' . date('d M Y', strtotime($vote['voted_at'])) . '</td>
                    <td>' . $vote['election_type'] . '</td>
                    <td>' . htmlspecialchars($vote['candidate_name']) . '</td>
                    <td>' . htmlspecialchars($vote['party_name']) . '</td>
                </tr>';
    }
    
    $html .= '
            </tbody>
        </table>
    </div>';
}

// Footer
$html .= '
    <div class="footer">
        <p>Generated on: ' . date('d F Y, h:i A') . '</p>
        <p>© ' . date('Y') . ' VoteNepal - Official Voter Document</p>
    </div>
</body>
</html>';

// Load HTML and generate PDF
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output PDF
$dompdf->stream("voter_info_" . $voter['voter_id'] . ".pdf", array("Attachment" => true));
exit();
?>