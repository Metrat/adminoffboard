#!/bin/bash

APP_DIR="/var/www/nextcloud"
ALL_USERS_FILE="/tmp/allusers.txt"
OCC="sudo -u www-data php $APP_DIR/occ"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

generate_all_users() {
    echo -e "\n${YELLOW}⏳ Генерация списка пользователей...${NC}"
    $OCC user:list --output=json 2>/dev/null | \
    python3 -c "
import json, sys
data = sys.stdin.read()
users = json.loads(data)
for uid in sorted(users.keys()):
    print(uid)
" > "$ALL_USERS_FILE"
    
    local count=$(wc -l < "$ALL_USERS_FILE")
    echo -e "${GREEN}✅ Файл создан: $ALL_USERS_FILE${NC}"
    echo -e "${GREEN}📊 Всего пользователей: $count${NC}"
}

view_users() {
    if [ ! -f "$ALL_USERS_FILE" ]; then
        generate_all_users
    fi
    echo -e "\n${CYAN}Список всех пользователей:${NC}"
    cat -n "$ALL_USERS_FILE"
    echo -e "${GREEN}Всего: $(wc -l < "$ALL_USERS_FILE")${NC}"
}

select_user() {
    if [ ! -f "$ALL_USERS_FILE" ] || [ ! -s "$ALL_USERS_FILE" ]; then
        generate_all_users
    fi
    view_users
    echo -e "\n${YELLOW}0 - Отмена${NC}"
    read -p "Введите номер: " choice
    [ "$choice" = "0" ] && return 1
    USER=$(sed -n "${choice}p" "$ALL_USERS_FILE")
    [ -z "$USER" ] && echo -e "${RED}❌ Неверный выбор!${NC}" && return 1
    echo -e "${GREEN}✅ Выбран: $USER${NC}"
    return 0
}

create_operation_file() {
    generate_all_users
    local total=$(wc -l < "$ALL_USERS_FILE")
    local default_file="/tmp/adminoffboard_operation.txt"
    read -p "Файл для операции [${default_file}]: " file_path
    file_path=${file_path:-$default_file}
    
    echo -e "\n${YELLOW}1) Использовать ВСЕХ пользователей (${GREEN}$total${YELLOW})${NC}"
    echo -e "${YELLOW}2) Редактировать список${NC}"
    echo -e "${YELLOW}0) Отмена${NC}"
    read -p "Выберите: " sub
    
    case $sub in
        1) cp "$ALL_USERS_FILE" "$file_path" ;;
        2)
            cp "$ALL_USERS_FILE" "$file_path"
            echo -e "\n${YELLOW}Удалите пользователей, которых НЕ нужно обрабатывать${NC}"
            read -p "Нажмите Enter..."
            nano "$file_path"
            ;;
        *) echo -e "${RED}Отмена${NC}"; return 1 ;;
    esac
    
    if [ ! -s "$file_path" ]; then
        echo -e "${RED}❌ Файл пуст!${NC}"
        return 1
    fi
    
    echo -e "\n${GREEN}✅ Файл готов: $file_path${NC}"
    echo -e "${BLUE}Будет обработано: $(wc -l < "$file_path") пользователей${NC}"
    head -10 "$file_path"
    [ $(wc -l < "$file_path") -gt 10 ] && echo "..."
    return 0
}

confirm() {
    echo -e "\n${RED}⚠️  $1${NC}"
    echo -e "${RED}⚠️  Затронуто: $(wc -l < "$2") пользователей${NC}"
    echo -e "${YELLOW}Для подтверждения введите YES${NC}"
    read -p "> " c
    [ "$c" = "YES" ] || [ "$c" = "yes" ]
}

# Меню
single_disable() {
    clear
    echo -e "${BLUE}=== ОДИНОЧНОЕ ОТКЛЮЧЕНИЕ ===${NC}"
    select_user || return
    echo -e "\n1) Dry-run\n2) Отключить"
    read -p "Режим: " mode
    case $mode in
        1) $OCC adminoffboard:users:disable --user="$USER" --dry-run ;;
        2) 
            echo -e "${RED}Отключить $USER?${NC}"
            echo -e "${YELLOW}Введите YES${NC}"; read -p "> " c
            [ "$c" = "YES" ] || [ "$c" = "yes" ] && $OCC adminoffboard:users:disable --user="$USER" --force
            ;;
    esac
}

mass_disable() {
    clear
    echo -e "${BLUE}=== МАССОВОЕ ОТКЛЮЧЕНИЕ ===${NC}"
    create_operation_file || return
    echo -e "\n1) Dry-run\n2) Отключить"
    read -p "Режим: " mode
    case $mode in
        1) $OCC adminoffboard:users:disable --file="$file_path" --dry-run ;;
        2) confirm "Отключить пользователей?" "$file_path" && $OCC adminoffboard:users:disable --file="$file_path" --force ;;
    esac
}

tokens_menu() {
    clear
    echo -e "${BLUE}=== УДАЛЕНИЕ ТОКЕНОВ ===${NC}"
    echo -e "1) Одному\n2) Массово"
    read -p "Выберите: " sub
    case $sub in
        1)
            select_user || return
            echo -e "${YELLOW}Введите YES${NC}"; read -p "> " c
            [ "$c" = "YES" ] || [ "$c" = "yes" ] && $OCC adminoffboard:tokens:delete --user="$USER" --all
            ;;
        2)
            create_operation_file || return
            confirm "Удалить токены?" "$file_path" || return
            while IFS= read -r u; do
                [ -z "$u" ] && continue
                echo -e "${YELLOW}$u${NC}"
                $OCC adminoffboard:tokens:delete --user="$u" --all
            done < "$file_path"
            echo -e "${GREEN}✅ Готово!${NC}"
            ;;
    esac
}

offboard_menu() {
    clear
    echo -e "${BLUE}=== OFFBOARD ===${NC}"
    echo -e "1) Одного\n2) Массово"
    read -p "Выберите: " sub
    case $sub in
        1)
            select_user || return
            echo -e "${RED}Отключение + токены${NC}"
            echo -e "${YELLOW}Введите YES${NC}"; read -p "> " c
            [ "$c" = "YES" ] || [ "$c" = "yes" ] && $OCC adminoffboard:offboard --user="$USER" --disable --delete-tokens --force
            ;;
        2)
            create_operation_file || return
            confirm "Offboard?" "$file_path" || return
            while IFS= read -r u; do
                [ -z "$u" ] && continue
                echo -e "${YELLOW}$u${NC}"
                $OCC adminoffboard:offboard --user="$u" --disable --delete-tokens --force
            done < "$file_path"
            echo -e "${GREEN}✅ Готово!${NC}"
            ;;
    esac
}

enable_menu() {
    clear
    echo -e "${BLUE}=== ВКЛЮЧЕНИЕ ===${NC}"
    echo -e "1) Одного\n2) Массово"
    read -p "Выберите: " sub
    case $sub in
        1)
            select_user || return
            echo -e "${YELLOW}Введите YES${NC}"; read -p "> " c
            [ "$c" = "YES" ] || [ "$c" = "yes" ] && $OCC user:enable "$USER"
            ;;
        2)
            create_operation_file || return
            confirm "Включить?" "$file_path" || return
            while IFS= read -r u; do
                [ -z "$u" ] && continue
                echo -e "${YELLOW}$u${NC}"
                $OCC user:enable "$u"
            done < "$file_path"
            echo -e "${GREEN}✅ Готово!${NC}"
            ;;
    esac
}

# Главное меню
generate_all_users

while true; do
    clear
    echo -e "${CYAN}╔════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║   AdminOffboard v0.1.6            ║${NC}"
    echo -e "${CYAN}║   Пользователей: $(wc -l < "$ALL_USERS_FILE")               ║${NC}"
    echo -e "${CYAN}╠════════════════════════════════════╣${NC}"
    echo -e "${CYAN}║${NC} 1. Одиночное отключение         ${CYAN}║${NC}"
    echo -e "${CYAN}║${NC} 2. Массовое отключение          ${CYAN}║${NC}"
    echo -e "${CYAN}║${NC} 3. Удаление токенов             ${CYAN}║${NC}"
    echo -e "${CYAN}║${NC} 4. Полный offboard              ${CYAN}║${NC}"
    echo -e "${CYAN}║${NC} 5. Включение пользователей      ${CYAN}║${NC}"
    echo -e "${CYAN}║${NC} 6. Обновить список              ${CYAN}║${NC}"
    echo -e "${CYAN}║${NC} 7. Просмотр списка              ${CYAN}║${NC}"
    echo -e "${CYAN}║${NC} 8. Тест приложения              ${CYAN}║${NC}"
    echo -e "${CYAN}║${NC} 0. Выход                        ${CYAN}║${NC}"
    echo -e "${CYAN}╚════════════════════════════════════╝${NC}"
    read -p "Выберите: " choice
    
    case $choice in
        1) single_disable ;;
        2) mass_disable ;;
        3) tokens_menu ;;
        4) offboard_menu ;;
        5) enable_menu ;;
        6) generate_all_users ;;
        7) view_users; read -p "Enter..." ;;
        8) $OCC adminoffboard:test ;;
        0) echo -e "${GREEN}До свидания!${NC}"; exit 0 ;;
    esac
    
    [ "$choice" != "0" ] && read -p "Нажмите Enter..."
done
