<?php
require_once '../includes/session.php';
require_once '../includes/functions.php';

$type = isset($_GET['type']) ? $_GET['type'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote Success - VoteNepal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .success-container {
            background: white;
            border-radius: 30px;
            padding: 60px;
            max-width: 600px;
            text-align: center;
            box-shadow: 0 30px 70px rgba(0,0,0,0.3);
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success-icon {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.7);
            }
            70% {
                box-shadow: 0 0 0 20px rgba(76, 175, 80, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(76, 175, 80, 0);
            }
        }

        .success-icon i {
            font-size: 60px;
            color: white;
        }

        h1 {
            font-size: 32px;
            color: var(--dark);
            margin-bottom: 15px;
        }

        .vote-type {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 8px 25px;
            border-radius: 50px;
            display: inline-block;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .message {
            color: var(--gray);
            margin-bottom: 30px;
            line-height: 1.8;
            font-size: 16px;
        }

        .receipt {
            background: var(--light);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            text-align: left;
        }

        .receipt h3 {
            margin-bottom: 20px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .receipt-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px dashed var(--border-color);
        }

        .receipt-item:last-child {
            border-bottom: none;
        }

        .receipt-label {
            color: var(--gray);
            font-weight: 500;
        }

        .receipt-value {
            font-weight: 600;
            color: var(--dark);
        }

        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }

        .share-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }

        .share-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray);
            text-decoration: none;
            transition: all 0.3s;
        }

        .share-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background: var(--primary);
            opacity: 0.7;
            animation: fall 5s linear infinite;
        }

        @keyframes fall {
            to {
                transform: translateY(100vh) rotate(360deg);
            }
        }

        @media (max-width: 480px) {
            .success-container {
                padding: 40px 20px;
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Confetti Animation -->
    <div id="confetti"></div>

    <div class="success-container">
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>

        <h1>Vote Recorded Successfully!</h1>
        <div class="vote-type">
            <?php echo $type; ?> Vote • <?php echo $type == 'FPTP' ? 'प्रत्यक्ष निर्वाचन' : 'समानुपातिक निर्वाचन'; ?>
        </div>

        <p class="message">
            Thank you for participating in Nepal's democratic process.<br>
            Your vote has been securely recorded and encrypted.
        </p>

        <div class="receipt">
            <h3>
                <i class="fas fa-receipt"></i>
                Vote Receipt
            </h3>
            <div class="receipt-item">
                <span class="receipt-label">Transaction ID</span>
                <span class="receipt-value">VOTE<?php echo time(); ?></span>
            </div>
            <div class="receipt-item">
                <span class="receipt-label">Date & Time</span>
                <span class="receipt-value"><?php echo date('Y-m-d H:i:s'); ?></span>
            </div>
            <div class="receipt-item">
                <span class="receipt-label">Election Type</span>
                <span class="receipt-value"><?php echo $type; ?></span>
            </div>
            <div class="receipt-item">
                <span class="receipt-label">Status</span>
                <span class="receipt-value" style="color: #4caf50;">✓ Confirmed</span>
            </div>
        </div>

        <div class="actions">
            <?php if ($type == 'FPTP'): ?>
                <a href="vote_pr.php" class="btn btn-primary">
                    <i class="fas fa-arrow-right"></i>
                    Continue to PR Vote
                </a>
            <?php else: ?>
                <a href="dashboard.php" class="btn btn-primary">
                    <i class="fas fa-home"></i>
                    Go to Dashboard
                </a>
            <?php endif; ?>
            
            <a href="download_receipt.php" class="btn btn-outline">
                <i class="fas fa-download"></i>
                Download Receipt
            </a>
        </div>

        <div class="share-buttons">
            <a href="#" class="share-btn"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="share-btn"><i class="fab fa-twitter"></i></a>
            <a href="#" class="share-btn"><i class="fab fa-whatsapp"></i></a>
            <a href="#" class="share-btn"><i class="fas fa-envelope"></i></a>
        </div>
    </div>

    <script>
        // Confetti effect
        function createConfetti() {
            for (let i = 0; i < 50; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.animationDuration = Math.random() * 3 + 2 + 's';
                confetti.style.background = `hsl(${Math.random() * 360}, 70%, 50%)`;
                confetti.style.width = Math.random() * 10 + 5 + 'px';
                confetti.style.height = confetti.style.width;
                document.body.appendChild(confetti);
                
                // Remove after animation
                setTimeout(() => {
                    confetti.remove();
                }, 5000);
            }
        }

        // Create confetti on load
        createConfetti();
        
        // Create more confetti every 3 seconds
        setInterval(createConfetti, 3000);
    </script>
</body>
</html>