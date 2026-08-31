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

if command -v whiptail >/dev/null 2>&1; then
    USE_WHIPTAIL=true
else
    USE_WHIPTAIL=false
fi

generate_all_users() {
    $OCC user:list --output=json 2>/dev/null | \
    python3 -c "
import json, sys
users = json.loads(sys.stdin.read())
for uid in sorted(users.keys()):
    print(uid)
" > "$ALL_USERS_FILE"
}

view_users() {
    if [ ! -f "$ALL_USERS_FILE" ] || [ ! -s "$ALL_USERS_FILE" ]; then
        generate_all_users
    fi

    if $USE_WHIPTAIL; then
        whiptail --title "AdminOffboard - Пользователи" --textbox "$ALL_USERS_FILE" 20 60 3>&1 1>&2 2>&3
    else
        echo -e "\n${CYAN}Список пользователей:${NC}"
        cat -n "$ALL_USERS_FILE"
        echo -e "${GREEN}Всего: $(wc -l < "$ALL_USERS_FILE")${NC}"
    fi
}

select_user() {
    if [ ! -f "$ALL_USERS_FILE" ] || [ ! -s "$ALL_USERS_FILE" ]; then
        generate_all_users
    fi

    if $USE_WHIPTAIL; then
        local users=()
        while IFS= read -r uid; do
            users+=("$uid" "")
        done < "$ALL_USERS_FILE"
        SELECTED_USER=$(whiptail --title "AdminOffboard" --menu "Выберите пользователя:" 20 60 10 "${users[@]}" 3>&1 1>&2 2>&3)
    else
        echo -e "\n${CYAN}Список:${NC}"
        cat -n "$ALL_USERS_FILE"
        echo -n -e "${YELLOW}Имя пользователя: ${NC}"
        read SELECTED_USER
    fi
}

show_main_menu() {
    while true; do
        if $USE_WHIPTAIL; then
            CHOICE=$(whiptail --title "AdminOffboard v0.2.3" --menu "Выберите действие:" 16 60 8 \
                "1" "Список пользователей" \
                "2" "Offboard пользователя" \
                "3" "Disable пользователя" \
                "4" "Delete tokens" \
                "5" "Remote Wipe" \
                "6" "Process queue" \
                "7" "Test command" \
                "0" "Выход" 3>&1 1>&2 2>&3)
        else
            echo -e "\n${BLUE}=== AdminOffboard v0.2.3 ===${NC}"
            echo -e "${CYAN}1${NC}. Список пользователей"
            echo -e "${CYAN}2${NC}. Offboard пользователя"
            echo -e "${CYAN}3${NC}. Disable пользователя"
            echo -e "${CYAN}4${NC}. Delete tokens"
            echo -e "${CYAN}5${NC}. Remote Wipe"
            echo -e "${CYAN}6${NC}. Process queue"
            echo -e "${CYAN}7${NC}. Test command"
            echo -e "${CYAN}0${NC}. Выход"
            echo -n -e "${YELLOW}Выбор: ${NC}"
            read CHOICE
        fi

        case $CHOICE in
            1) view_users ;;
            2) select_user; [ -n "$SELECTED_USER" ] && $OCC adminoffboard:offboard --user="$SELECTED_USER" --disable --delete-tokens --force ;;
            3) select_user; [ -n "$SELECTED_USER" ] && $OCC adminoffboard:users:disable --user="$SELECTED_USER" --force ;;
            4) select_user; [ -n "$SELECTED_USER" ] && $OCC adminoffboard:tokens:delete --user="$SELECTED_USER" ;;
            5) select_user; [ -n "$SELECTED_USER" ] && $OCC adminoffboard:remote-wipe --user="$SELECTED_USER" --all ;;
            6) $OCC adminoffboard:process-queue ;;
            7) $OCC adminoffboard:test ;;
            0) echo -e "${GREEN}Выход.${NC}"; exit 0 ;;
            *) $USE_WHIPTAIL && whiptail --msgbox "Неверный выбор" 8 40 || echo -e "${RED}Неверный выбор${NC}" ;;
        esac
    done
}

show_main_menu
