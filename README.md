# Admin Offboard

[![Nextcloud](https://img.shields.io/badge/Nextcloud-34-blue.svg)](https://nextcloud.com/)
[![PHP](https://img.shields.io/badge/PHP-8.2+-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-AGPLv3-blue.svg)](LICENSE)

## 🚀 Enterprise Administration Tool for Nextcloud

Admin Offboard is a powerful administration extension for Nextcloud that provides enterprise-grade user offboarding and device management capabilities.

## ✨ Features

### 🔐 Security & Compliance
- **Audit Trail**: Complete logging of all administrative actions
- **Idempotent Operations**: Safe to retry operations
- **Dry Run Mode**: Test before executing
- **Admin-Only**: Strict permission controls

### 📱 Device Management
- **Mass Token Deletion**: Remove all device tokens for users in bulk
- **Remote Wipe**: Support for compatible clients (Desktop, Mobile)
- **Device Discovery**: List and manage user devices

### 👥 User Management
- **Mass Disable**: Disable multiple user accounts at once
- **Batch Operations**: Process users in bulk via queue
- **Workflow Engine**: Customizable operation workflows

### 🎯 User Interface
- **Admin Dashboard**: Central management interface
- **Bulk Operations**: Batch actions with progress tracking
- **Job Monitoring**: Queue status and job details

### 🔌 Integration
- **REST API**: Complete API for automation
- **OCC Commands**: Command-line interface
- **Nextcloud Hooks**: Integration with Nextcloud events

## 📋 Requirements

- Nextcloud Hub 26 (34.0.2) or higher
- PHP 8.2 or higher
- MySQL/MariaDB/PostgreSQL

## 🚀 Quick Start

### Installation

1. Download the app from the Nextcloud App Store or GitHub
2. Extract to your Nextcloud apps directory
3. Enable the app via Nextcloud administration panel:
   ```bash
   sudo -u www-data php occ app:enable adminoffboard