#ifndef UNICODE
#define UNICODE
#endif
#ifndef _UNICODE
#define _UNICODE
#endif

#include <windows.h>
#include <windowsx.h>
#include <gdiplus.h>
#include <string>
#include <vector>
#include <sstream>
#include <iomanip>
#include <thread>
#include <atomic>
#include <cmath>

#include "models.hpp"
#include "json_mini.hpp"
#include "network.hpp"

using namespace Gdiplus;

// Global Application State
ClientState g_state;
std::atomic<bool> g_running{true};
HWND g_hwnd = nullptr;

// UI State
int g_active_tab = 0; // 0: Billing, 1: Food Menu, 2: Kitchen Queue, 3: Settings
std::string g_food_category_filter = "all"; // "all", "makanan", "minuman", "snack"
bool g_fullscreen_lock_mode = false; // Toggleable with F11 so user can test comfortably

// Form Inputs
int g_focused_input = 0; // 0: none, 1: username, 2: password, 3: food_notes, 4: server_host
std::string g_input_username = "sultan01";
std::string g_input_password = "password";
std::string g_input_food_notes = "";

// QRIS Modal State
bool g_show_qris_modal = false;
std::string g_qris_order_code = "";
std::string g_qris_title = "";
long long g_qris_amount = 0;
std::string g_qris_type = ""; // "billing" or "food"
int g_qris_tx_id = 0;

// Admin Message Modal
bool g_show_admin_modal = false;
std::string g_admin_modal_text = "";

// Interactive Hit Rects
struct HitZone {
    RECT rc;
    std::string action;
    int param_id = 0;
};
std::vector<HitZone> g_hit_zones;

void add_hit_zone(int x, int y, int w, int h, const std::string& action, int param_id = 0) {
    RECT r = { x, y, x + w, y + h };
    g_hit_zones.push_back({ r, action, param_id });
}

void show_toast(const std::string& msg, int duration_ms = 3500) {
    std::lock_guard<std::mutex> lock(g_state.mtx);
    g_state.toast_text = msg;
    g_state.toast_until_tick = GetTickCount64() + duration_ms;
}

std::string format_rupiah(long long amount) {
    std::string s = std::to_string(std::abs(amount));
    std::string out;
    int count = 0;
    for (int i = (int)s.size() - 1; i >= 0; --i) {
        if (count > 0 && count % 3 == 0) out = "." + out;
        out = s[i] + out;
        count++;
    }
    return (amount < 0 ? "-Rp " : "Rp ") + out;
}

std::string format_timer(int total_seconds) {
    if (total_seconds <= 0) return "00:00:00";
    int h = total_seconds / 3600;
    int m = (total_seconds % 3600) / 60;
    int s = total_seconds % 60;
    std::ostringstream oss;
    oss << std::setfill('0') << std::setw(2) << h << ":"
        << std::setfill('0') << std::setw(2) << m << ":"
        << std::setfill('0') << std::setw(2) << s;
    return oss.str();
}

// Network Sync Functions
void sync_catalog_from_server() {
    std::string host;
    int port;
    std::string pc_code;
    {
        std::lock_guard<std::mutex> lock(g_state.mtx);
        host = g_state.server_host;
        port = g_state.server_port;
        pc_code = g_state.pc_code;
    }

    HttpResponse res = http_request(host, port, "GET", "/api/client/catalog?pc_code=" + pc_code);
    if (!res.ok) return;

    JsonValue root = JsonParser::parse(res.body);
    if (root.type != JsonValue::OBJECT) return;

    std::lock_guard<std::mutex> lock(g_state.mtx);

    // Parse packages
    if (root.has("packages") && root["packages"].type == JsonValue::ARRAY) {
        g_state.packages.clear();
        for (const auto& item : root["packages"].arr_val) {
            BillingPackage pkg;
            pkg.id = (int)item["id"].as_int();
            pkg.name = item["name"].as_str();
            pkg.duration_minutes = (int)item["duration_minutes"].as_int();
            pkg.price = item["price"].as_int();
            pkg.description = item["description"].as_str();
            g_state.packages.push_back(pkg);
        }
    }

    // Parse menu items while preserving current cart quantities
    if (root.has("menus") && root["menus"].type == JsonValue::ARRAY) {
        std::map<int, int> old_cart;
        for (const auto& m : g_state.menu_items) {
            if (m.cart_qty > 0) old_cart[m.id] = m.cart_qty;
        }
        g_state.menu_items.clear();
        for (const auto& item : root["menus"].arr_val) {
            MenuItem m;
            m.id = (int)item["id"].as_int();
            m.name = item["name"].as_str();
            m.category = item["category"].as_str();
            m.price = item["price"].as_int();
            m.stock = (int)item["stock"].as_int();
            if (old_cart.count(m.id)) m.cart_qty = old_cart[m.id];
            g_state.menu_items.push_back(m);
        }
    }
}

void sync_heartbeat() {
    std::string host;
    int port;
    std::string pc_code;
    {
        std::lock_guard<std::mutex> lock(g_state.mtx);
        host = g_state.server_host;
        port = g_state.server_port;
        pc_code = g_state.pc_code;
    }

    std::string body = "{\"pc_code\":\"" + json_escape(pc_code) + "\"}";
    HttpResponse res = http_request(host, port, "POST", "/api/client/heartbeat", body);
    if (!res.ok) {
        std::lock_guard<std::mutex> lock(g_state.mtx);
        g_state.server_online = false;
        return;
    }

    JsonValue root = JsonParser::parse(res.body);
    if (root.type != JsonValue::OBJECT) return;

    bool trigger_repaint = false;
    {
        std::lock_guard<std::mutex> lock(g_state.mtx);
        g_state.server_online = true;
        bool was_locked = g_state.is_locked;
        g_state.is_locked = root["is_locked"].as_bool(true);
        g_state.session_status = root["status"].as_str("locked");
        g_state.remaining_seconds = (int)root["remaining_seconds"].as_int(0);
        g_state.member_name = root["member_name"].as_str("-");
        g_state.member_username = root["member_username"].as_str("");
        g_state.member_balance = root["member_balance"].as_int(0);

        std::string msg = root["admin_message"].as_str("");
        int msg_id = (int)root["message_id"].as_int(0);
        if (!msg.empty() && msg_id > g_state.last_message_id) {
            g_state.last_message_id = msg_id;
            g_admin_modal_text = msg;
            g_show_admin_modal = true;
        }

        // Active orders
        if (g_state.is_locked) {
            g_state.active_orders.clear();
        } else if (root.has("orders") && root["orders"].type == JsonValue::ARRAY) {
            g_state.active_orders.clear();
            for (const auto& o : root["orders"].arr_val) {
                ActiveOrder ao;
                ao.id = (int)o["id"].as_int();
                ao.order_code = o["order_code"].as_str();
                ao.order_type = o["order_type"].as_str("food");
                ao.total_price = o["total_price"].as_int();
                ao.payment_method = o["payment_method"].as_str();
                ao.payment_status = o["payment_status"].as_str();
                ao.kitchen_status = o["kitchen_status"].as_str();
                ao.items_summary = o["items_summary"].as_str();
                ao.created_at = o["created_at"].as_str();
                g_state.active_orders.push_back(ao);

                // If currently showing QRIS modal for this order and it is now paid, close modal!
                if (g_show_qris_modal && ao.order_code == g_qris_order_code && ao.payment_status == "paid") {
                    g_show_qris_modal = false;
                    g_state.toast_text = "Pembayaran QRIS (" + ao.order_code + ") Berhasil Dikonfirmasi!";
                    g_state.toast_until_tick = GetTickCount64() + 4500;
                }
            }
        }

        if (was_locked != g_state.is_locked) {
            trigger_repaint = true;
        }
    }

    if (g_hwnd) InvalidateRect(g_hwnd, nullptr, FALSE);
}

void perform_login() {
    std::string host, pc_code, user, pass;
    int port;
    {
        std::lock_guard<std::mutex> lock(g_state.mtx);
        host = g_state.server_host;
        port = g_state.server_port;
        pc_code = g_state.pc_code;
        user = g_input_username;
        pass = g_input_password;
    }

    if (user.empty()) {
        show_toast("Username tidak boleh kosong!");
        return;
    }

    std::thread([host, port, pc_code, user, pass]() {
        std::string body = "{\"pc_code\":\"" + json_escape(pc_code) +
                           "\",\"username\":\"" + json_escape(user) +
                           "\",\"password\":\"" + json_escape(pass) + "\"}";
        HttpResponse res = http_request(host, port, "POST", "/api/client/login", body);
        JsonValue root = JsonParser::parse(res.body);
        if (res.ok && root["success"].as_bool()) {
            {
                std::lock_guard<std::mutex> lock(g_state.mtx);
                g_state.active_orders.clear();
                for (auto& m : g_state.menu_items) m.cart_qty = 0;
                g_input_password = "";
            }
            show_toast("Login berhasil! Selamat bermain di " + pc_code);
            sync_catalog_from_server();
            sync_heartbeat();
        } else {
            std::string err = root["message"].as_str("Gagal login. Periksa username/password atau koneksi server.");
            show_toast(err, 4000);
            if (g_hwnd) InvalidateRect(g_hwnd, nullptr, FALSE);
        }
    }).detach();
}

void perform_logout() {
    std::string host, pc_code;
    int port;
    {
        std::lock_guard<std::mutex> lock(g_state.mtx);
        host = g_state.server_host;
        port = g_state.server_port;
        pc_code = g_state.pc_code;
        g_state.active_orders.clear();
        for (auto& m : g_state.menu_items) m.cart_qty = 0;
        g_state.member_name = "-";
        g_state.member_username = "";
        g_state.member_balance = 0;
        g_state.remaining_seconds = 0;
        g_state.is_locked = true;
    }

    std::thread([host, port, pc_code]() {
        std::string body = "{\"pc_code\":\"" + json_escape(pc_code) + "\"}";
        http_request(host, port, "POST", "/api/client/logout", body);
        show_toast("Sesi selesai. Sisa waktu tersimpan di akun member.");
        sync_heartbeat();
    }).detach();
}

void perform_buy_billing(int package_id, const std::string& payment_method) {
    std::string host, pc_code;
    int port;
    {
        std::lock_guard<std::mutex> lock(g_state.mtx);
        host = g_state.server_host;
        port = g_state.server_port;
        pc_code = g_state.pc_code;
    }

    std::thread([host, port, pc_code, package_id, payment_method]() {
        std::ostringstream oss;
        oss << "{\"pc_code\":\"" << json_escape(pc_code)
            << "\",\"package_id\":" << package_id
            << ",\"payment_method\":\"" << json_escape(payment_method) << "\"}";

        HttpResponse res = http_request(host, port, "POST", "/api/client/buy-billing", oss.str());
        JsonValue root = JsonParser::parse(res.body);
        if (res.ok && root["success"].as_bool()) {
            if (payment_method == "qris") {
                std::lock_guard<std::mutex> lock(g_state.mtx);
                g_qris_order_code = root["order_code"].as_str("QR-BILL");
                g_qris_title = root["package_name"].as_str("Tambah Paket Billing");
                g_qris_amount = root["amount"].as_int(0);
                g_qris_type = "billing";
                g_show_qris_modal = true;
            } else {
                show_toast(root["message"].as_str("Paket billing berhasil ditambahkan!"), 4000);
            }
            sync_heartbeat();
        } else {
            show_toast(root["message"].as_str("Gagal membeli paket billing!"), 4000);
            if (g_hwnd) InvalidateRect(g_hwnd, nullptr, FALSE);
        }
    }).detach();
}

void perform_order_food(const std::string& payment_method) {
    std::string host, pc_code, notes;
    int port;
    std::ostringstream items_json;
    int total_items = 0;

    {
        std::lock_guard<std::mutex> lock(g_state.mtx);
        host = g_state.server_host;
        port = g_state.server_port;
        pc_code = g_state.pc_code;
        notes = g_input_food_notes;

        items_json << "[";
        bool first = true;
        for (const auto& m : g_state.menu_items) {
            if (m.cart_qty > 0) {
                if (!first) items_json << ",";
                items_json << "{\"menu_id\":" << m.id << ",\"qty\":" << m.cart_qty << "}";
                first = false;
                total_items += m.cart_qty;
            }
        }
        items_json << "]";
    }

    if (total_items == 0) {
        show_toast("Pilih minimal 1 makanan/minuman terlebih dahulu!");
        if (g_hwnd) InvalidateRect(g_hwnd, nullptr, FALSE);
        return;
    }

    std::string items_str = items_json.str();
    std::thread([host, port, pc_code, payment_method, notes, items_str]() {
        std::ostringstream oss;
        oss << "{\"pc_code\":\"" << json_escape(pc_code)
            << "\",\"payment_method\":\"" << json_escape(payment_method)
            << "\",\"notes\":\"" << json_escape(notes)
            << "\",\"items\":" << items_str << "}";

        HttpResponse res = http_request(host, port, "POST", "/api/client/order-food", oss.str());
        JsonValue root = JsonParser::parse(res.body);
        if (res.ok && root["success"].as_bool()) {
            {
                std::lock_guard<std::mutex> lock(g_state.mtx);
                for (auto& m : g_state.menu_items) m.cart_qty = 0;
                g_input_food_notes = "";
                if (payment_method == "qris") {
                    g_qris_order_code = root["order_code"].as_str("QR-FOOD");
                    g_qris_title = "Pesanan Makanan & Minuman (" + g_qris_order_code + ")";
                    g_qris_amount = root["amount"].as_int(0);
                    g_qris_type = "food";
                    g_show_qris_modal = true;
                }
            }
            if (payment_method != "qris") {
                show_toast("Pesanan berhasil dikirim ke Antrian Dapur!", 4000);
                g_active_tab = 2; // Switch to Kitchen Queue tab
            }
            sync_catalog_from_server();
            sync_heartbeat();
        } else {
            show_toast(root["message"].as_str("Gagal mengirim pesanan makanan!"), 4000);
            if (g_hwnd) InvalidateRect(g_hwnd, nullptr, FALSE);
        }
    }).detach();
}

void simulate_qris_paid() {
    std::string host, order_code;
    int port;
    {
        std::lock_guard<std::mutex> lock(g_state.mtx);
        host = g_state.server_host;
        port = g_state.server_port;
        order_code = g_qris_order_code;
    }
    if (order_code.empty()) return;

    std::thread([host, port, order_code]() {
        std::string body = "{\"order_code\":\"" + json_escape(order_code) + "\"}";
        HttpResponse res = http_request(host, port, "POST", "/api/client/confirm-qris", body);
        JsonValue root = JsonParser::parse(res.body);
        if (res.ok && root["success"].as_bool()) {
            g_show_qris_modal = false;
            show_toast("Pembayaran QRIS berhasil! Pesanan/Billing telah aktif.", 4000);
            sync_heartbeat();
        } else {
            show_toast(root["message"].as_str("Gagal konfirmasi QRIS"), 3500);
        }
    }).detach();
}

// GDI+ Drawing Helpers
void fill_round_rect(Graphics& g, Brush* brush, int x, int y, int w, int h, int radius) {
    GraphicsPath path;
    int d = radius * 2;
    if (d > w) d = w;
    if (d > h) d = h;
    path.AddArc(x, y, d, d, 180, 90);
    path.AddArc(x + w - d, y, d, d, 270, 90);
    path.AddArc(x + w - d, y + h - d, d, d, 0, 90);
    path.AddArc(x, y + h - d, d, d, 90, 90);
    path.CloseFigure();
    g.FillPath(brush, &path);
}

void draw_round_rect(Graphics& g, Pen* pen, int x, int y, int w, int h, int radius) {
    GraphicsPath path;
    int d = radius * 2;
    if (d > w) d = w;
    if (d > h) d = h;
    path.AddArc(x, y, d, d, 180, 90);
    path.AddArc(x + w - d, y, d, d, 270, 90);
    path.AddArc(x + w - d, y + h - d, d, d, 0, 90);
    path.AddArc(x, y + h - d, d, d, 90, 90);
    path.CloseFigure();
    g.DrawPath(pen, &path);
}

void draw_text(Graphics& g, const std::string& text, int x, int y, int w, int h,
               float font_size, Color color, int font_style = FontStyleRegular,
               StringAlignment align = StringAlignmentNear, StringAlignment valign = StringAlignmentCenter) {
    FontFamily fontFamily(L"Segoe UI");
    Font font(&fontFamily, font_size, font_style, UnitPixel);
    SolidBrush brush(color);
    RectF layoutRect((REAL)x, (REAL)y, (REAL)w, (REAL)h);
    StringFormat format;
    format.SetAlignment(align);
    format.SetLineAlignment(valign);
    format.SetTrimming(StringTrimmingEllipsisCharacter);
    std::wstring wtext = to_wstring_utf8(text);
    g.DrawString(wtext.c_str(), -1, &font, layoutRect, &format, &brush);
}

void draw_button(Graphics& g, int x, int y, int w, int h, const std::string& label,
                 Color bg_color, Color text_color, const std::string& action, int param_id = 0,
                 int radius = 8, float font_size = 13.5f) {
    SolidBrush bg(bg_color);
    fill_round_rect(g, &bg, x, y, w, h, radius);
    draw_text(g, label, x, y, w, h, font_size, text_color, FontStyleBold, StringAlignmentCenter, StringAlignmentCenter);
    add_hit_zone(x, y, w, h, action, param_id);
}

// Draw Simulated Scannable QR Code Matrix
void draw_qr_matrix(Graphics& g, int x, int y, int size, const std::string& seed_str) {
    SolidBrush whiteBrush(Color(255, 255, 255, 255));
    SolidBrush blackBrush(Color(255, 15, 23, 42));
    fill_round_rect(g, &whiteBrush, x, y, size, size, 12);

    int grid = 21;
    int padding = 14;
    int cell = (size - padding * 2) / grid;
    int start_x = x + (size - cell * grid) / 2;
    int start_y = y + (size - cell * grid) / 2;

    unsigned int hash = 2166136261u;
    for (char c : seed_str) {
        hash ^= (unsigned char)c;
        hash *= 16777619u;
    }

    auto is_finder = [](int r, int c) {
        if (r < 7 && c < 7) return true;
        if (r < 7 && c >= 14) return true;
        if (r >= 14 && c < 7) return true;
        return false;
    };

    auto finder_pixel = [](int r, int c, int r0, int c0) {
        int dr = r - r0;
        int dc = c - c0;
        if (dr == 0 || dr == 6 || dc == 0 || dc == 6) return true;
        if (dr >= 2 && dr <= 4 && dc >= 2 && dc <= 4) return true;
        return false;
    };

    for (int r = 0; r < grid; ++r) {
        for (int c = 0; c < grid; ++c) {
            bool dark = false;
            if (r < 7 && c < 7) dark = finder_pixel(r, c, 0, 0);
            else if (r < 7 && c >= 14) dark = finder_pixel(r, c, 0, 14);
            else if (r >= 14 && c < 7) dark = finder_pixel(r, c, 14, 0);
            else if (!is_finder(r, c)) {
                unsigned int v = (hash ^ ((r * 31 + c * 17) * 2654435761u));
                dark = ((v >> ((r + c) % 16)) & 1) != 0;
            }
            if (dark) {
                g.FillRectangle(&blackBrush, start_x + c * cell, start_y + r * cell, cell, cell);
            }
        }
    }
}

void render_ui(HDC hdc, int width, int height) {
    Bitmap buffer(width, height, PixelFormat32bppARGB);
    Graphics g(&buffer);
    g.SetSmoothingMode(SmoothingModeAntiAlias);
    g.SetTextRenderingHint(TextRenderingHintClearTypeGridFit);

    g_hit_zones.clear();

    // Copy state safely
    bool is_locked, server_online;
    std::string pc_code, member_name, member_user, server_host;
    long long member_balance;
    int remaining_seconds, server_port;
    std::vector<BillingPackage> packages;
    std::vector<MenuItem> menu_items;
    std::vector<ActiveOrder> active_orders;
    std::string toast_text;
    unsigned long long toast_until;

    {
        std::lock_guard<std::mutex> lock(g_state.mtx);
        is_locked = g_state.is_locked;
        server_online = g_state.server_online;
        pc_code = g_state.pc_code;
        member_name = g_state.member_name;
        member_user = g_state.member_username;
        member_balance = g_state.member_balance;
        remaining_seconds = g_state.remaining_seconds;
        server_host = g_state.server_host;
        server_port = g_state.server_port;
        packages = g_state.packages;
        menu_items = g_state.menu_items;
        active_orders = g_state.active_orders;
        toast_text = g_state.toast_text;
        toast_until = g_state.toast_until_tick;
    }

    // Background Gradient (Deep Ukikura Dark Cyber Theme)
    LinearGradientBrush bgBrush(
        Point(0, 0), Point(width, height),
        Color(255, 9, 13, 24), Color(255, 17, 24, 39)
    );
    g.FillRectangle(&bgBrush, 0, 0, width, height);

    // Subtle decorative top glow bar
    LinearGradientBrush accentBar(
        Point(0, 0), Point(width, 0),
        Color(255, 6, 182, 212), Color(255, 99, 102, 241)
    );
    g.FillRectangle(&accentBar, 0, 0, width, 4);

    if (is_locked) {
        // =====================================================
        // MODE 1: LOCK SCREEN (UKIKURA NATIVE iCAFE LOCKSCREEN)
        // =====================================================
        int pad = 40;
        int left_w = (width - pad * 3) * 55 / 100;
        int right_w = (width - pad * 3) - left_w;
        int right_x = pad * 2 + left_w;

        // Top Bar Status
        SolidBrush cardBg(Color(235, 17, 24, 39));
        Pen borderPen(Color(100, 51, 65, 85), 1.0f);

        // Station Hero Card (Left)
        fill_round_rect(g, &cardBg, pad, pad, left_w, height - pad * 2, 16);
        draw_round_rect(g, &borderPen, pad, pad, left_w, height - pad * 2, 16);

        // Station Badge
        SolidBrush cyanBadge(Color(40, 6, 182, 212));
        fill_round_rect(g, &cyanBadge, pad + 30, pad + 28, 170, 34, 8);
        draw_text(g, "UKIKURA iCAFE PRO", pad + 42, pad + 28, 150, 34, 12.0f, Color(255, 34, 211, 238), FontStyleBold);

        // Server Status Badge
        Color statusCol = server_online ? Color(255, 16, 185, 129) : Color(255, 239, 68, 68);
        SolidBrush statusBg(server_online ? Color(35, 16, 185, 129) : Color(35, 239, 68, 68));
        fill_round_rect(g, &statusBg, pad + 215, pad + 28, 220, 34, 8);
        draw_text(g, server_online ? "SERVER KASIR ONLINE" : "SERVER KASIR OFFLINE",
                  pad + 225, pad + 28, 200, 34, 11.5f, statusCol, FontStyleBold);

        // Big PC Number Display
        draw_text(g, pc_code, pad + 30, pad + 75, 360, 85, 64.0f, Color(255, 248, 250, 252), FontStyleBold);
        draw_text(g, "TERMINAL LOCKED — SILAKAN LOGIN MEMBER ATAU HUBUNGI KASIR",
                  pad + 32, pad + 162, left_w - 60, 28, 13.0f, Color(255, 148, 163, 184), FontStyleBold);

        // Switch PC Number Quick Buttons (for testing multiple PCs easily)
        draw_button(g, pad + left_w - 230, pad + 95, 95, 34, "< PC Sebelumnya", Color(255, 30, 41, 59), Color(255, 203, 213, 225), "prev_pc", 0, 6, 11.0f);
        draw_button(g, pad + left_w - 125, pad + 95, 95, 34, "PC Berikutnya >", Color(255, 30, 41, 59), Color(255, 203, 213, 225), "next_pc", 0, 6, 11.0f);

        // Packages Preview List on Lockscreen
        draw_text(g, "DAFTAR PAKET BILLING TERSEDIA:", pad + 32, pad + 215, 350, 26, 13.0f, Color(255, 56, 189, 248), FontStyleBold);

        int pkg_y = pad + 250;
        int shown = 0;
        for (const auto& pkg : packages) {
            if (shown >= 4) break;
            SolidBrush pkgBg(Color(180, 30, 41, 59));
            fill_round_rect(g, &pkgBg, pad + 30, pkg_y, left_w - 60, 64, 10);
            draw_round_rect(g, &borderPen, pad + 30, pkg_y, left_w - 60, 64, 10);

            draw_text(g, pkg.name, pad + 48, pkg_y + 10, 260, 24, 15.0f, Color(255, 241, 245, 249), FontStyleBold);
            draw_text(g, std::to_string(pkg.duration_minutes) + " Menit  •  " + pkg.description,
                      pad + 48, pkg_y + 34, 340, 20, 12.0f, Color(255, 148, 163, 184));
            draw_text(g, format_rupiah(pkg.price), pad + left_w - 210, pkg_y, 160, 64, 16.0f,
                      Color(255, 52, 211, 153), FontStyleBold, StringAlignmentFar, StringAlignmentCenter);

            pkg_y += 76;
            shown++;
        }
        if (packages.empty()) {
            draw_text(g, "Menunggu sinkronisasi katalog paket dari Server Laravel...",
                      pad + 32, pkg_y, left_w - 64, 40, 13.0f, Color(255, 100, 116, 139));
        }

        // Bottom info on left card
        draw_text(g, "Tips Testing: Klik [Isi Demo Member] di kanan untuk masuk instan, atau tekan F11 untuk mode layar penuh.",
                  pad + 32, height - pad - 42, left_w - 64, 28, 11.5f, Color(255, 100, 116, 139));

        // Right Card: Login Member Box
        fill_round_rect(g, &cardBg, right_x, pad, right_w, height - pad * 2, 16);
        draw_round_rect(g, &borderPen, right_x, pad, right_w, height - pad * 2, 16);

        int rx = right_x + 36;
        int rw = right_w - 72;

        draw_text(g, "LOGIN MEMBER", rx, pad + 36, rw, 34, 24.0f, Color(255, 248, 250, 252), FontStyleBold);
        draw_text(g, "Masuk dengan akun member untuk menggunakan billing, top-up waktu, atau pesan makanan dari PC.",
                  rx, pad + 74, rw, 42, 12.5f, Color(255, 148, 163, 184));

        // Demo autofill buttons
        draw_button(g, rx, pad + 130, (rw - 10) / 2, 34, "Isi Akun: sultan01", Color(255, 30, 41, 59), Color(255, 56, 189, 248), "fill_demo_1", 0, 8, 12.0f);
        draw_button(g, rx + (rw - 10) / 2 + 10, pad + 130, (rw - 10) / 2, 34, "Isi Akun: gamer02", Color(255, 30, 41, 59), Color(255, 167, 139, 250), "fill_demo_2", 0, 8, 12.0f);

        // Input Username
        draw_text(g, "USERNAME MEMBER", rx, pad + 182, rw, 22, 11.5f, Color(255, 148, 163, 184), FontStyleBold);
        SolidBrush inputBg(Color(255, 15, 23, 42));
        Pen activePen(Color(255, 6, 182, 212), 2.0f);
        fill_round_rect(g, &inputBg, rx, pad + 208, rw, 46, 8);
        draw_round_rect(g, (g_focused_input == 1) ? &activePen : &borderPen, rx, pad + 208, rw, 46, 8);
        draw_text(g, g_input_username + (g_focused_input == 1 ? "|" : ""), rx + 14, pad + 208, rw - 28, 46, 14.5f, Color(255, 248, 250, 252));
        add_hit_zone(rx, pad + 208, rw, 46, "focus_username");

        // Input Password
        draw_text(g, "PASSWORD / PIN", rx, pad + 270, rw, 22, 11.5f, Color(255, 148, 163, 184), FontStyleBold);
        fill_round_rect(g, &inputBg, rx, pad + 296, rw, 46, 8);
        draw_round_rect(g, (g_focused_input == 2) ? &activePen : &borderPen, rx, pad + 296, rw, 46, 8);
        std::string masked_pass(g_input_password.size(), '*');
        draw_text(g, masked_pass + (g_focused_input == 2 ? "|" : ""), rx + 14, pad + 296, rw - 28, 46, 14.5f, Color(255, 248, 250, 252));
        add_hit_zone(rx, pad + 296, rw, 46, "focus_password");

        // Login Button
        draw_button(g, rx, pad + 365, rw, 50, "MASUK & BUKA TERMINAL PC",
                    Color(255, 6, 182, 212), Color(255, 15, 23, 42), "do_login", 0, 10, 15.0f);

        // Info Box
        SolidBrush infoBg(Color(120, 30, 41, 59));
        fill_round_rect(g, &infoBg, rx, pad + 435, rw, 95, 10);
        draw_text(g, "FITUR CLIENT UKIKURA:\n- Top-up paket billing langsung pakai Saldo Member / QRIS\n- Pesan makanan & minuman langsung masuk Antrian Dapur\n- Kasir juga dapat membuka (Unlock) PC ini dari komputer Kasir",
                  rx + 14, pad + 442, rw - 28, 82, 11.5f, Color(255, 203, 213, 225), FontStyleRegular, StringAlignmentNear, StringAlignmentNear);

    } else {
        // =====================================================
        // MODE 2: ACTIVE CLIENT DASHBOARD (BILLING + F&B + KDS)
        // =====================================================
        SolidBrush headerBg(Color(240, 15, 23, 42));
        Pen borderPen(Color(100, 51, 65, 85), 1.0f);
        g.FillRectangle(&headerBg, 0, 4, width, 76);
        g.DrawLine(&borderPen, 0, 80, width, 80);

        // Station Badge in Header
        SolidBrush stationBg(Color(45, 6, 182, 212));
        fill_round_rect(g, &stationBg, 24, 18, 110, 48, 10);
        draw_text(g, pc_code, 24, 18, 110, 48, 20.0f, Color(255, 34, 211, 238), FontStyleBold, StringAlignmentCenter, StringAlignmentCenter);

        // Member Info
        draw_text(g, "MEMBER AKTIF", 150, 18, 160, 20, 10.5f, Color(255, 148, 163, 184), FontStyleBold);
        draw_text(g, member_name + (member_user.empty() ? "" : " (@" + member_user + ")"),
                  150, 38, 250, 26, 15.0f, Color(255, 248, 250, 252), FontStyleBold);

        // Balance Card in Header
        SolidBrush balBg(Color(35, 16, 185, 129));
        fill_round_rect(g, &balBg, 410, 16, 205, 52, 10);
        draw_text(g, "SALDO MEMBER", 424, 20, 175, 18, 10.0f, Color(255, 110, 231, 183), FontStyleBold);
        draw_text(g, format_rupiah(member_balance), 424, 38, 175, 26, 16.5f, Color(255, 52, 211, 153), FontStyleBold);

        // Remaining Timer Card in Header
        bool low_time = (remaining_seconds < 300);
        SolidBrush timeBg(low_time ? Color(45, 239, 68, 68) : Color(40, 99, 102, 241));
        fill_round_rect(g, &timeBg, 630, 16, 220, 52, 10);
        draw_text(g, "SISA WAKTU BILLING", 644, 20, 190, 18, 10.0f,
                  low_time ? Color(255, 252, 165, 165) : Color(255, 165, 180, 252), FontStyleBold);
        draw_text(g, format_timer(remaining_seconds), 644, 36, 190, 28, 20.0f,
                  low_time ? Color(255, 248, 113, 113) : Color(255, 224, 231, 255), FontStyleBold);

        // Logout / Lock Button
        draw_button(g, width - 165, 21, 140, 42, "LOGOUT / SELESAI",
                    Color(255, 220, 38, 38), Color(255, 255, 255, 255), "do_logout", 0, 8, 12.0f);

        // Left Navigation Sidebar
        int side_w = 230;
        SolidBrush sideBg(Color(200, 15, 23, 42));
        g.FillRectangle(&sideBg, 0, 81, side_w, height - 81);
        g.DrawLine(&borderPen, side_w, 81, side_w, height);

        struct NavItem { int idx; std::string label; std::string badge; };
        std::vector<NavItem> navs = {
            { 0, "Tambah Billing", "" },
            { 1, "Pesan Makanan", "" },
            { 2, "Antrian Dapur", std::to_string(active_orders.size()) },
            { 3, "Pengaturan PC", "" }
        };

        int nav_y = 105;
        for (const auto& nav : navs) {
            bool active = (g_active_tab == nav.idx);
            Color bg = active ? Color(255, 6, 182, 212) : Color(0, 0, 0, 0);
            Color fg = active ? Color(255, 15, 23, 42) : Color(255, 203, 213, 225);
            if (active) {
                SolidBrush b(bg);
                fill_round_rect(g, &b, 16, nav_y, side_w - 32, 46, 10);
            }
            draw_text(g, nav.label, 30, nav_y, side_w - 60, 46, 14.0f, fg, active ? FontStyleBold : FontStyleRegular);
            if (!nav.badge.empty() && nav.badge != "0") {
                SolidBrush badgeBg(active ? Color(255, 15, 23, 42) : Color(255, 245, 158, 11));
                fill_round_rect(g, &badgeBg, side_w - 54, nav_y + 12, 28, 22, 6);
                draw_text(g, nav.badge, side_w - 54, nav_y + 12, 28, 22, 11.0f,
                          active ? Color(255, 56, 189, 248) : Color(255, 15, 23, 42), FontStyleBold, StringAlignmentCenter);
            }
            add_hit_zone(16, nav_y, side_w - 32, 46, "switch_tab", nav.idx);
            nav_y += 56;
        }

        // Connection status at bottom of sidebar
        draw_text(g, server_online ? "Terhubung ke Kasir Laravel" : "Putus dari Server Kasir",
                  20, height - 55, side_w - 30, 22, 11.5f,
                  server_online ? Color(255, 52, 211, 153) : Color(255, 248, 113, 113), FontStyleBold);
        draw_text(g, server_host + ":" + std::to_string(server_port),
                  20, height - 34, side_w - 30, 18, 11.0f, Color(255, 100, 116, 139));

        // Content Area
        int cx = side_w + 28;
        int cy = 102;
        int cw = width - cx - 28;
        int ch = height - cy - 24;

        if (g_active_tab == 0) {
            // -------------------------------------------------
            // TAB 0: TAMBAH BILLING (PAKET WAKTU)
            // -------------------------------------------------
            draw_text(g, "TAMBAH WAKTU BILLING LANGSUNG DARI PC", cx, cy, cw, 30, 20.0f, Color(255, 248, 250, 252), FontStyleBold);
            draw_text(g, "Pilih paket billing di bawah. Anda bisa membayar langsung menggunakan Saldo Member (otomatis aktif) atau Scan QRIS.",
                      cx, cy + 32, cw, 24, 13.0f, Color(255, 148, 163, 184));

            int cols = 3;
            int gap = 18;
            int card_w = (cw - gap * (cols - 1)) / cols;
            int card_h = 195;
            int start_y = cy + 72;

            for (size_t i = 0; i < packages.size(); ++i) {
                const auto& pkg = packages[i];
                int r = (int)i / cols;
                int c = (int)i % cols;
                int px = cx + c * (card_w + gap);
                int py = start_y + r * (card_h + gap);
                if (py + card_h > height - 20) break;

                SolidBrush cardBg(Color(220, 30, 41, 59));
                fill_round_rect(g, &cardBg, px, py, card_w, card_h, 14);
                draw_round_rect(g, &borderPen, px, py, card_w, card_h, 14);

                // Duration Pill
                SolidBrush pillBg(Color(45, 56, 189, 248));
                fill_round_rect(g, &pillBg, px + 18, py + 16, 110, 26, 6);
                draw_text(g, std::to_string(pkg.duration_minutes) + " MENIT",
                          px + 18, py + 16, 110, 26, 11.0f, Color(255, 56, 189, 248), FontStyleBold, StringAlignmentCenter);

                draw_text(g, pkg.name, px + 18, py + 50, card_w - 36, 26, 17.0f, Color(255, 248, 250, 252), FontStyleBold);
                draw_text(g, pkg.description, px + 18, py + 78, card_w - 36, 22, 12.0f, Color(255, 148, 163, 184));
                draw_text(g, format_rupiah(pkg.price), px + 18, py + 104, card_w - 36, 30, 20.0f, Color(255, 52, 211, 153), FontStyleBold);

                int btn_w = (card_w - 46) / 2;
                draw_button(g, px + 18, py + 142, btn_w, 38, "Bayar Saldo",
                            Color(255, 16, 185, 129), Color(255, 15, 23, 42), "buy_pkg_balance", pkg.id, 8, 12.0f);
                draw_button(g, px + 28 + btn_w, py + 142, btn_w, 38, "Bayar QRIS",
                            Color(255, 99, 102, 241), Color(255, 255, 255, 255), "buy_pkg_qris", pkg.id, 8, 12.0f);
            }
        } else if (g_active_tab == 1) {
            // -------------------------------------------------
            // TAB 1: PESAN MAKANAN & MINUMAN (F&B CAFE)
            // -------------------------------------------------
            int cart_w = 310;
            int menu_w = cw - cart_w - 20;

            draw_text(g, "KATALOG MAKANAN & MINUMAN WARNET", cx, cy, menu_w, 28, 19.0f, Color(255, 248, 250, 252), FontStyleBold);

            // Category Filter Pills
            struct CatFilter { std::string key; std::string label; };
            std::vector<CatFilter> cats = {
                {"all", "Semua Menu"},
                {"makanan", "Makanan Berat"},
                {"minuman", "Minuman Segar"},
                {"snack", "Snack & Cemilan"}
            };
            int fx = cx;
            for (const auto& cat : cats) {
                bool active = (g_food_category_filter == cat.key);
                draw_button(g, fx, cy + 36, 132, 32, cat.label,
                            active ? Color(255, 6, 182, 212) : Color(255, 30, 41, 59),
                            active ? Color(255, 15, 23, 42) : Color(255, 203, 213, 225),
                            "filter_cat_" + cat.key, 0, 8, 11.5f);
                fx += 140;
            }

            // Menu Grid (2 Columns)
            int cols = 2;
            int gap = 14;
            int card_w = (menu_w - gap) / cols;
            int card_h = 98;
            int my = cy + 82;
            int idx_drawn = 0;

            for (const auto& m : menu_items) {
                if (g_food_category_filter != "all" && m.category != g_food_category_filter) continue;
                int r = idx_drawn / cols;
                int c = idx_drawn % cols;
                int mx = cx + c * (card_w + gap);
                int item_y = my + r * (card_h + gap);
                if (item_y + card_h > height - 15) break;

                SolidBrush cardBg(m.cart_qty > 0 ? Color(240, 23, 37, 84) : Color(220, 30, 41, 59));
                fill_round_rect(g, &cardBg, mx, item_y, card_w, card_h, 12);
                draw_round_rect(g, &borderPen, mx, item_y, card_w, card_h, 12);

                draw_text(g, m.name, mx + 16, item_y + 12, card_w - 130, 24, 14.5f, Color(255, 248, 250, 252), FontStyleBold);
                draw_text(g, "Kategori: " + m.category + "  •  Stok: " + std::to_string(m.stock),
                          mx + 16, item_y + 36, card_w - 130, 20, 11.5f, Color(255, 148, 163, 184));
                draw_text(g, format_rupiah(m.price), mx + 16, item_y + 60, 150, 24, 15.0f, Color(255, 52, 211, 153), FontStyleBold);

                // - / qty / + Controls
                int qx = mx + card_w - 118;
                int qy = item_y + 32;
                draw_button(g, qx, qy, 32, 32, "-", Color(255, 51, 65, 85), Color(255, 248, 250, 252), "cart_sub", m.id, 6, 15.0f);
                draw_text(g, std::to_string(m.cart_qty), qx + 32, qy, 38, 32, 14.5f, Color(255, 248, 250, 252), FontStyleBold, StringAlignmentCenter);
                draw_button(g, qx + 70, qy, 32, 32, "+", Color(255, 6, 182, 212), Color(255, 15, 23, 42), "cart_add", m.id, 6, 15.0f);

                idx_drawn++;
            }

            // Right Cart Panel
            int rx = cx + menu_w + 20;
            SolidBrush cartBg(Color(235, 15, 23, 42));
            fill_round_rect(g, &cartBg, rx, cy, cart_w, ch, 14);
            draw_round_rect(g, &borderPen, rx, cy, cart_w, ch, 14);

            draw_text(g, "KERANJANG PESANAN", rx + 18, cy + 16, cart_w - 36, 26, 15.5f, Color(255, 248, 250, 252), FontStyleBold);
            g.DrawLine(&borderPen, rx + 16, cy + 50, rx + cart_w - 16, cy + 50);

            int list_y = cy + 60;
            long long total_cart = 0;
            int count_items = 0;
            for (const auto& m : menu_items) {
                if (m.cart_qty <= 0) continue;
                long long sub = m.price * m.cart_qty;
                total_cart += sub;
                count_items += m.cart_qty;

                if (list_y < cy + ch - 210) {
                    draw_text(g, std::to_string(m.cart_qty) + "x " + m.name, rx + 18, list_y, 175, 22, 12.5f, Color(255, 226, 232, 240), FontStyleBold);
                    draw_text(g, format_rupiah(sub), rx + cart_w - 115, list_y, 97, 22, 12.5f, Color(255, 52, 211, 153), FontStyleBold, StringAlignmentFar);
                    list_y += 28;
                }
            }

            if (count_items == 0) {
                draw_text(g, "Keranjang masih kosong.\nKlik tombol [+] pada menu makanan untuk memesan.",
                          rx + 20, cy + 110, cart_w - 40, 60, 12.5f, Color(255, 100, 116, 139), FontStyleRegular, StringAlignmentCenter);
            }

            // Notes Input & Checkout at bottom of Cart
            int bot_y = cy + ch - 195;
            g.DrawLine(&borderPen, rx + 16, bot_y, rx + cart_w - 16, bot_y);

            draw_text(g, "CATATAN DAPUR (OPSIONAL):", rx + 18, bot_y + 10, cart_w - 36, 18, 10.5f, Color(255, 148, 163, 184), FontStyleBold);
            SolidBrush noteBg(Color(255, 30, 41, 59));
            Pen focusPen(Color(255, 6, 182, 212), 1.5f);
            fill_round_rect(g, &noteBg, rx + 18, bot_y + 32, cart_w - 36, 36, 6);
            draw_round_rect(g, (g_focused_input == 3) ? &focusPen : &borderPen, rx + 18, bot_y + 32, cart_w - 36, 36, 6);
            std::string note_display = g_input_food_notes.empty() && g_focused_input != 3
                ? "Contoh: Pedas, tanpa bawang..."
                : (g_input_food_notes + (g_focused_input == 3 ? "|" : ""));
            draw_text(g, note_display, rx + 26, bot_y + 32, cart_w - 52, 36, 12.0f,
                      g_input_food_notes.empty() && g_focused_input != 3 ? Color(255, 100, 116, 139) : Color(255, 248, 250, 252));
            add_hit_zone(rx + 18, bot_y + 32, cart_w - 36, 36, "focus_notes");

            draw_text(g, "TOTAL BAYAR", rx + 18, bot_y + 78, 120, 24, 12.0f, Color(255, 148, 163, 184), FontStyleBold);
            draw_text(g, format_rupiah(total_cart), rx + 120, bot_y + 74, cart_w - 138, 28, 18.0f, Color(255, 52, 211, 153), FontStyleBold, StringAlignmentFar);

            draw_button(g, rx + 18, bot_y + 110, cart_w - 36, 36, "PESAN — POTONG SALDO MEMBER",
                        Color(255, 16, 185, 129), Color(255, 15, 23, 42), "order_food_balance", 0, 8, 12.0f);
            draw_button(g, rx + 18, bot_y + 152, cart_w - 36, 36, "PESAN — BAYAR PAKAI QRIS",
                        Color(255, 99, 102, 241), Color(255, 255, 255, 255), "order_food_qris", 0, 8, 12.0f);

        } else if (g_active_tab == 2) {
            // -------------------------------------------------
            // TAB 2: STATUS PESANAN & ANTRIAN DAPUR LIVE
            // -------------------------------------------------
            draw_text(g, "STATUS PESANAN & ANTRIAN DAPUR (LIVE TRACKING)", cx, cy, cw, 30, 19.0f, Color(255, 248, 250, 252), FontStyleBold);
            draw_text(g, "Pantau langsung apakah makanan/minuman Anda sedang dimasak di dapur atau sudah siap diantar ke meja PC Anda.",
                      cx, cy + 32, cw, 22, 13.0f, Color(255, 148, 163, 184));

            int oy = cy + 68;
            if (active_orders.empty()) {
                SolidBrush emptyBg(Color(180, 30, 41, 59));
                fill_round_rect(g, &emptyBg, cx, oy, cw, 120, 12);
                draw_text(g, "Belum ada pesanan aktif dari " + pc_code + ". Silakan pesan melalui menu Pesan Makanan.",
                          cx + 20, oy, cw - 40, 120, 14.0f, Color(255, 148, 163, 184), FontStyleRegular, StringAlignmentCenter);
            }

            for (const auto& ord : active_orders) {
                if (oy + 105 > height - 20) break;
                SolidBrush cardBg(Color(220, 30, 41, 59));
                fill_round_rect(g, &cardBg, cx, oy, cw, 98, 12);
                draw_round_rect(g, &borderPen, cx, oy, cw, 98, 12);

                draw_text(g, ord.order_code + "  •  " + ord.items_summary, cx + 20, oy + 14, cw - 300, 24, 15.0f, Color(255, 248, 250, 252), FontStyleBold);
                draw_text(g, "Total: " + format_rupiah(ord.total_price) + "  |  Metode: " +
                          (ord.payment_method == "qris" ? "QRIS" : "Saldo Member") + " (" +
                          (ord.payment_status == "paid" ? "LUNAS" : "MENUNGGU BAYAR") + ")",
                          cx + 20, oy + 40, cw - 300, 20, 12.5f, Color(255, 52, 211, 153));

                // Kitchen Status Progress Steps
                std::string k_label = "MENUNGGU DAPUR";
                Color k_col = Color(255, 251, 191, 36);
                if (ord.kitchen_status == "cooking") {
                    k_label = "SEDANG DIMASAK DI DAPUR";
                    k_col = Color(255, 249, 115, 22);
                } else if (ord.kitchen_status == "ready") {
                    k_label = "SIAP DIANTAR KE " + pc_code;
                    k_col = Color(255, 16, 185, 129);
                } else if (ord.kitchen_status == "completed") {
                    k_label = "SELESAI / SUDAH DIANTAR";
                    k_col = Color(255, 56, 189, 248);
                }

                SolidBrush kBg(Color(40, k_col.GetR(), k_col.GetG(), k_col.GetB()));
                fill_round_rect(g, &kBg, cx + cw - 270, oy + 18, 250, 36, 8);
                draw_text(g, k_label, cx + cw - 270, oy + 18, 250, 36, 12.0f, k_col, FontStyleBold, StringAlignmentCenter);

                if (ord.payment_status != "paid" && ord.payment_method == "qris") {
                    draw_button(g, cx + cw - 270, oy + 60, 250, 28, "Lihat Kode QRIS / Bayar",
                                Color(255, 99, 102, 241), Color(255, 255, 255, 255), "reopen_qris", ord.id, 6, 11.5f);
                } else {
                    draw_text(g, "Waktu Pesan: " + ord.created_at, cx + 20, oy + 66, 300, 20, 11.5f, Color(255, 100, 116, 139));
                }

                oy += 110;
            }
        } else if (g_active_tab == 3) {
            // -------------------------------------------------
            // TAB 3: PENGATURAN TERMINAL PC
            // -------------------------------------------------
            draw_text(g, "PENGATURAN TERMINAL CLIENT C++", cx, cy, cw, 30, 19.0f, Color(255, 248, 250, 252), FontStyleBold);
            SolidBrush cardBg(Color(220, 30, 41, 59));
            fill_round_rect(g, &cardBg, cx, cy + 45, 520, 260, 14);
            draw_round_rect(g, &borderPen, cx, cy + 45, 520, 260, 14);

            draw_text(g, "Nomor Terminal PC Saat Ini: " + pc_code, cx + 24, cy + 70, 460, 28, 16.0f, Color(255, 56, 189, 248), FontStyleBold);
            draw_button(g, cx + 24, cy + 108, 150, 38, "< Ganti PC Sebelumnya", Color(255, 51, 65, 85), Color(255, 255, 255, 255), "prev_pc", 0, 8, 12.0f);
            draw_button(g, cx + 188, cy + 108, 150, 38, "Ganti PC Berikutnya >", Color(255, 51, 65, 85), Color(255, 255, 255, 255), "next_pc", 0, 8, 12.0f);

            draw_text(g, "Server Laravel Kasir: http://" + server_host + ":" + std::to_string(server_port),
                      cx + 24, cy + 168, 460, 24, 14.0f, Color(255, 203, 213, 225));
            draw_button(g, cx + 24, cy + 205, 220, 40, "Sinkronisasi Ulang Katalog",
                        Color(255, 6, 182, 212), Color(255, 15, 23, 42), "force_sync", 0, 8, 12.5f);
        }
    }

    // =====================================================
    // MODAL 1: QRIS PAYMENT MODAL
    // =====================================================
    if (g_show_qris_modal) {
        SolidBrush dimBg(Color(200, 3, 7, 18));
        g.FillRectangle(&dimBg, 0, 0, width, height);

        int mw = 440;
        int mh = 520;
        int mx = (width - mw) / 2;
        int my = (height - mh) / 2;

        SolidBrush modalBg(Color(255, 17, 24, 39));
        Pen cyanPen(Color(255, 6, 182, 212), 2.0f);
        fill_round_rect(g, &modalBg, mx, my, mw, mh, 18);
        draw_round_rect(g, &cyanPen, mx, my, mw, mh, 18);

        draw_text(g, "PEMBAYARAN QRIS INSTAN", mx + 24, my + 22, mw - 48, 28, 19.0f, Color(255, 248, 250, 252), FontStyleBold, StringAlignmentCenter);
        draw_text(g, g_qris_title, mx + 24, my + 52, mw - 48, 22, 13.0f, Color(255, 56, 189, 248), FontStyleBold, StringAlignmentCenter);

        // Draw GDI+ QR Matrix
        draw_qr_matrix(g, mx + (mw - 230) / 2, my + 86, 230, g_qris_order_code + std::to_string(g_qris_amount));

        draw_text(g, "Kode Transaksi: " + g_qris_order_code, mx + 24, my + 328, mw - 48, 20, 12.0f, Color(255, 148, 163, 184), FontStyleRegular, StringAlignmentCenter);
        draw_text(g, format_rupiah(g_qris_amount), mx + 24, my + 350, mw - 48, 32, 24.0f, Color(255, 52, 211, 153), FontStyleBold, StringAlignmentCenter);
        draw_text(g, "Scan QRIS di atas dengan GoPay / OVO / DANA / Mobile Banking", mx + 24, my + 386, mw - 48, 20, 11.5f, Color(255, 203, 213, 225), FontStyleRegular, StringAlignmentCenter);

        draw_button(g, mx + 28, my + 418, mw - 56, 40, "Simulasi Scan QRIS Berhasil (Auto-Lunas)",
                    Color(255, 16, 185, 129), Color(255, 15, 23, 42), "simulate_qris_paid", 0, 8, 13.0f);
        draw_button(g, mx + 28, my + 466, mw - 56, 36, "Tutup Jendela QRIS",
                    Color(255, 51, 65, 85), Color(255, 226, 232, 240), "close_qris", 0, 8, 12.0f);
    }

    // =====================================================
    // MODAL 2: PESAN DARI KASIR (ADMIN BROADCAST / ALERT)
    // =====================================================
    if (g_show_admin_modal) {
        SolidBrush dimBg(Color(190, 3, 7, 18));
        g.FillRectangle(&dimBg, 0, 0, width, height);

        int mw = 460;
        int mh = 230;
        int mx = (width - mw) / 2;
        int my = (height - mh) / 2;

        SolidBrush modalBg(Color(255, 30, 41, 59));
        Pen amberPen(Color(255, 245, 158, 11), 2.5f);
        fill_round_rect(g, &modalBg, mx, my, mw, mh, 16);
        draw_round_rect(g, &amberPen, mx, my, mw, mh, 16);

        draw_text(g, "PESAN DARI KASIR / OPERATOR", mx + 24, my + 24, mw - 48, 28, 17.0f, Color(255, 251, 191, 36), FontStyleBold, StringAlignmentCenter);
        draw_text(g, g_admin_modal_text, mx + 30, my + 68, mw - 60, 80, 15.0f, Color(255, 248, 250, 252), FontStyleRegular, StringAlignmentCenter);
        draw_button(g, mx + (mw - 180) / 2, my + 165, 180, 40, "MENGERTI (OK)",
                    Color(255, 245, 158, 11), Color(255, 15, 23, 42), "close_admin_modal", 0, 8, 13.0f);
    }

    // =====================================================
    // TOAST NOTIFICATION BANNER
    // =====================================================
    if (!toast_text.empty() && GetTickCount64() < toast_until) {
        int tw = 520;
        int th = 46;
        int tx = (width - tw) / 2;
        int ty = height - 68;
        SolidBrush toastBg(Color(245, 6, 182, 212));
        fill_round_rect(g, &toastBg, tx, ty, tw, th, 10);
        draw_text(g, toast_text, tx + 16, ty, tw - 32, th, 13.5f, Color(255, 15, 23, 42), FontStyleBold, StringAlignmentCenter);
    }

    // Blit offscreen buffer to screen
    Graphics screenG(hdc);
    screenG.DrawImage(&buffer, 0, 0);
}

void change_pc_number(int delta) {
    int current_num = 1;
    {
        std::lock_guard<std::mutex> lock(g_state.mtx);
        if (g_state.pc_code.size() >= 4 && g_state.pc_code.substr(0, 3) == "PC-") {
            current_num = std::atoi(g_state.pc_code.substr(3).c_str());
        }
        current_num += delta;
        if (current_num < 1) current_num = 10;
        if (current_num > 10) current_num = 1;
        std::ostringstream oss;
        oss << "PC-" << std::setfill('0') << std::setw(2) << current_num;
        g_state.pc_code = oss.str();
        g_state.active_orders.clear();
        for (auto& m : g_state.menu_items) m.cart_qty = 0;
    }
    show_toast("Berpindah ke Terminal " + g_state.pc_code);
    std::thread([]() {
        sync_catalog_from_server();
        sync_heartbeat();
    }).detach();
}

void handle_click(int x, int y) {
    for (int i = (int)g_hit_zones.size() - 1; i >= 0; --i) {
        const auto& hz = g_hit_zones[i];
        if (x >= hz.rc.left && x <= hz.rc.right && y >= hz.rc.top && y <= hz.rc.bottom) {
            std::string act = hz.action;
            int id = hz.param_id;

            if (act == "focus_username") { g_focused_input = 1; }
            else if (act == "focus_password") { g_focused_input = 2; }
            else if (act == "focus_notes") { g_focused_input = 3; }
            else if (act == "fill_demo_1") {
                g_input_username = "sultan01";
                g_input_password = "password";
                show_toast("Akun Demo sultan01 terisi! Klik MASUK untuk mulai.");
            }
            else if (act == "fill_demo_2") {
                g_input_username = "gamer02";
                g_input_password = "password";
                show_toast("Akun Demo gamer02 terisi! Klik MASUK untuk mulai.");
            }
            else if (act == "do_login") { perform_login(); }
            else if (act == "do_logout") { perform_logout(); }
            else if (act == "switch_tab") { g_active_tab = id; g_focused_input = 0; }
            else if (act == "prev_pc") { change_pc_number(-1); }
            else if (act == "next_pc") { change_pc_number(1); }
            else if (act == "force_sync") {
                show_toast("Menyinkronkan data dengan Server Laravel...");
                std::thread([]() { sync_catalog_from_server(); sync_heartbeat(); }).detach();
            }
            else if (act == "buy_pkg_balance") { perform_buy_billing(id, "balance"); }
            else if (act == "buy_pkg_qris") { perform_buy_billing(id, "qris"); }
            else if (act.rfind("filter_cat_", 0) == 0) {
                g_food_category_filter = act.substr(11);
            }
            else if (act == "cart_add") {
                std::lock_guard<std::mutex> lock(g_state.mtx);
                for (auto& m : g_state.menu_items) {
                    if (m.id == id && m.cart_qty < m.stock) m.cart_qty++;
                }
            }
            else if (act == "cart_sub") {
                std::lock_guard<std::mutex> lock(g_state.mtx);
                for (auto& m : g_state.menu_items) {
                    if (m.id == id && m.cart_qty > 0) m.cart_qty--;
                }
            }
            else if (act == "order_food_balance") { perform_order_food("balance"); }
            else if (act == "order_food_qris") { perform_order_food("qris"); }
            else if (act == "close_qris") { g_show_qris_modal = false; }
            else if (act == "simulate_qris_paid") { simulate_qris_paid(); }
            else if (act == "reopen_qris") {
                std::lock_guard<std::mutex> lock(g_state.mtx);
                for (const auto& o : g_state.active_orders) {
                    if (o.id == id) {
                        g_qris_order_code = o.order_code;
                        g_qris_title = o.items_summary;
                        g_qris_amount = o.total_price;
                        g_show_qris_modal = true;
                        break;
                    }
                }
            }
            else if (act == "close_admin_modal") { g_show_admin_modal = false; }

            InvalidateRect(g_hwnd, nullptr, FALSE);
            return;
        }
    }
    g_focused_input = 0;
    InvalidateRect(g_hwnd, nullptr, FALSE);
}

void toggle_fullscreen(HWND hwnd) {
    g_fullscreen_lock_mode = !g_fullscreen_lock_mode;
    if (g_fullscreen_lock_mode) {
        SetWindowLong(hwnd, GWL_STYLE, WS_POPUP | WS_VISIBLE);
        int sw = GetSystemMetrics(SM_CXSCREEN);
        int sh = GetSystemMetrics(SM_CYSCREEN);
        SetWindowPos(hwnd, HWND_TOPMOST, 0, 0, sw, sh, SWP_FRAMECHANGED | SWP_SHOWWINDOW);
    } else {
        SetWindowLong(hwnd, GWL_STYLE, WS_OVERLAPPEDWINDOW | WS_VISIBLE);
        SetWindowPos(hwnd, HWND_NOTOPMOST, 80, 50, 1180, 740, SWP_FRAMECHANGED | SWP_SHOWWINDOW);
    }
}

LRESULT CALLBACK WndProc(HWND hwnd, UINT msg, WPARAM wParam, LPARAM lParam) {
    switch (msg) {
        case WM_PAINT: {
            PAINTSTRUCT ps;
            HDC hdc = BeginPaint(hwnd, &ps);
            RECT rc;
            GetClientRect(hwnd, &rc);
            render_ui(hdc, rc.right - rc.left, rc.bottom - rc.top);
            EndPaint(hwnd, &ps);
            return 0;
        }
        case WM_ERASEBKGND:
            return 1; // Prevent flicker
        case WM_LBUTTONDOWN: {
            int x = GET_X_LPARAM(lParam);
            int y = GET_Y_LPARAM(lParam);
            handle_click(x, y);
            return 0;
        }
        case WM_KEYDOWN: {
            if (wParam == VK_F11) {
                toggle_fullscreen(hwnd);
                return 0;
            }
            if (wParam == VK_RETURN && g_focused_input == 2) {
                perform_login();
                return 0;
            }
            if (wParam == VK_TAB) {
                if (g_focused_input == 1) g_focused_input = 2;
                else if (g_focused_input == 2) g_focused_input = 1;
                InvalidateRect(hwnd, nullptr, FALSE);
                return 0;
            }
            return 0;
        }
        case WM_CHAR: {
            char ch = (char)wParam;
            std::string* target = nullptr;
            if (g_focused_input == 1) target = &g_input_username;
            else if (g_focused_input == 2) target = &g_input_password;
            else if (g_focused_input == 3) target = &g_input_food_notes;

            if (target) {
                if (ch == '\b') {
                    if (!target->empty()) target->pop_back();
                } else if (ch >= 32 && ch <= 126 && target->size() < 60) {
                    target->push_back(ch);
                }
                InvalidateRect(hwnd, nullptr, FALSE);
            }
            return 0;
        }
        case WM_TIMER: {
            if (wParam == 1) {
                // Local 1-second countdown smoothing
                std::lock_guard<std::mutex> lock(g_state.mtx);
                if (!g_state.is_locked && g_state.remaining_seconds > 0) {
                    g_state.remaining_seconds--;
                }
                InvalidateRect(hwnd, nullptr, FALSE);
            }
            return 0;
        }
        case WM_DESTROY:
            g_running = false;
            PostQuitMessage(0);
            return 0;
    }
    return DefWindowProc(hwnd, msg, wParam, lParam);
}

int WINAPI WinMain(HINSTANCE hInstance, HINSTANCE, LPSTR lpCmdLine, int nCmdShow) {
    // Optional CLI args: WarnetClient.exe PC-02
    if (lpCmdLine && strlen(lpCmdLine) > 0) {
        std::string arg(lpCmdLine);
        if (arg.find("PC-") == 0) {
            g_state.pc_code = arg;
        }
    }

    // Initialize GDI+
    GdiplusStartupInput gdiplusStartupInput;
    ULONG_PTR gdiplusToken;
    GdiplusStartup(&gdiplusToken, &gdiplusStartupInput, nullptr);

    WNDCLASSEX wc = {};
    wc.cbSize = sizeof(WNDCLASSEX);
    wc.style = CS_HREDRAW | CS_VREDRAW;
    wc.lpfnWndProc = WndProc;
    wc.hInstance = hInstance;
    wc.hCursor = LoadCursor(nullptr, IDC_ARROW);
    wc.lpszClassName = L"UkikuraWarnetClientClass";
    RegisterClassEx(&wc);

    g_hwnd = CreateWindowEx(
        0,
        L"UkikuraWarnetClientClass",
        L"Ukikura iCafe Native Client (C++) - Billing & Food Order",
        WS_OVERLAPPEDWINDOW | WS_VISIBLE,
        80, 50, 1180, 740,
        nullptr, nullptr, hInstance, nullptr
    );

    ShowWindow(g_hwnd, nCmdShow);
    UpdateWindow(g_hwnd);

    // 1-second UI timer
    SetTimer(g_hwnd, 1, 1000, nullptr);

    // Background polling worker thread (every 2 seconds)
    std::thread poller([]() {
        int tick = 0;
        while (g_running) {
            if (tick % 3 == 0) {
                sync_catalog_from_server();
            }
            sync_heartbeat();
            tick++;
            for (int i = 0; i < 20 && g_running; ++i) {
                Sleep(100);
            }
        }
    });

    MSG msg;
    while (GetMessage(&msg, nullptr, 0, 0)) {
        TranslateMessage(&msg);
        DispatchMessage(&msg);
    }

    g_running = false;
    if (poller.joinable()) poller.join();
    GdiplusShutdown(gdiplusToken);
    return 0;
}
