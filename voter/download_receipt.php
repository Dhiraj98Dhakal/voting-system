<?php
require_once '../includes/auth.php';
Auth::requireVoter();

require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$db = Database::getInstance()->getConnection();
$voter_id = $_SESSION['user_id'];

// Get latest vote
$query = "SELECT v.*, c.candidate_name, p.party_name, p.party_logo,
          vt.name as voter_name, vt.voter_id
          FROM votes v
          JOIN candidates c ON v.candidate_id = c.id
          JOIN parties p ON c.party_id = p.id
          JOIN voters vt ON v.voter_id = vt.id
          WHERE v.voter_id = ?
          ORDER BY v.voted_at DESC
          LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $voter_id);
$stmt->execute();
$vote = $stmt->get_result()->fetch_assoc();

if (!$vote) {
    header("Location: dashboard.php");
    exit();
}

// Generate HTML receipt
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Vote Receipt - VoteNepal</title>
    <style>
        body {
            font-family: "DejaVu Sans", sans-serif;
            padding: 40px;
            background: #f8f9fa;
        }
        .receipt {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px dashed #667eea;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 50px;
            color: #667eea;
            margin-bottom: 10px;
        }
        .title {
            font-size: 24px;
            color: #333;
            margin-bottom: 5px;
        }
        .receipt-id {
            background: #f0f4f8;
            padding: 10px;
            border-radius: 10px;
            font-family: monospace;
            font-size: 18px;
            margin: 20px 0;
        }
        .details {
            margin: 30px 0;
        }
        .row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        .label {
            color: #666;
            font-weight: 500;
        }
        .value {
            font-weight: 600;
            color: #333;
        }
        .verified {
            background: #d1fae5;
            color: #065f46;
            padding: 5px 15px;
            border-radius: 20px;
            display: inline-block;
            font-size: 14px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px dashed #667eea;
            font-size: 12px;
            color: #999;
        }
        .qr {
            text-align: center;
            margin: 20px 0;
        }
        .qr-placeholder {
            width: 100px;
            height: 100px;
            background: #f0f4f8;
            margin: 0 auto;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="header">
            <div class="logo">🗳️</div>
            <div class="title">VoteNepal</div>
            <p>Official Vote Receipt</p>
        </div>

        <div style="text-align: center;">
            <span class="verified">✓ Verified Vote</span>
        </div>

        <div class="receipt-id">
            <strong>Receipt No:</strong> VOTE' . str_pad($vote['id'], 10, '0', STR_PAD_LEFT) . '
        </div>

        <div class="qr">
            <div class="qr-placeholder">
                <i class="fas fa-qrcode"></i>
            </div>
            <p style="font-size: 12px; margin-top: 5px;">Scan to verify</p>
        </div>

        <div class="details">
            <div class="row">
                <span class="label">Voter ID:</span>
                <span class="value">' . $vote['voter_id'] . '</span>
            </div>
            <div class="row">
                <span class="label">Voter Name:</span>
                <span class="value">' . htmlspecialchars($vote['voter_name']) . '</span>
            </div>
            <div class="row">
                <span class="label">Election Type:</span>
                <span class="value">' . $vote['election_type'] . '</span>
            </div>
            <div class="row">
                <span class="label">Candidate:</span>
                <span class="value">' . htmlspecialchars($vote['candidate_name']) . '</span>
            </div>
            <div class="row">
                <span class="label">Party:</span>
                <span class="value">' . htmlspecialchars($vote['party_name']) . '</span>
            </div>
            <div class="row">
                <span class="label">Date & Time:</span>
                <span class="value">' . date('d F Y, h:i A', strtotime($vote['voted_at'])) . '</span>
            </div>
        </div>

        <div style="background: #f0f4f8; padding: 15px; border-radius: 10px; margin: 20px 0;">
            <p style="margin: 0; font-size: 14px; text-align: center;">
                This receipt confirms that your vote has been securely recorded in the VoteNepal system.
            </p>
        </div>

        <div class="footer">
            <p>This is a computer generated receipt. Valid for verification purposes.</p>
            <p>© 2024 VoteNepal. All rights reserved.</p>
        </div>
    </div>
</body>
</html>';

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A5', 'portrait');
$dompdf->render();

$dompdf->stream("vote_receipt_" . $vote['voter_id'] . ".pdf", array("Attachment" => true));
exit();
?>