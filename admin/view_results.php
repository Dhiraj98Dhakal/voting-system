<?php
require_once '../includes/auth.php';
Auth::requireAdmin();

$db = Database::getInstance()->getConnection();

// Get FPTP results by constituency
$fptp_results = $db->query("
    SELECT 
        p.name as province_name,
        d.name as district_name,
        cn.constituency_number,
        cand.candidate_name,
        party.party_name,
        party.party_logo,
        COUNT(v.id) as vote_count
    FROM constituencies cn
    JOIN districts d ON cn.district_id = d.id
    JOIN provinces p ON d.province_id = p.id
    LEFT JOIN candidates cand ON cand.constituency_id = cn.id AND cand.election_type = 'FPTP'
    LEFT JOIN parties party ON cand.party_id = party.id
    LEFT JOIN votes v ON v.candidate_id = cand.id
    GROUP BY cn.id, cand.id
    ORDER BY p.name, d.name, cn.constituency_number, vote_count DESC
");

// Get PR results (national level)
$pr_results = $db->query("
    SELECT 
        party.party_name,
        party.party_logo,
        COUNT(v.id) as vote_count,
        (COUNT(v.id) * 100.0 / (SELECT COUNT(*) FROM votes WHERE election_type = 'PR')) as percentage
    FROM parties party
    LEFT JOIN candidates cand ON cand.party_id = party.id AND cand.election_type = 'PR'
    LEFT JOIN votes v ON v.candidate_id = cand.id
    GROUP BY party.id
    ORDER BY vote_count DESC
");

// Overall statistics
$total_voters = $db->query("SELECT COUNT(*) as total FROM voters")->fetch_assoc()['total'];
$total_votes_fptp = $db->query("SELECT COUNT(*) as total FROM votes WHERE election_type = 'FPTP'")->fetch_assoc()['total'];
$total_votes_pr = $db->query("SELECT COUNT(*) as total FROM votes WHERE election_type = 'PR'")->fetch_assoc()['total'];
$turnout = $total_voters > 0 ? round((($total_votes_fptp + $total_votes_pr) / (2 * $total_voters)) * 100, 2) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results - VoteNepal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <!-- Same sidebar -->
        </div>
        
        <div class="admin-content">
            <div class="content-header">
                <h1>Election Results</h1>
            </div>
            
            <!-- Summary Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-details">
                        <h3><?php echo $total_voters; ?></h3>
                        <p>Total Voters</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🗳️</div>
                    <div class="stat-details">
                        <h3><?php echo $total_votes_fptp; ?></h3>
                        <p>FPTP Votes</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📋</div>
                    <div class="stat-details">
                        <h3><?php echo $total_votes_pr; ?></h3>
                        <p>PR Votes</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-details">
                        <h3><?php echo $turnout; ?>%</h3>
                        <p>Voter Turnout</p>
                    </div>
                </div>
            </div>
            
            <!-- PR Results Chart -->
            <div class="data-table-container">
                <h2>Proportional Representation (PR) Results</h2>
                <canvas id="prChart" style="max-height: 400px; margin-bottom: 30px;"></canvas>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Party</th>
                            <th>Votes</th>
                            <th>Percentage</th>
                            <th>Seats</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_seats = 110; // Total PR seats
                        $pr_results->data_seek(0);
                        while($result = $pr_results->fetch_assoc()): 
                            $seats = round(($result['vote_count'] / max($total_votes_pr, 1)) * $total_seats);
                        ?>
                        <tr>
                            <td>
                                <?php if ($result['party_logo']): ?>
                                    <img src="../assets/uploads/parties/<?php echo $result['party_logo']; ?>" 
                                         alt="Logo" style="width: 30px; height: 30px; vertical-align: middle;">
                                <?php endif; ?>
                                <?php echo htmlspecialchars($result['party_name']); ?>
                            </td>
                            <td><strong><?php echo number_format($result['vote_count']); ?></strong></td>
                            <td><?php echo number_format($result['percentage'], 2); ?>%</td>
                            <td><span class="badge badge-success"><?php echo $seats; ?> seats</span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- FPTP Results by Constituency -->
            <div class="data-table-container">
                <h2>First Past The Post (FPTP) Results by Constituency</h2>
                
                <?php 
                $current_constituency = '';
                $fptp_results->data_seek(0);
                while($result = $fptp_results->fetch_assoc()): 
                    $constituency_key = $result['province_name'] . '_' . $result['district_name'] . '_' . $result['constituency_number'];
                    
                    if ($current_constituency != $constituency_key):
                        if ($current_constituency != ''): ?>
                            </tbody>
                            </table>
                        <?php endif; 
                        $current_constituency = $constituency_key;
                ?>
                        <h3 style="margin-top: 30px;">
                            <?php echo htmlspecialchars($result['province_name']); ?> - 
                            <?php echo htmlspecialchars($result['district_name']); ?> - 
                            Constituency <?php echo $result['constituency_number']; ?>
                        </h3>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Candidate</th>
                                    <th>Party</th>
                                    <th>Votes</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                    <?php endif; ?>
                                <tr <?php echo $result['vote_count'] > 0 ? 'class="winner-row"' : ''; ?>>
                                    <td><?php echo htmlspecialchars($result['candidate_name'] ?: 'No candidate'); ?></td>
                                    <td>
                                        <?php if ($result['party_logo']): ?>
                                            <img src="../assets/uploads/parties/<?php echo $result['party_logo']; ?>" 
                                                 alt="Logo" style="width: 20px; height: 20px; vertical-align: middle;">
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($result['party_name'] ?: 'Independent'); ?>
                                    </td>
                                    <td><strong><?php echo number_format($result['vote_count']); ?></strong></td>
                                    <td>
                                        <?php if ($result['vote_count'] > 0): ?>
                                            <span class="badge badge-success">Leading</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                <?php endwhile; ?>
                            </tbody>
                        </table>
            </div>
        </div>
    </div>
    
    <script>
    // PR Results Chart
    <?php 
    $pr_results->data_seek(0);
    $labels = [];
    $data = [];
    while($result = $pr_results->fetch_assoc()): 
        $labels[] = $result['party_name'];
        $data[] = $result['vote_count'];
    endwhile; 
    ?>
    
    new Chart(document.getElementById('prChart'), {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                data: <?php echo json_encode($data); ?>,
                backgroundColor: [
                    '#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
                    '#ec4899', '#06b6d4', '#84cc16', '#d946ef', '#f97316'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });
    </script>
    
    <style>
    .winner-row {
        background-color: #d1fae5;
    }
    .winner-row td:first-child {
        border-left: 4px solid var(--success-color);
    }
    </style>
</body>
</html>