#pragma once
#include <string>
#include <vector>
#include <mutex>

struct BillingPackage {
    int id = 0;
    std::string name;
    int duration_minutes = 0;
    std::string pc_type = "all"; // "all", "reguler", "vip", "vip++"
    long long price = 0;
    std::string description;
};

struct MenuItem {
    int id = 0;
    std::string name;
    std::string category; // "makanan", "minuman", "snack"
    long long price = 0;
    int stock = 0;
    int cart_qty = 0;
};

struct ActiveOrder {
    int id = 0;
    std::string order_code;
    std::string order_type;       // "food" or "billing"
    long long total_price = 0;
    std::string payment_method;   // "balance" or "qris"
    std::string payment_status;   // "paid", "pending"
    std::string kitchen_status;   // "waiting", "cooking", "ready", "completed"
    std::string items_summary;
    std::string created_at;
};

struct GameItem {
    int id = 0;
    std::string name;
    std::string category;
    std::string publisher;
    std::string command;
    std::string args;
    std::string size_formatted;
    std::string min_tier = "reguler"; // "reguler", "vip", "vip++"
    bool is_vault_game = false;
};

struct ClientState {
    std::mutex mtx;

    // Config
    std::string server_host = "127.0.0.1";
    int server_port = 8088;
    std::string pc_code = "PC-01";
    std::string pc_type = "reguler"; // "reguler", "vip", "vip++"
    long long hourly_rate = 5000;
    std::string specs = "";
    std::string diskless_total_games = "Memuat katalog server...";

    // Connection & Session
    bool server_online = false;
    bool is_locked = true;
    std::string session_status = "locked"; // "locked", "active"
    std::string member_name = "-";
    std::string member_username = "";
    long long member_balance = 0;
    int remaining_seconds = 0;

    // Admin message from cashier
    std::string admin_message = "";
    int last_message_id = 0;

    // Catalogs synced from Laravel
    std::vector<BillingPackage> packages;
    std::vector<MenuItem> menu_items;
    std::vector<ActiveOrder> active_orders;
    std::vector<GameItem> server_games;

    // Notification banner
    std::string toast_text = "";
    unsigned long long toast_until_tick = 0;
};
