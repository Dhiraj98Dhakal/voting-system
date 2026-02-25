<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php'; 

$db = Database::getInstance()->getConnection();

// Get live vote counts (cached for performance)
$cache_file = 'cache/dashboard_stats.json';
$cache_time = 30;

if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_time)) {
    $stats = json_decode(file_get_contents($cache_file), true);
} else {
    // Fixed query without recent votes (privacy fix)
    $stats_query = "
        SELECT 
            (SELECT COUNT(*) FROM votes) as total_votes,
            (SELECT COUNT(*) FROM votes WHERE election_type = 'FPTP') as fptp_votes,
            (SELECT COUNT(*) FROM votes WHERE election_type = 'PR') as pr_votes,
            (SELECT COUNT(*) FROM voters WHERE is_verified = 1) as total_voters,
            
            -- Top parties subquery
            (SELECT 
                CONCAT(
                    '[',
                    GROUP_CONCAT(
                        JSON_OBJECT(
                            'party_name', party_data.party_name,
                            'party_logo', party_data.party_logo,
                            'vote_count', party_data.vote_count
                        )
                    ),
                    ']'
                )
                FROM (
                    SELECT 
                        parties.party_name,
                        parties.party_logo,
                        COUNT(votes.id) as vote_count
                    FROM parties
                    LEFT JOIN candidates ON candidates.party_id = parties.id
                    LEFT JOIN votes ON votes.candidate_id = candidates.id
                    GROUP BY parties.id
                    ORDER BY vote_count DESC
                    LIMIT 5
                ) as party_data
            ) as top_parties
    ";
    
    $result = $db->query($stats_query);
    
    if (!$result) {
        die("Query Error: " . $db->error);
    }
    
    $row = $result->fetch_assoc();
    
    $total_votes = $row['total_votes'] ?? 0;
    $fptp_votes = $row['fptp_votes'] ?? 0;
    $pr_votes = $row['pr_votes'] ?? 0;
    $total_voters = $row['total_voters'] ?? 0;
    
    // Calculate turnout
    $expected_votes = $total_voters * 2;
    $turnout = $expected_votes > 0 ? round(($total_votes / $expected_votes) * 100, 1) : 0;
    
    // Parse top parties JSON
    $top_parties = [];
    if (!empty($row['top_parties'])) {
        $top_parties = json_decode($row['top_parties'], true);
        if (!is_array($top_parties)) {
            $top_parties = [];
        }
    }
    
    $stats = [
        'total_votes' => (int)$total_votes,
        'fptp_votes' => (int)$fptp_votes,
        'pr_votes' => (int)$pr_votes,
        'total_voters' => (int)$total_voters,
        'turnout' => $turnout,
        'top_parties' => $top_parties,
        'last_updated' => time()
    ];
    
    // Save to cache
    if (!is_dir('cache')) mkdir('cache', 0777);
    file_put_contents($cache_file, json_encode($stats));
}

// Get user data for dropdown if logged in
$userData = null;
if (isLoggedIn()) {
    if (isAdmin()) {
        $userQuery = "SELECT username as name, 'admin' as role FROM admins WHERE id = ?";
    } else {
        $userQuery = "SELECT name, 'voter' as role, profile_photo FROM voters WHERE id = ?";
    }
    $stmt = $db->prepare($userQuery);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $userData = $stmt->get_result()->fetch_assoc();
}


?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VoteNepal - Digital Democracy Platform</title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* ========== CSS VARIABLES - THEME SYSTEM ========== */
        :root[data-theme="light"] {
            --primary-50: #eef2ff;
            --primary-100: #e0e7ff;
            --primary-200: #c7d2fe;
            --primary-300: #a5b4fc;
            --primary-400: #818cf8;
            --primary-500: #6366f1;
            --primary-600: #4f46e5;
            --primary-700: #4338ca;
            --primary-800: #3730a3;
            --primary-900: #312e81;
            
            --surface-0: #ffffff;
            --surface-50: #f8fafc;
            --surface-100: #f1f5f9;
            --surface-200: #e2e8f0;
            --surface-300: #cbd5e1;
            --surface-400: #94a3b8;
            
            --text-primary: #0f172a;
            --text-secondary: #334155;
            --text-tertiary: #64748b;
            --text-inverse: #ffffff;
            
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --info: #3b82f6;
            
            --gradient-1: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-2: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            --gradient-3: linear-gradient(135deg, #059669 0%, #10b981 100%);
            
            --card-bg: rgba(255, 255, 255, 0.9);
            --card-border: 1px solid rgba(203, 213, 225, 0.3);
            --nav-bg: rgba(255, 255, 255, 0.8);
            --shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            --shadow-hover: 0 20px 60px rgba(79, 70, 229, 0.15);
            
            --chart-grid: #e2e8f0;
            --chart-text: #334155;
        }

        :root[data-theme="dark"] {
            --primary-50: #2e3a5c;
            --primary-100: #3a4a70;
            --primary-200: #4a5f8a;
            --primary-300: #5a74a4;
            --primary-400: #6a89be;
            --primary-500: #7a9ed8;
            --primary-600: #8ab3f2;
            --primary-700: #9ac8ff;
            --primary-800: #aaddff;
            --primary-900: #baf2ff;
            
            --surface-0: #0f172a;
            --surface-50: #1e293b;
            --surface-100: #334155;
            --surface-200: #475569;
            --surface-300: #64748b;
            --surface-400: #94a3b8;
            
            --text-primary: #f1f5f9;
            --text-secondary: #cbd5e1;
            --text-tertiary: #94a3b8;
            --text-inverse: #0f172a;
            
            --success: #34d399;
            --warning: #fbbf24;
            --error: #f87171;
            --info: #60a5fa;
            
            --gradient-1: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
            --gradient-2: linear-gradient(135deg, #2d2b55 0%, #4338ca 100%);
            --gradient-3: linear-gradient(135deg, #065f46 0%, #059669 100%);
            
            --card-bg: rgba(30, 41, 59, 0.9);
            --card-border: 1px solid rgba(71, 85, 105, 0.3);
            --nav-bg: rgba(15, 23, 42, 0.8);
            --shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            --shadow-hover: 0 20px 60px rgba(0, 0, 0, 0.4);
            
            --chart-grid: #334155;
            --chart-text: #cbd5e1;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--surface-0);
            color: var(--text-primary);
            line-height: 1.6;
            transition: background-color 0.3s ease, color 0.2s ease;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        ::-webkit-scrollbar-track {
            background: var(--surface-100);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-500);
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-600);
        }

        /* Glass Navbar */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: var(--nav-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(203, 213, 225, 0.1);
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            box-shadow: var(--shadow);
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0.75rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .logo i {
            background: var(--gradient-1);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-size: 2rem;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 1rem;
            list-style: none;
        }

        .nav-links a {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 2px;
            background: var(--gradient-1);
            transition: width 0.3s ease;
        }

        .nav-links a:hover::after,
        .nav-links a.active::after {
            width: 80%;
        }

        .nav-links a:hover,
        .nav-links a.active {
            color: var(--primary-600);
        }

        /* Theme Toggle */
        .theme-toggle {
            background: var(--surface-100);
            border: none;
            border-radius: 30px;
            padding: 0.3rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            position: relative;
            width: 60px;
            height: 30px;
            transition: background 0.3s ease;
        }

        .theme-toggle i {
            font-size: 1rem;
            color: var(--text-secondary);
            transition: all 0.3s ease;
            z-index: 2;
        }

        .theme-toggle .toggle-indicator {
            position: absolute;
            width: 24px;
            height: 24px;
            background: var(--primary-500);
            border-radius: 50%;
            transition: transform 0.3s ease;
            z-index: 1;
        }

        [data-theme="dark"] .theme-toggle .toggle-indicator {
            transform: translateX(30px);
        }

        [data-theme="dark"] .fa-sun {
            color: var(--text-tertiary);
        }

        [data-theme="dark"] .fa-moon {
            color: var(--text-primary);
        }

        /* User Dropdown */
        .user-dropdown {
            position: relative;
            display: inline-block;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--gradient-1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .user-avatar:hover {
            transform: scale(1.05);
            border-color: var(--primary-400);
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .dropdown-menu {
            position: absolute;
            top: 120%;
            right: 0;
            background: var(--surface-50);
            border-radius: 12px;
            padding: 0.5rem;
            min-width: 200px;
            box-shadow: var(--shadow);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            border: 1px solid var(--surface-200);
            z-index: 100;
        }

        .user-dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--text-primary);
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.3s ease;
        }

        .dropdown-item:hover {
            background: var(--surface-200);
        }

        .dropdown-item i {
            width: 20px;
            color: var(--primary-500);
        }

        .dropdown-divider {
            height: 1px;
            background: var(--surface-200);
            margin: 0.5rem 0;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: var(--gradient-1);
            color: white;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.4);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary-500);
            color: var(--primary-500);
        }

        .btn-outline:hover {
            background: var(--primary-500);
            color: white;
        }

        .btn-large {
            padding: 0.8rem 2rem;
            font-size: 1rem;
        }

        /* Logout Alert */
        .logout-alert {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideIn 0.5s ease, fadeOut 0.5s ease 3s forwards;
            transform: translateX(400px);
        }

        @keyframes slideIn {
            to { transform: translateX(0); }
        }

        @keyframes fadeOut {
            to { opacity: 0; transform: translateX(400px); }
        }

        .logout-alert i { font-size: 24px; }
        .logout-alert-content { flex: 1; }
        .logout-alert h4 { margin: 0; font-size: 16px; font-weight: 600; }
        .logout-alert p { margin: 5px 0 0; font-size: 14px; opacity: 0.9; }
        .logout-alert-close {
            background: none; border: none; color: white; font-size: 20px;
            cursor: pointer; opacity: 0.7; transition: opacity 0.3s;
        }
        .logout-alert-close:hover { opacity: 1; }

        /* Hero Section */
        .hero-section {
            min-height: 90vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            background: var(--gradient-1);
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 0%, transparent 50%);
        }

        .hero-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 4rem;
            padding: 4rem 2rem;
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        .hero-text { flex: 1; }
        .hero-text h1 {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: white;
        }
        .hero-text p {
            font-size: 1.2rem;
            color: rgba(255,255,255,0.9);
            margin-bottom: 2rem;
        }

        .hero-stats {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .stat-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            min-width: 150px;
            border: 1px solid rgba(255,255,255,0.2);
            transition: transform 0.3s ease;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
        }
        .stat-card p {
            font-size: 1rem;
            color: rgba(255,255,255,0.8);
        }

        /* Live Count Section */
        .live-count-section {
            padding: 4rem 2rem;
            background: var(--surface-0);
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 3rem;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 700;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .live-pulse {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .pulse-dot {
            width: 12px;
            height: 12px;
            background: #ef4444;
            border-radius: 50%;
            position: relative;
        }
        .pulse-dot::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: #ef4444;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            100% { transform: scale(2.5); opacity: 0; }
        }

        .live-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .live-stat-card {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            border: var(--card-border);
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }
        .live-stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            background: var(--gradient-1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
        }

        .stat-content { flex: 1; }
        .stat-label {
            display: block;
            font-size: 0.9rem;
            color: var(--text-tertiary);
            margin-bottom: 0.25rem;
        }
        .stat-number {
            display: block;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.2;
        }

        /* Live Details Grid */
        .live-details-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .live-card {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            border: var(--card-border);
            box-shadow: var(--shadow);
        }

        .live-card h3 {
            margin-bottom: 1.5rem;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.2rem;
        }

        /* Party List */
        .party-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .party-row {
            display: grid;
            grid-template-columns: 40px 1fr 100px;
            align-items: center;
            gap: 1rem;
            padding: 0.5rem;
            background: var(--surface-50);
            border-radius: 10px;
            transition: transform 0.2s ease;
        }
        .party-row:hover { transform: translateX(5px); }

        .party-rank {
            font-weight: 700;
            color: var(--primary-500);
            text-align: center;
        }

        .party-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .party-logo-small {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
        }

        .party-name {
            font-weight: 500;
            color: var(--text-primary);
        }

        .party-votes {
            font-weight: 600;
            color: var(--primary-500);
            text-align: right;
        }

        .party-bar {
            grid-column: 1 / -1;
            height: 8px;
            background: var(--surface-200);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 0.25rem;
        }

        .bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-500), var(--primary-300));
            border-radius: 4px;
            transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Chart Card */
        .chart-card {
            display: flex;
            flex-direction: column;
        }

        .chart-container {
            position: relative;
            flex: 1;
            min-height: 250px;
        }

        #voteChart {
            max-height: 250px;
            width: 100% !important;
        }

        .chart-center-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            pointer-events: none;
        }
        .chart-center-text .total {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1;
        }
        .chart-center-text .label {
            font-size: 0.8rem;
            color: var(--text-tertiary);
        }

        /* Features Section */
        .features {
            padding: 4rem 2rem;
            background: var(--surface-50);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .feature-card {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            border: var(--card-border);
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .feature-card h3 {
            font-size: 1.2rem;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        .feature-card p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Scroll to Top Button */
        .scroll-top {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--gradient-1);
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
            z-index: 99;
        }
        .scroll-top.show {
            opacity: 1;
            visibility: visible;
        }
        .scroll-top:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        /* Footer */
        footer {
            background: var(--surface-0);
            padding: 2rem;
            text-align: center;
            border-top: 1px solid var(--surface-200);
            color: var(--text-tertiary);
        }

        /* Live Footer */
        .live-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid var(--surface-200);
        }
        .last-updated {
            color: var(--text-tertiary);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        .security-note {
            color: var(--text-tertiary);
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .live-details-grid {
                grid-template-columns: 1fr;
            }
            .hero-content {
                flex-direction: column;
                text-align: center;
            }
            .hero-stats {
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .nav-container {
                padding: 0.75rem 1rem;
                flex-wrap: wrap;
            }
            .nav-links {
                order: 3;
                width: 100%;
                justify-content: center;
                margin-top: 1rem;
                flex-wrap: wrap;
            }
            .hero-text h1 {
                font-size: 2.5rem;
            }
            .stat-card {
                min-width: 120px;
                padding: 1.5rem;
            }
            .stat-card h3 {
                font-size: 2rem;
            }
            .live-stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Logout Alert -->
    <?php if (isset($_GET['logout']) && $_GET['logout'] == 'success'): ?>
    <div class="logout-alert" id="logoutAlert">
        <i class="fas fa-check-circle"></i>
        <div class="logout-alert-content">
            <h4>Logout Successful!</h4>
            <p>You have been logged out successfully. / तपाईं सफलतापूर्वक लगआउट हुनुभयो।</p>
        </div>
        <button class="logout-alert-close" onclick="document.getElementById('logoutAlert').remove()">&times;</button>
    </div>
    <?php endif; ?>

    <!-- Navbar -->
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <div class="logo">
                <i class="fas fa-vote-yea"></i>
                <span>VoteNepal</span>
            </div>

            <ul class="nav-links">
                <li><a href="#home" class="nav-link active">Home</a></li>
                <li><a href="#about" class="nav-link">About</a></li>
                <li><a href="#features" class="nav-link">Features</a></li>
                <li><a href="#live-count" class="nav-link">Live Count</a></li>
                
                <!-- Theme Toggle -->
                <li>
                    <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                        <i class="fas fa-sun"></i>
                        <i class="fas fa-moon"></i>
                        <span class="toggle-indicator"></span>
                    </button>
                </li>
                
                <?php if (isLoggedIn()): ?>
                    <!-- User Dropdown -->
                    <li class="user-dropdown">
                        <div class="user-avatar">
                            <?php if (isset($userData['profile_photo']) && $userData['profile_photo']): ?>
                                <img src="assets/uploads/voters/<?php echo $userData['profile_photo']; ?>" alt="User">
                            <?php else: ?>
                                <?php echo strtoupper(substr($userData['name'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                        <div class="dropdown-menu">
                            <div class="dropdown-item">
                                <i class="fas fa-user"></i>
                                <div>
                                    <div><?php echo htmlspecialchars($userData['name']); ?></div>
                                    <small style="color: var(--text-tertiary);"><?php echo ucfirst($userData['role']); ?></small>
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <?php if (isAdmin()): ?>
                                <a href="admin/dashboard.php" class="dropdown-item">
                                    <i class="fas fa-tachometer-alt"></i>
                                    Dashboard
                                </a>
                                <a href="admin/change_password.php" class="dropdown-item">
                                    <i class="fas fa-key"></i>
                                    Change Password
                                </a>
                            <?php else: ?>
                                <a href="voter/dashboard.php" class="dropdown-item">
                                    <i class="fas fa-tachometer-alt"></i>
                                    Dashboard
                                </a>
                                <a href="voter/profile.php" class="dropdown-item">
                                    <i class="fas fa-id-card"></i>
                                    My Profile
                                </a>
                                <a href="voter/change_password.php" class="dropdown-item">
                                    <i class="fas fa-key"></i>
                                    Change Password
                                </a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a href="<?php echo isAdmin() ? 'admin/logout.php' : 'voter/logout.php'; ?>" class="dropdown-item">
                                <i class="fas fa-sign-out-alt"></i>
                                Logout
                            </a>
                        </div>
                    </li>
                <?php else: ?>
                    <li><a href="voter/login.php" class="btn btn-outline">Voter Login</a></li>
                    <li><a href="admin/index.php" class="btn btn-primary">Admin Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="hero-content">
            <div class="hero-text">
                <h1>Welcome to Nepal's<br>Digital Democracy</h1>
                <p>Secure, Transparent, and Accessible Voting System for All Nepali Citizens</p>
                <div class="cta-buttons">
                    <?php if (!isLoggedIn()): ?>
                        <a href="voter/register.php" class="btn btn-primary btn-large">
                            <i class="fas fa-user-plus"></i>
                            Register as Voter
                        </a>
                    <?php endif; ?>
                    <a href="#live-count" class="btn btn-outline btn-large">
                        <i class="fas fa-chart-line"></i>
                        View Live Results
                    </a>
                </div>
            </div>
            <div class="hero-stats">
                <div class="stat-card">
                    <h3>7</h3>
                    <p>Provinces</p>
                </div>
                <div class="stat-card">
                    <h3>77</h3>
                    <p>Districts</p>
                </div>
                <div class="stat-card">
                    <h3>165</h3>
                    <p>Constituencies</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Live Vote Counting Section -->
    <section id="live-count" class="live-count-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Live Election Results</h2>
                <div class="live-pulse">
                    <span class="pulse-dot"></span>
                    <span>LIVE</span>
                </div>
            </div>
            
            <div class="live-stats-grid">
                <div class="live-stat-card total-votes">
                    <div class="stat-icon">🗳️</div>
                    <div class="stat-content">
                        <span class="stat-label">Total Votes Cast</span>
                        <span class="stat-number counter" id="totalVotes" data-target="<?php echo $stats['total_votes']; ?>">
                            <?php echo number_format($stats['total_votes']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="live-stat-card fptp-votes">
                    <div class="stat-icon">🏛️</div>
                    <div class="stat-content">
                        <span class="stat-label">FPTP Votes</span>
                        <span class="stat-number counter" id="fptpVotes" data-target="<?php echo $stats['fptp_votes']; ?>">
                            <?php echo number_format($stats['fptp_votes']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="live-stat-card pr-votes">
                    <div class="stat-icon">📋</div>
                    <div class="stat-content">
                        <span class="stat-label">PR Votes</span>
                        <span class="stat-number counter" id="prVotes" data-target="<?php echo $stats['pr_votes']; ?>">
                            <?php echo number_format($stats['pr_votes']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="live-stat-card turnout">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <span class="stat-label">Voter Turnout</span>
                        <span class="stat-number counter" id="turnout" data-target="<?php echo $stats['turnout']; ?>">
                            <?php echo $stats['turnout']; ?>%
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="live-details-grid">
                <!-- Party-wise Results -->
                <div class="live-card party-results">
                    <h3><i class="fas fa-trophy" style="color: var(--primary-500);"></i> Leading Parties</h3>
                    <div class="party-list" id="partyList">
                        <?php if (!empty($stats['top_parties'])): ?>
                            <?php foreach($stats['top_parties'] as $index => $party): ?>
                            <div class="party-row">
                                <div class="party-rank">#<?php echo $index + 1; ?></div>
                                <div class="party-info">
                                    <?php if(!empty($party['party_logo'])): ?>
                                        <img src="assets/uploads/parties/<?php echo $party['party_logo']; ?>" 
                                             alt="<?php echo $party['party_name']; ?>" class="party-logo-small">
                                    <?php endif; ?>
                                    <span class="party-name"><?php echo $party['party_name']; ?></span>
                                </div>
                                <div class="party-votes counter" data-target="<?php echo $party['vote_count']; ?>">
                                    <?php echo number_format($party['vote_count']); ?>
                                </div>
                                <div class="party-bar">
                                    <div class="bar-fill" style="width: <?php echo min(100, ($party['vote_count'] / max($stats['total_votes'], 1)) * 100); ?>%;">
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="party-row">
                                <div class="party-rank">#1</div>
                                <div class="party-info">
                                    <span class="party-name">No votes yet</span>
                                </div>
                                <div class="party-votes">0</div>
                                <div class="party-bar">
                                    <div class="bar-fill" style="width: 0%;"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Vote Distribution Chart -->
                <div class="live-card chart-card">
                    <h3><i class="fas fa-chart-pie" style="color: var(--primary-500);"></i> Vote Distribution</h3>
                    <div class="chart-container">
                        <canvas id="voteChart"></canvas>
                        <div class="chart-center-text">
                            <span class="total counter" data-target="<?php echo $stats['total_votes']; ?>">
                                <?php echo number_format($stats['total_votes']); ?>
                            </span>
                            <span class="label">Total Votes</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="live-footer">
                <p class="last-updated">
                    <i class="fas fa-sync-alt" style="margin-right: 0.5rem;"></i>
                    Last Updated: <span id="lastUpdated"><?php echo date('h:i:s A'); ?></span>
                </p>
                <p class="security-note">
                    <i class="fas fa-shield-alt"></i>
                    Votes are encrypted and securely counted
                </p>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="container">
            <h2 class="section-title">Why Choose VoteNepal?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🔒</div>
                    <h3>Secure Voting</h3>
                    <p>End-to-end encrypted voting system ensuring ballot secrecy</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">👤</div>
                    <h3>Easy Registration</h3>
                    <p>Simple online registration with document verification</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Real-time Results</h3>
                    <p>Instant result calculation and publication</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📱</div>
                    <h3>Mobile Friendly</h3>
                    <p>Access from any device, anywhere in Nepal</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> VoteNepal. All rights reserved.</p>
            <p style="margin-top: 0.5rem; font-size: 0.9rem;">
                <i class="fas fa-heart" style="color: var(--error);"></i> 
                Empowering Democracy in Nepal
            </p>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <button class="scroll-top" id="scrollTop" aria-label="Scroll to top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script>
        // ========== THEME TOGGLE ==========
        const themeToggle = document.getElementById('themeToggle');
        const htmlElement = document.documentElement;
        
        const savedTheme = localStorage.getItem('theme') || 'light';
        htmlElement.setAttribute('data-theme', savedTheme);
        
        themeToggle.addEventListener('click', () => {
            const currentTheme = htmlElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            htmlElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            if (voteChart) {
                voteChart.options.plugins.legend.labels.color = getComputedStyle(document.documentElement)
                    .getPropertyValue('--chart-text').trim();
                voteChart.update();
            }
        });

        // ========== NAVBAR ==========
        const navbar = document.getElementById('navbar');
        const sections = document.querySelectorAll('section[id]');
        const navLinks = document.querySelectorAll('.nav-link');

        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
            
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop - 100;
                const sectionHeight = section.clientHeight;
                if (scrollY >= sectionTop && scrollY < sectionTop + sectionHeight) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${current}`) {
                    link.classList.add('active');
                }
            });
        });

        // ========== NUMBER COUNTER ==========
        function animateNumbers() {
            const counters = document.querySelectorAll('.counter');
            
            counters.forEach(counter => {
                const target = parseInt(counter.getAttribute('data-target'));
                const current = parseInt(counter.innerText.replace(/[^0-9]/g, '')) || 0;
                
                if (current !== target) {
                    const increment = Math.ceil((target - current) / 20);
                    const newValue = current + increment;
                    
                    if (counter.id === 'turnout') {
                        counter.innerText = Math.min(newValue, target) + '%';
                    } else {
                        counter.innerText = Math.min(newValue, target).toLocaleString();
                    }
                    
                    if (newValue < target) {
                        setTimeout(animateNumbers, 20);
                    }
                }
            });
        }

        animateNumbers();

        // ========== CHART ==========
        const ctx = document.getElementById('voteChart').getContext('2d');
        const chartTextColor = getComputedStyle(document.documentElement)
            .getPropertyValue('--chart-text').trim();
        
        const voteChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['FPTP Votes', 'PR Votes'],
                datasets: [{
                    data: [<?php echo $stats['fptp_votes']; ?>, <?php echo $stats['pr_votes']; ?>],
                    backgroundColor: ['#6366f1', '#10b981'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '70%',
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            color: chartTextColor,
                            font: { family: 'Inter', size: 12 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value.toLocaleString()} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        function updateChart(fptp, pr) {
            voteChart.data.datasets[0].data = [fptp, pr];
            voteChart.update();
        }

        // ========== SCROLL TO TOP ==========
        const scrollTopBtn = document.getElementById('scrollTop');
        
        window.addEventListener('scroll', () => {
            if (window.scrollY > 500) {
                scrollTopBtn.classList.add('show');
            } else {
                scrollTopBtn.classList.remove('show');
            }
        });
        
        scrollTopBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // ========== AUTO-REFRESH ==========
        function updateLiveCounts() {
            fetch('api/live_counts.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('totalVotes').setAttribute('data-target', data.total_votes);
                    document.getElementById('fptpVotes').setAttribute('data-target', data.fptp_votes);
                    document.getElementById('prVotes').setAttribute('data-target', data.pr_votes);
                    document.getElementById('turnout').setAttribute('data-target', data.turnout);
                    document.getElementById('lastUpdated').textContent = new Date().toLocaleTimeString();
                    
                    updateChart(data.fptp_votes, data.pr_votes);
                    animateNumbers();
                })
                .catch(error => console.error('Error updating live counts:', error));
        }

        setInterval(updateLiveCounts, 30000);

        setTimeout(function() {
            const alert = document.getElementById('logoutAlert');
            if (alert) alert.remove();
        }, 3500);
    </script>
</body>
</html>