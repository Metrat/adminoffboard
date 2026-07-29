# Admin Offboard

[![Nextcloud](https://img.shields.io/badge/Nextcloud-34-blue.svg)](https://nextcloud.com/)
[![PHP](https://img.shields.io/badge/PHP-8.2+-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-AGPLv3-blue.svg)](LICENSE)
[![CI](https://github.com/Metrat/adminoffboard/actions/workflows/ci.yml/badge.svg)](https://github.com/Metrat/adminoffboard/actions/workflows/ci.yml)
[![Code Coverage](https://codecov.io/gh/Metrat/adminoffboard/branch/main/graph/badge.svg)](https://codecov.io/gh/Metrat/adminoffboard)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Metrat/adminoffboard/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/Metrat/adminoffboard/?branch=main)

## 🚀 Enterprise Administration Tool for Nextcloud

Admin Offboard is a powerful administration extension for Nextcloud that provides enterprise-grade user offboarding and device management capabilities.

## 📦 Installation

### From Nextcloud App Store
1. Open Nextcloud Apps
2. Search for "Admin Offboard"
3. Click Install

### From GitHub
```bash
cd /path/to/nextcloud/apps
git clone https://github.com/Metrat/adminoffboard.git
cd adminoffboard
composer install --no-dev
php occ app:enable adminoffboard

Quick Start
# Offboard a user
php occ adminoffboard:offboard --user=username

# Disable multiple users
php occ adminoffboard:disable-users --users=user1,user2,user3

# Process queue
php occ adminoffboard:process-queue

# View audit logs
php occ adminoffboard:audit

🔒 Security
All operations require admin privileges

Full audit logging

Idempotent operations

Dry-run support

Only uses public OCP API

🤝 Contributing
Please read CONTRIBUTING.md for details on our code of conduct and the process for submitting pull requests.

📄 License
This project is licensed under the AGPL-3.0 License - see the LICENSE file for details.

🙏 Credits
Developed by Metrat