<?php
require_once '../includes/auth.php';
Auth::requireAdmin();

$db = Database::getInstance()->getConnection();

// Get detailed live results
$results = $db->query("
    SELECT 
        p.name as province_name,
        d.name as district_name,
        c.constituency_number,
        cand.candidate_name,
        party.party_name,
        party.party_logo,
        cand.election_type,
        COUNT(v.id) as vote_count,
        (SELECT COUNT(*) FROM votes WHERE candidate_id = cand.id) as total_votes
    FROM constituencies c
    JOIN districts d ON c.district_id = d.id
    JOIN provinces p ON d.province_id = p.id
    LEFT JOIN candidates cand ON cand.constituency_id = c.id
    LEFT JOIN parties party ON cand.party_id = party.id
    LEFT JOIN votes v ON v.candidate_id = cand.id
    GROUP BY c.id, cand.id
    ORDER BY p.id, d.id, c.constituency_number, vote_count DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Results - VoteNepal Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/live-count.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .live-results-container {
            padding: 20px;
        }
        
        .refresh-bar {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .auto-refresh {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .results-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .tab-btn {
            padding: 10px 20px;
            border: none;
            background: white;
            cursor: pointer;
            border-radius: 5px;
            font-weight: 500;
        }
        
        .tab-btn.active {
            background: var(--primary-color);
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .province-group {
            margin-bottom: 30px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .province-header {
            background: var(--primary-color);
            color: white;
            padding: 15px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .province-content {
            padding: 20px;
            display: none;
        }
        
        .province-content.show {
            display: block;
        }
        
        .constituency-card {
            background: var(--light-color);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .candidate-row {
            display: grid;
            grid-template-columns: 1fr 150px 80px;
            align-items: center;
            padding: 8px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .candidate-row.winner {
            background: #d1fae5;
            border-radius: 5px;
        }
        
        .vote-bar {
            height: 20px;
            background: #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 5px;
        }
        
        .vote-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            transition: width 0.5s ease;
        }
        
        .live-badge {
            background: var(--danger-color);
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        .export-buttons {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <!-- Sidebar content -->
            <div class="sidebar-header">
                <h2>🗳️ VoteNepal</h2>
                <p>Admin Panel</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="manage_voters.php">👥 Voters</a></li>
                <li><a href="manage_parties.php">🎯 Parties</a></li>
                <li><a href="manage_candidates.php">👤 Candidates</a></li>
                <li><a href="manage_provinces.php">🗺️ Provinces</a></li>
                <li><a href="manage_districts.php">🏘️ Districts</a></li>
                <li><a href="manage_constituencies.php">📍 Constituencies</a></li>
                <li class="active"><a href="live_results.php">📊 Live Results</a></li>
                <li><a href="logout.php">🚪 Logout</a></li>
            </ul>
        </div>
        
        <div class="admin-content">
            <div class="live-results-container">
                <div class="content-header">
                    <h1>📊 Live Election Results</h1>
                    <div class="export-buttons">
                        <button class="btn btn-outline" onclick="exportToPDF()">📥 Export PDF</button>
                        <button class="btn btn-outline" onclick="exportToExcel()">📊 Export Excel</button>
                    </div>
                </div>
                
                <div class="refresh-bar">
                    <div class="auto-refresh">
                        <span class="live-badge">🔴 LIVE</span>
                        <span>Auto-refresh every <span id="countdown">30</span>s</span>
                    </div>
                    <button class="btn btn-primary" onclick="refreshData()">🔄 Refresh Now</button>
                </div>
                
                <div class="results-tabs">
                    <button class="tab-btn active" onclick="switchTab('overview')">📈 Overview</button>
                    <button class="tab-btn" onclick="switchTab('fptp')">🏛️ FPTP Results</button>
                    <button class="tab-btn" onclick="switchTab('pr')">📋 PR Results</button>
                    <button class="tab-btn" onclick="switchTab('constituency')">📍 By Constituency</button>
                </div>
                
                <!-- Overview Tab -->
                <div id="overview-tab" class="tab-content active">
                    <!-- Live stats cards same as public view but more detailed -->
                    <div class="live-stats-grid">
                        <!-- Add detailed stats -->
                    </div>
                </div>
                
                <!-- FPTP Tab -->
                <div id="fptp-tab" class="tab-content">
                    <?php
                    $current_province = '';
                    $results->data_seek(0);
                    while($row = $results->fetch_assoc()): 
                        if($row['election_type'] == 'FPTP'):
                            if($current_province != $row['province_name']):
                                if($current_province != ''): ?>
                                    </div></div>
                                <?php endif; 
                                $current_province = $row['province_name'];
                            ?>
                            <div class="province-group">
                                <div class="province-header" onclick="toggleProvince(this)">
                                    <span>🏛️ <?php echo $row['province_name']; ?></span>
                                    <span>▼</span>
                                </div>
                                <div class="province-content show">
                            <?php endif; ?>
                            
                            <div class="constituency-card">
                                <h4><?php echo $row['district_name']; ?> - Constituency <?php echo $row['constituency_number']; ?></h4>
                                <div class="candidate-row">
                                    <span><?php echo $row['candidate_name']; ?> (<?php echo $row['party_name']; ?>)</span>
                                    <span class="vote-count"><?php echo number_format($row['vote_count']); ?></span>
                                    <span class="percentage">
                                        <?php 
                                        $total = $row['total_votes'] ?: 1;
                                        echo round(($row['vote_count'] / $total) * 100, 1); 
                                        ?>%
                                    </span>
                                </div>
                                <div class="vote-bar">
                                    <div class="vote-fill" style="width: <?php echo ($row['vote_count'] / $total) * 100; ?>%"></div>
                                </div>
                            </div>
                    <?php 
                        endif;
                    endwhile; ?>
                </div>
                
                <!-- PR Tab -->
                <div id="pr-tab" class="tab-content">
                    <!-- PR results with proportional representation -->
                </div>
                
                <!-- By Constituency Tab -->
                <div id="constituency-tab" class="tab-content">
                    <!-- Detailed constituency results -->
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let countdown = 30;
        let refreshInterval;
        
        function startCountdown() {
            refreshInterval = setInterval(() => {
                countdown--;
                document.getElementById('countdown').textContent = countdown;
                
                if (countdown <= 0) {
                    refreshData();
                    countdown = 30;
                }
            }, 1000);
        }
        
        function refreshData() {
            location.reload();
        }
        
        function switchTab(tab) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            
            document.getElementById(tab + '-tab').classList.add('active');
            event.target.classList.add('active');
        }
        
        function toggleProvince(header) {
            const content = header.nextElementSibling;
            content.classList.toggle('show');
            header.querySelector('span:last-child').textContent = content.classList.contains('show') ? '▼' : '▶';
        }
        
        function exportToPDF() {
            window.print();
        }
        
        function exportToExcel() {
            window.location.href = 'export_results.php?type=excel';
        }
        
        startCountdown();
    </script>
</body>
</html>