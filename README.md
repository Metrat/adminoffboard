# Admin Offboard

[![Nextcloud](https://img.shields.io/badge/Nextcloud-34-blue.svg)](https://nextcloud.com/)
[![PHP](https://img.shields.io/badge/PHP-8.2+-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-AGPLv3-blue.svg)](LICENSE)
[![Version](https://img.shields.io/badge/Version-0.2.3-green.svg)](https://github.com/Metrat/adminoffboard/releases)

## 🚀 Enterprise Administration Tool for Nextcloud

Admin Offboard provides enterprise-grade user offboarding and device management capabilities for Nextcloud 34.

## ✨ Features

- **6 Console Commands** — test, users:disable, tokens:delete, offboard, process-queue, remote-wipe
- **Remote Wipe** — реальное удаление токенов устройств с push-уведомлениями
- **Background Jobs** — автоматическая обработка очереди каждые 5 минут
- **Audit Logging** — полный аудит всех операций
- **Device Management** — синхронизация устройств из токенов
- **Queue System** — отложенные операции с retry

## 📦 Installation

```bash
cd /path/to/nextcloud/apps
git clone https://github.com/Metrat/adminoffboard.git
cd adminoffboard
composer install --no-dev
chown -R www-data:www-data .
sudo -u www-data php occ app:enable adminoffboard
