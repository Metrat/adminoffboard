Basic Usage
Web Interface
Navigate to Admin Offboard in the Nextcloud admin menu

Use the dashboard to manage users and devices

Perform bulk operations with dry-run option

OCC Commands
bash
# Offboard a user
php occ adminoffboard:offboard-user --user=username --dry-run

# Disable multiple users
php occ adminoffboard:disable-users --users=user1,user2,user3

# Delete tokens for users
php occ adminoffboard:delete-tokens --users=user1,user2

# Remote wipe a device
php occ adminoffboard:remote-wipe --user=username --device=device-id
REST API
bash
# Get users
curl -u admin:password https://nextcloud.local/index.php/apps/adminoffboard/api/v1/users

# Offboard user
curl -X POST -u admin:password \
  https://nextcloud.local/index.php/apps/adminoffboard/api/v1/users/{userId}/offboard
🏗️ Architecture
text
┌─────────────────────────────────────┐
│          Presentation Layer          │
│  ┌──────────────┐ ┌──────────────┐  │
│  │   Web UI     │ │  REST API    │  │
│  └──────────────┘ └──────────────┘  │
│  ┌──────────────┐                   │
│  │ OCC Commands │                   │
│  └──────────────┘                   │
└─────────────────────────────────────┘
                 │
┌─────────────────────────────────────┐
│           Business Layer             │
│  ┌──────────────┐ ┌──────────────┐  │
│  │  Workflow    │ │  Operations  │  │
│  └──────────────┘ └──────────────┘  │
│  ┌──────────────┐ ┌──────────────┐  │
│  │  Queue       │ │  Audit       │  │
│  └──────────────┘ └──────────────┘  │
└─────────────────────────────────────┘
                 │
┌─────────────────────────────────────┐
│            Driver Layer              │
│  ┌──────────────┐ ┌──────────────┐  │
│  │  Driver      │ │  Driver      │  │
│  │  Factory     │ │  Registry    │  │
│  └──────────────┘ └──────────────┘  │
└─────────────────────────────────────┘
                 │
┌─────────────────────────────────────┐
│           Repository Layer           │
│  ┌──────────────┐ ┌──────────────┐  │
│  │  Job Repo    │ │ Audit Repo   │  │
│  └──────────────┘ └──────────────┘  │
│  ┌──────────────┐                   │
│  │Device Repo   │                   │
│  └──────────────┘                   │
└─────────────────────────────────────┘
                 │
┌─────────────────────────────────────┐
│          Nextcloud Adapter           │
│  ┌──────────────┐ ┌──────────────┐  │
│  │  OCP API     │ │  Database    │  │
│  │  Wrappers    │ │  Wrappers    │  │
│  └──────────────┘ └──────────────┘  │
└─────────────────────────────────────┘
🧪 Development
Setup Development Environment
bash
git clone https://github.com/Metrat/adminoffboard.git
cd adminoffboard
composer install
make build
Running Tests
bash
# Run all tests
composer test

# Run with coverage
composer test:coverage

# Code style check
composer cs:check

# Static analysis
composer phpstan
composer psalm
Building
bash
# Build app archive
make archive

# Build for production
make build
📖 Documentation
User Guide

Admin Guide

API Reference

Developer Guide

Architecture Design

🤝 Contributing
Contributions are welcome! Please read our Contributing Guide before submitting pull requests.

Development Process
Fork the repository

Create a feature branch

Make your changes

Run tests and coding standards

Submit a pull request

📝 License
This project is licensed under the GNU Affero General Public License v3.0 - see the LICENSE file for details.

📞 Support
Issues: GitHub Issues

Discussions: GitHub Discussions

Email: disparam@gmail.com

🙏 Credits
Developed by Metrat

Special thanks to the Nextcloud community for their support and contributions.

⚠️ Important: This tool performs administrative actions that can affect user access and data. Always test in a staging environment first and use dry-run mode for initial testing.

Дополнительные зависимости для production (не включены, т.к. Nextcloud предоставляет их):

В Nextcloud окружении эти зависимости уже доступны:

ext-curl - для HTTP запросов

ext-gd - для работы с изображениями

ext-mbstring - для работы со строками

ext-zip - для работы с архивами

psr/log - PSR-3 логирование

psr/container - PSR-11 контейнер

doctrine/dbal - Database Abstraction Layer

symfony/event-dispatcher - Event система

Установка:

bash
composer install
Обновление зависимостей:

bash
composer update