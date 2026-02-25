# 🗳️ VoteNepal - Online Voting System

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![PHP](https://img.shields.io/badge/PHP-8.3-purple)
![MySQL](https://img.shields.io/badge/MySQL-8.0-orange)
![License](https://img.shields.io/badge/license-MIT-green)

**VoteNepal** is a secure, transparent, and accessible online voting system designed for Nepal's elections. It supports both FPTP (First Past The Post) and PR (Proportional Representation) voting systems with real-time result tracking.

## 📋 Features

### 👥 For Voters
- **Easy Registration** - Register with citizenship number and personal details
- **Voter ID Generation** - Unique voter ID sent via email after registration
- **Secure Login** - Password-protected voter accounts
- **FPTP Voting** - Vote for constituency representatives
- **PR Voting** - Vote for proportional representation
- **Profile Management** - Update personal information and change password
- **Download Information** - Download voter details as PDF/HTML
- **Live Results** - View real-time election results

### 👑 For Administrators
- **Admin Dashboard** - Complete system overview
- **Voter Management** - Add, edit, verify, delete voters
- **Party Management** - Add, edit, delete political parties
- **Candidate Management** - Add, edit, delete candidates
- **Location Management** - Manage provinces, districts, constituencies
- **Real-time Results** - View live vote counts and statistics
- **Election Management** - Configure upcoming elections

### 🔒 Security Features
- Password hashing with bcrypt
- CSRF protection
- Session management
- Input sanitization
- Secure file uploads
- Email verification (OTP)

## 🖥️ Technology Stack

| Component | Technology |
|-----------|------------|
| **Frontend** | HTML5, CSS3, JavaScript, Chart.js |
| **Backend** | PHP 8.3 |
| **Database** | MySQL 8.0 |
| **Server** | Apache (WAMP) |
| **PDF Generation** | Dompdf |
| **Email Service** | PHPMailer (Gmail SMTP) |
| **Version Control** | Git |

## 📁 Project Structure
voting-system/
├── index.php # Landing page with live results
├── .htaccess # Apache configuration
├── includes/ # Core PHP files
│ ├── config.php # Database configuration
│ ├── db_connection.php # Database connection
│ ├── functions.php # Helper functions
│ ├── auth.php # Authentication class
│ ├── session.php # Session management
│ └── mailer.php # Email functions
├── admin/ # Admin panel
│ ├── index.php # Admin login
│ ├── dashboard.php # Admin dashboard
│ ├── manage_voters.php # Voter management
│ ├── manage_parties.php # Party management
│ ├── manage_candidates.php # Candidate management
│ ├── manage_provinces.php # Province management
│ ├── manage_districts.php # District management
│ └── manage_constituencies.php # Constituency management
├── voter/ # Voter panel
│ ├── register.php # Voter registration
│ ├── login.php # Voter login
│ ├── dashboard.php # Voter dashboard
│ ├── vote_fptp.php # FPTP voting
│ ├── vote_pr.php # PR voting
│ ├── profile.php # View profile
│ ├── edit_profile.php # Edit profile
│ ├── change_password.php # Change password
│ ├── download_info.php # Download information
│ └── forgot_password.php # Password recovery
├── api/ # API endpoints
│ ├── get_districts.php # Get districts by province
│ ├── get_constituencies.php # Get constituencies by district
│ └── live_counts.php # Live vote counts
├── assets/ # Static assets
│ ├── css/ # Stylesheets
│ ├── js/ # JavaScript files
│ ├── uploads/ # Uploaded files
│ └── fonts/ # Custom fonts
└── database/ # SQL files
└── database.sql # Database schema



## 🚀 Installation Guide

### Prerequisites
- WAMP Server (PHP 8.3+, MySQL 8.0+)
- Composer (for PHP dependencies)
- Git (optional)

### Step 1: Clone the Repository
```bash
git clone https://github.com/Dhiraj98Dhakal/voting-system.git
cd voting-system