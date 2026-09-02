# AdminOffboard

**AdminOffboard** — Nextcloud-приложение для автоматизации отключения пользователей, удаления токенов и удалённого стирания данных.

## 🇬🇧 English | 🇷🇺 Русский

---

## 🌟 Features / Возможности

### 🖥️ Console Commands / Консольные команды (6)

| Command | Description |
|---------|-------------|
| `adminoffboard:test` | Test command / Тестовая команда |
| `adminoffboard:users:disable` | Disable users (single/mass, --force) / Отключение пользователей |
| `adminoffboard:tokens:delete` | Delete tokens (single/mass, --except-current) / Удаление токенов |
| `adminoffboard:offboard` | Full offboarding (disable + tokens + wipe) / Полное отключение |
| `adminoffboard:process-queue` | Process job queue / Обработка очереди задач |
| `adminoffboard:remote-wipe` | Remote wipe devices / Удалённое стирание устройств |

### 🌐 Web Interface / Веб-интерфейс (6 pages)

| Page | Description |
|------|-------------|
| **Dashboard** | Real-time statistics / Статистика в реальном времени |
| **Offboard Users** | User table with actions / Таблица пользователей с действиями |
| **Bulk Operations** | Mass actions / Массовые операции |
| **Audit Logs** | Searchable history / История с поиском |
| **Queue Management** | Job processing / Обработка задач |
| **Settings** | App info / Информация о приложении |

### 🔒 Remote Wipe System / Система удалённого стирания

- ✅ Windows PowerShell agent / PowerShell агент для Windows
- ✅ Task Scheduler auto-install / Автоустановка через Task Scheduler
- ✅ Hidden background execution (VBS wrapper) / Скрытое фоновое выполнение
- ✅ SSL bypass for self-signed certificates / Обход SSL для самоподписанных сертификатов
- ✅ Automatic file deletion on stolen laptops / Автоудаление файлов при краже ноутбука

### 📊 Database / База данных

| Table | Description |
|-------|-------------|
| `oc_adminoffboard_audit` | Audit log / Журнал аудита |
| `oc_adminoffboard_devices` | Devices with wipe status / Устройства со статусом стирания |
| `oc_adminoffboard_jobs` | Job queue / Очередь задач |

### ✅ Tests / Тесты

- 9 PHPUnit tests / 9 PHPUnit тестов
- 14 assertions / 14 проверок

---

## 📦 Installation / Установка

### From Release / Из релиза

```bash
# Download latest release / Скачать последний релиз
wget https://github.com/Metrat/adminoffboard/releases/download/v0.3.0/adminoffboard-v0.3.0.tar.gz

# Extract to apps folder / Распаковать в папку apps
cd /var/www/nextcloud/apps
tar -xzf adminoffboard-v0.3.0.tar.gz

# Set permissions / Установить права
chown -R www-data:www-data adminoffboard

# Enable app / Включить приложение
sudo -u www-data php /var/www/nextcloud/occ app:enable adminoffboard

From Git / Из Git
bash
cd /var/www/nextcloud/apps
git clone https://github.com/Metrat/adminoffboard.git
chown -R www-data:www-data adminoffboard
sudo -u www-data php /var/www/nextcloud/occ app:enable adminoffboard

## Usage / Использование
Console / Консоль
bash
# Disable users / Отключить пользователей
sudo -u www-data php occ adminoffboard:users:disable user1 user2 --force

# Delete tokens / Удалить токены
sudo -u www-data php occ adminoffboard:tokens:delete user1 user2

# Full offboarding / Полное отключение
sudo -u www-data php occ adminoffboard:offboard user1

# Remote wipe / Удалённое стирание
sudo -u www-data php occ adminoffboard:remote-wipe user1

Web Interface / Веб-интерфейс
https://your-nextcloud.com/index.php/apps/adminoffboard/

Remote Wipe Setup / Настройка удалённого стирания
1. Deploy Script / Разместить скрипт
In web interface, click Deploy for user / В веб-интерфейсе нажмите Deploy у пользователя.

2. Install Agent / Установить агент
On user's Windows PC / На Windows-компьютере пользователя:
powershell
curl.exe -k -s "https://your-server.com/index.php/apps/adminoffboard/api/v1/wipe-agent/install-script/USERNAME" -o "$env:TEMP\install-response.json"
$json = Get-Content "$env:TEMP\install-response.json" -Raw | ConvertFrom-Json
[Text.Encoding]::UTF8.GetString([Convert]::FromBase64String($json.data.content)) | Out-File "$env:TEMP\install-wipe-agent.ps1" -Encoding UTF8
powershell -ExecutionPolicy Bypass -File "$env:TEMP\install-wipe-agent.ps1" -Username 'USERNAME'

3. Wipe / Стирание
Admin clicks Wipe / Администратор жмёт Wipe
Task Scheduler checks API every 5 min / Task Scheduler проверяет API каждые 5 минут
Files auto-delete / Файлы автоматически удаляются

🔧 Requirements / Требования
Nextcloud 29-34
PHP 8.0+
MySQL/MariaDB
Windows (для Remote Wipe агента)

📄 License
AGPL-3.0-or-later

🔗 Links / Ссылки
Releases
Issues
Latest Release

