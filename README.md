# 🗳️ VoteNepal - Online Voting System

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![PHP](https://img.shields.io/badge/PHP-8.3-purple)
![MySQL](https://img.shields.io/badge/MySQL-8.0-orange)
![License](https://img.shields.io/badge/license-MIT-green)

**VoteNepal** is a secure, transparent, and accessible online voting system designed for Nepal's elections. It supports both FPTP (First Past The Post) and PR (Proportional Representation) voting systems with real-time result tracking.

---

## 📋 Features

### 👥 For Voters
| Feature | Description |
|---------|-------------|
| **Easy Registration** | Register with citizenship number and personal details |
| **Voter ID Generation** | Unique voter ID sent via email after registration |
| **Secure Login** | Password-protected voter accounts |
| **FPTP Voting** | Vote for constituency representatives |
| **PR Voting** | Vote for proportional representation |
| **Profile Management** | Update personal information and change password |
| **Download Information** | Download voter details as PDF/HTML |
| **Live Results** | View real-time election results |
| **Password Recovery** | OTP-based password reset via email |

### 👑 For Administrators
| Feature | Description |
|---------|-------------|
| **Admin Dashboard** | Complete system overview with statistics |
| **Voter Management** | Add, edit, verify, delete voters |
| **Party Management** | Add, edit, delete political parties |
| **Candidate Management** | Add, edit, delete candidates |
| **Location Management** | Manage provinces (7), districts (77), constituencies (165) |
| **Real-time Results** | View live vote counts and statistics |
| **Election Management** | Configure upcoming elections |
| **Data Export** | Export results and voter information |

### 🔒 Security Features
- ✅ Password hashing with bcrypt
- ✅ CSRF protection on all forms
- ✅ Session management with timeout
- ✅ Input sanitization against XSS
- ✅ Secure file uploads with validation
- ✅ Email verification (OTP) for password reset
- ✅ MD5 encryption for admin passwords

---

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
| **Icons** | Font Awesome 6 |
| **Fonts** | Google Fonts (Inter) |

---

## 📁 Project Structure
C:\wamp64\www\voting-system/
│
├── 📄 index.php
├── 📄 .htaccess
├── 📄 composer.json
├── 📄 README.md
├── 📄 test_email.php
├── 📄 test_db.php
│
├── 📁 includes/
│   ├── config.php
│   ├── db_connection.php
│   ├── functions.php
│   ├── auth.php
│   ├── session.php
│   └── mailer.php
│
├── 📁 admin/
│   ├── index.php
│   ├── dashboard.php
│   ├── manage_voters.php
│   ├── add_voter.php
│   ├── edit_voter.php
│   ├── view_voter.php
│   ├── manage_parties.php
│   ├── add_party.php
│   ├── edit_party.php
│   ├── manage_candidates.php
│   ├── add_candidate.php
│   ├── edit_candidate.php
│   ├── manage_provinces.php
│   ├── manage_districts.php
│   ├── manage_constituencies.php
│   ├── view_results.php
│   ├── change_password.php
│   └── logout.php
│
├── 📁 voter/
│   ├── register.php
│   ├── login.php
│   ├── dashboard.php
│   ├── vote_fptp.php
│   ├── vote_pr.php
│   ├── vote_success.php
│   ├── profile.php
│   ├── edit_profile.php
│   ├── change_password.php
│   ├── download_info.php
│   ├── forgot_password.php
│   ├── resend_otp.php
│   ├── navbar.php
│   └── logout.php
│
├── 📁 api/
│   ├── get_districts.php
│   ├── get_constituencies.php
│   ├── get_district_info.php
│   └── live_counts.php
│
├── 📁 assets/
│   ├── 📁 css/
│   │   ├── style.css
│   │   ├── admin.css
│   │   └── live-count.css
│   │
│   ├── 📁 js/
│   │   ├── main.js
│   │   └── registration.js
│   │
│   ├── 📁 uploads/
│   │   ├── 📁 voters/
│   │   ├── 📁 parties/
│   │   └── 📁 candidates/
│   │
│   └── 📁 fonts/
│       └── NotoSansDevanagari-Regular.ttf
│
├── 📁 cache/
│   └── dashboard_stats.json
│
├── 📁 database/
│   └── database.sql
│
└── 📁 vendor/
    ├── 📁 autoload.php
    ├── 📁 composer/
    └── 📁 dompdf/


# 📁 COMPLETE FILE STRUCTURE

## Root Directory (C:\wamp64\www\voting-system/)
- `index.php`                    # Homepage with live results
- `.htaccess`                    # Apache configuration
- `composer.json`                 # PHP dependencies
- `README.md`                     # Project documentation
- `test_email.php`                # Email testing script
- `test_db.php`                   # Database testing script

## 📁 includes/ - Core PHP Files
- `config.php`                    # Database and site configuration
- `db_connection.php`              # Database connection class
- `functions.php`                  # Helper functions
- `auth.php`                       # Authentication class
- `session.php`                    # Session management
- `mailer.php`                     # Email functions

## 📁 admin/ - Admin Panel
### Authentication
- `index.php`                      # Admin login
- `logout.php`                     # Admin logout
- `change_password.php`             # Admin password change

### Dashboard
- `dashboard.php`                  # Admin dashboard

### Voter Management
- `manage_voters.php`               # List all voters
- `add_voter.php`                   # Add new voter
- `edit_voter.php`                  # Edit voter
- `view_voter.php`                  # View voter details

### Party Management
- `manage_parties.php`              # List all parties
- `add_party.php`                   # Add new party
- `edit_party.php`                  # Edit party

### Candidate Management
- `manage_candidates.php`            # List all candidates
- `add_candidate.php`                # Add new candidate
- `edit_candidate.php`               # Edit candidate

### Location Management
- `manage_provinces.php`             # Manage provinces
- `manage_districts.php`             # Manage districts
- `manage_constituencies.php`        # Manage constituencies

### Results
- `view_results.php`                 # View election results

## 📁 voter/ - Voter Panel
### Authentication
- `register.php`                     # Voter registration
- `login.php`                        # Voter login
- `logout.php`                       # Voter logout
- `forgot_password.php`               # Password recovery
- `resend_otp.php`                    # Resend OTP
- `change_password.php`                # Change password

### Dashboard & Profile
- `dashboard.php`                     # Voter dashboard
- `profile.php`                       # View profile
- `edit_profile.php`                   # Edit profile
- `navbar.php`                         # Common navigation

### Voting
- `vote_fptp.php`                      # FPTP voting
- `vote_pr.php`                        # PR voting
- `vote_success.php`                    # Vote confirmation

### Downloads
- `download_info.php`                   # Download voter info (PDF)

## 📁 api/ - API Endpoints
- `get_districts.php`                   # Get districts by province
- `get_constituencies.php`               # Get constituencies by district
- `get_district_info.php`                 # Get district details
- `live_counts.php`                       # Live vote counts

## 📁 assets/ - Static Assets
### CSS
- `css/style.css`                        # Main stylesheet
- `css/admin.css`                        # Admin panel styles
- `css/live-count.css`                    # Live count styles

### JavaScript
- `js/main.js`                           # Main JavaScript
- `js/registration.js`                    # Registration JavaScript

### Uploads
- `uploads/voters/`                       # Voter photos
- `uploads/parties/`                      # Party logos
- `uploads/candidates/`                    # Candidate photos

### Fonts
- `fonts/NotoSansDevanagari-Regular.ttf`   # Nepali font for PDF

## 📁 cache/ - Cache Directory
- `dashboard_stats.json`                   # Cached vote counts

## 📁 database/ - SQL Files
- `database.sql`                           # Complete database schema

## 📁 vendor/ - Composer Dependencies
- `autoload.php`                           # Composer autoload
- `composer/`                              # Composer files
- `dompdf/`                                 # Dompdf library
- `phpmailer/`                              # PHPMailer library




---

## 🚀 Installation Guide

### Prerequisites
| Requirement | Version |
|-------------|---------|
| WAMP Server | 3.3+ |
| PHP | 8.0+ |
| MySQL | 5.7+ |
| Composer | Latest |
| Git | Optional |

### Step 1: Clone the Repository
```bash
git clone https://github.com/Dhiraj98Dhakal/voting-system.git
cd voting-system