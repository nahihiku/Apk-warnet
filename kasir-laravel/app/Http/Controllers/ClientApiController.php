<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PcClient;
use App\Models\Member;
use App\Models\BillingPackage;
use App\Models\FoodMenu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\GameVaultAccount;
use App\Models\DisklessServerConfig;
use App\Models\ServerGame;
use App\Services\ServerGameScanner;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClientApiController extends Controller
{
    public function catalog(Request $request)
    {
        $pcCode = $request->input('pc_code');
        $pcType = 'all';
        $pcSpecs = '';
        $hourlyRate = 10000;
        if ($pcCode) {
            $pc = PcClient::where('pc_code', $pcCode)->first();
            if ($pc) {
                if ($pc->pc_type) $pcType = $pc->pc_type;
                if ($pc->specs) $pcSpecs = $pc->specs;
                if ($pc->hourly_rate) $hourlyRate = $pc->hourly_rate;
            }
        }

        $packagesQuery = BillingPackage::where('is_active', true);
        if ($pcType !== 'all') {
            $packagesQuery->whereIn('pc_type', ['all', $pcType]);
        }
        $packages = $packagesQuery->orderBy('price')->get();
        $menus = FoodMenu::where('is_available', true)->orderBy('category')->orderBy('name')->get();

        $gameShare = DisklessServerConfig::getGameShareConfig($request->getHost());

        $disklessInfo = [
            'master_drive' => DisklessServerConfig::where('key', 'master_drive')->value('value') ?? '4TB NVMe PCIe Gen4 Enterprise (Drive D: Games)',
            'network_speed' => DisklessServerConfig::where('key', 'network_speed')->value('value') ?? '2.5GbE Full-Duplex Switch 16-Port',
            'superuser_mode' => DisklessServerConfig::where('key', 'superuser_mode')->value('value') ?? 'STANDBY',
            'game_share' => $gameShare,
        ];

        $vaultAccounts = GameVaultAccount::select('id', 'game_title', 'platform', 'status', 'borrowed_by_pc')
            ->orderBy('game_title')
            ->get();

        if (ServerGame::count() === 0) {
            ServerGameScanner::scan();
        }

        $games = ServerGame::where('is_visible', true)
            ->orderByRaw("CASE min_tier WHEN 'reguler' THEN 1 WHEN 'vip' THEN 2 WHEN 'vip++' THEN 3 ELSE 4 END")
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'pc_type' => $pcType,
            'specs' => $pcSpecs,
            'hourly_rate' => $hourlyRate,
            'packages' => $packages,
            'menus' => $menus,
            'diskless' => $disklessInfo,
            'game_share' => $gameShare,
            'game_vault' => $vaultAccounts,
            'games' => $games,
        ]);
    }

    public function heartbeat(Request $request)
    {
        $pcCode = $request->input('pc_code', 'PC-01');
        $pc = PcClient::with('member')->firstOrCreate(
            ['pc_code' => $pcCode],
            ['name' => "Terminal $pcCode", 'status' => 'locked', 'is_locked' => true]
        );

        $pc->last_seen_at = Carbon::now();

        $remaining = 0;
        if (!$pc->is_locked) {
            if ($pc->session_end_at) {
                $remaining = max(0, (int) Carbon::now()->diffInSeconds($pc->session_end_at, false));
                if ($remaining <= 0) {
                    // Time expired -> auto lock PC & kembalikan lisensi game vault jika ada
                    GameVaultAccount::where('borrowed_by_pc', $pcCode)->update([
                        'status' => 'available',
                        'borrowed_by_pc' => null,
                        'borrowed_by_member' => null,
                        'borrowed_at' => null,
                    ]);

                    if ($pc->member) {
                        $pc->member->update(['saved_seconds' => 0]);
                    }
                    $pc->is_locked = true;
                    $pc->status = 'locked';
                    $pc->current_member_id = null;
                    $pc->session_end_at = null;
                    $pc->remaining_seconds_cache = 0;
                } else {
                    $pc->remaining_seconds_cache = $remaining;
                    if ($pc->member) {
                        $pc->member->update(['saved_seconds' => $remaining]);
                    }
                }
            }
        }

        $pc->save();
        $pc->load('member');

        $orders = collect();
        if (!$pc->is_locked) {
            $ordersQuery = Order::where('pc_code', $pcCode);
            if ($pc->current_member_id) {
                // Membedakan antrian: HANYA tampilkan pesanan milik member yang sedang login di PC ini
                $ordersQuery->where('member_id', $pc->current_member_id);
            } else {
                // Jika PC dibuka oleh kasir untuk Guest tanpa akun member
                $ordersQuery->whereNull('member_id');
            }

            $orders = $ordersQuery->orderByDesc('id')
                ->limit(8)
                ->get()
                ->map(function ($o) {
                    return [
                        'id' => $o->id,
                        'order_code' => $o->order_code,
                        'order_type' => $o->order_type,
                        'total_price' => (int) $o->total_price,
                        'payment_method' => $o->payment_method,
                        'payment_status' => $o->payment_status,
                        'kitchen_status' => $o->kitchen_status,
                        'items_summary' => $o->items_summary ?? '-',
                        'created_at' => $o->created_at ? $o->created_at->format('H:i:s') : '-',
                    ];
                });
        }

        return response()->json([
            'success' => true,
            'pc_code' => $pc->pc_code,
            'pc_type' => $pc->pc_type ?? 'reguler',
            'hourly_rate' => (int) ($pc->hourly_rate ?? 5000),
            'specs' => $pc->specs ?? '',
            'is_locked' => (bool) $pc->is_locked,
            'status' => $pc->status,
            'remaining_seconds' => (int) $remaining,
            'member_name' => $pc->member ? $pc->member->name : 'Guest / Operator',
            'member_username' => $pc->member ? $pc->member->username : '',
            'member_balance' => $pc->member ? (int) $pc->member->balance : 0,
            'admin_message' => $pc->admin_message ?? '',
            'message_id' => (int) $pc->message_id,
            'orders' => $orders,
        ]);
    }

    public function login(Request $request)
    {
        $pcCode = $request->input('pc_code', 'PC-01');
        $username = trim($request->input('username', ''));
        $password = $request->input('password', '');

        $member = Member::where('username', $username)->first();
        if (!$member || !Hash::check($password, $member->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Username atau Password salah!',
            ], 401);
        }

        $pc = PcClient::firstOrCreate(
            ['pc_code' => $pcCode],
            ['name' => "Terminal $pcCode"]
        );

        // Give at least 5 minutes grace if saved_seconds is 0 so member can top-up billing from PC
        $seconds = max(300, (int) $member->saved_seconds);

        $pc->current_member_id = $member->id;
        $pc->is_locked = false;
        $pc->status = 'active';
        $pc->session_end_at = Carbon::now()->addSeconds($seconds);
        $pc->remaining_seconds_cache = $seconds;
        $pc->admin_message = null; // Bersihkan notifikasi/pesan dari user sebelumnya
        $pc->last_seen_at = Carbon::now();
        $pc->save();

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil!',
            'remaining_seconds' => $seconds,
        ]);
    }

    public function logout(Request $request)
    {
        $pcCode = $request->input('pc_code', 'PC-01');

        // Otomatis kembalikan lisensi game vault yang dipinjam terminal ini
        GameVaultAccount::where('borrowed_by_pc', $pcCode)->update([
            'status' => 'available',
            'borrowed_by_pc' => null,
            'borrowed_by_member' => null,
            'borrowed_at' => null,
        ]);

        $pc = PcClient::with('member')->where('pc_code', $pcCode)->first();
        if ($pc) {
            $rem = $pc->computeRemainingSeconds();
            if ($pc->member) {
                $pc->member->update(['saved_seconds' => $rem]);
            }
            $pc->is_locked = true;
            $pc->status = 'locked';
            $pc->current_member_id = null;
            $pc->session_end_at = null;
            $pc->remaining_seconds_cache = 0;
            $pc->admin_message = null; // Bersihkan pesan admin saat user logout
            $pc->save();
        }

        return response()->json(['success' => true]);
    }

    public function borrowGameVault(Request $request)
    {
        $pcCode = $request->input('pc_code', 'PC-01');
        $gameTitle = $request->input('game_title', '');

        $pc = PcClient::where('pc_code', $pcCode)->first();
        if (!$pc || $pc->is_locked) {
            return response()->json([
                'success' => false,
                'message' => 'Terminal PC belum aktif atau masih dalam kondisi terkunci!',
            ], 403);
        }

        if (!in_array($pc->pc_type, ['vip', 'vip++'])) {
            return response()->json([
                'success' => false,
                'message' => 'Layanan Pinjam Akun Game AAA Warnet hanya tersedia untuk Terminal VIP dan VIP++! Silakan beralih ke PC VIP/VIP++.',
            ], 403);
        }

        // Cari akun yang tersedia untuk game ini (exact maupun kecocokan judul)
        $lowerReq = strtolower($gameTitle);
        $account = GameVaultAccount::where('status', 'available')->get()->first(function ($acc) use ($lowerReq) {
            $accLower = strtolower($acc->game_title);
            return $accLower === $lowerReq || str_contains($accLower, $lowerReq) || str_contains($lowerReq, $accLower);
        });

        if (!$account) {
            // Cek apakah PC ini sendiri yang sedang meminjam akun ini
            $existing = GameVaultAccount::where('borrowed_by_pc', $pcCode)->get()->first(function ($acc) use ($lowerReq) {
                $accLower = strtolower($acc->game_title);
                return $accLower === $lowerReq || str_contains($accLower, $lowerReq) || str_contains($lowerReq, $accLower);
            });
            if ($existing) {
                return response()->json([
                    'success' => true,
                    'message' => "Anda telah meminjam akun {$gameTitle} sebelumnya. Meluncurkan game...",
                    'account_username' => $existing->account_username,
                    'account_token' => $existing->account_token,
                    'platform' => $existing->platform,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => "Seluruh lisensi akun sharing untuk {$gameTitle} sedang dipinjam oleh terminal lain. Silakan coba kembali beberapa saat lagi!",
            ], 404);
        }

        $memberName = $pc->member ? $pc->member->username : 'Guest';
        $account->update([
            'status' => 'borrowed',
            'borrowed_by_pc' => $pcCode,
            'borrowed_by_member' => $memberName,
            'borrowed_at' => Carbon::now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Berhasil meminjam Akun {$gameTitle}! Auto-login Steam diaktifkan.",
            'account_username' => $account->account_username,
            'account_token' => $account->account_token,
            'platform' => $account->platform,
        ]);
    }

    public function returnGameVault(Request $request)
    {
        $pcCode = $request->input('pc_code', 'PC-01');
        GameVaultAccount::where('borrowed_by_pc', $pcCode)->update([
            'status' => 'available',
            'borrowed_by_pc' => null,
            'borrowed_by_member' => null,
            'borrowed_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Seluruh akun game vault yang dipinjam telah dikembalikan ke sistem.',
        ]);
    }

    public function buyBilling(Request $request)
    {
        $pcCode = $request->input('pc_code', 'PC-01');
        $packageId = $request->input('package_id');
        $paymentMethod = $request->input('payment_method', 'balance'); // balance or qris

        $pc = PcClient::with('member')->where('pc_code', $pcCode)->firstOrFail();
        $pkg = BillingPackage::findOrFail($packageId);

        if ($pkg->pc_type !== 'all' && $pkg->pc_type !== ($pc->pc_type ?? 'reguler')) {
            return response()->json([
                'success' => false,
                'message' => "Paket ini khusus untuk terminal " . strtoupper($pkg->pc_type) . " (PC ini adalah " . strtoupper($pc->pc_type ?? 'reguler') . ")!",
            ], 400);
        }

        $addSeconds = $pkg->duration_minutes * 60;

        if ($paymentMethod === 'balance') {
            if (!$pc->member) {
                return response()->json([
                    'success' => false,
                    'message' => 'Silakan login dengan Akun Member untuk bayar pakai Saldo, atau gunakan metode QRIS!',
                ], 400);
            }
            if ($pc->member->balance < $pkg->price) {
                return response()->json([
                    'success' => false,
                    'message' => 'Saldo Member tidak cukup! Sisa saldo: Rp ' . number_format($pc->member->balance, 0, ',', '.'),
                ], 400);
            }

            DB::transaction(function () use ($pc, $pkg, $addSeconds, $pcCode) {
                $pc->member->decrement('balance', $pkg->price);

                $baseTime = ($pc->session_end_at && $pc->session_end_at->isFuture())
                    ? $pc->session_end_at
                    : Carbon::now();

                $pc->is_locked = false;
                $pc->status = 'active';
                $pc->session_end_at = $baseTime->copy()->addSeconds($addSeconds);
                $pc->remaining_seconds_cache = $pc->computeRemainingSeconds();
                $pc->save();

                Order::create([
                    'order_code' => 'BILL-' . strtoupper(substr(uniqid(), -5)),
                    'order_type' => 'billing',
                    'pc_code' => $pcCode,
                    'member_id' => $pc->current_member_id,
                    'billing_package_id' => $pkg->id,
                    'total_price' => $pkg->price,
                    'payment_method' => 'balance',
                    'payment_status' => 'paid',
                    'kitchen_status' => 'completed',
                    'items_summary' => "Tambah Billing: {$pkg->name} (+{$pkg->duration_minutes} Menit)",
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => "Berhasil! +{$pkg->duration_minutes} Menit ditambahkan & Saldo terpotong.",
            ]);
        }

        // QRIS Flow
        $orderCode = 'QRB-' . strtoupper(substr(uniqid(), -5));
        Order::create([
            'order_code' => $orderCode,
            'order_type' => 'billing',
            'pc_code' => $pcCode,
            'member_id' => $pc->current_member_id,
            'billing_package_id' => $pkg->id,
            'total_price' => $pkg->price,
            'payment_method' => 'qris',
            'payment_status' => 'pending',
            'kitchen_status' => 'none',
            'items_summary' => "Tambah Billing: {$pkg->name} (+{$pkg->duration_minutes} Menit)",
        ]);

        return response()->json([
            'success' => true,
            'order_code' => $orderCode,
            'package_name' => $pkg->name . " (+{$pkg->duration_minutes} Menit)",
            'amount' => (int) $pkg->price,
        ]);
    }

    public function orderFood(Request $request)
    {
        $pcCode = $request->input('pc_code', 'PC-01');
        $paymentMethod = $request->input('payment_method', 'balance'); // balance or qris
        $notes = $request->input('notes', '');
        $itemsInput = $request->input('items', []);

        if (empty($itemsInput)) {
            return response()->json(['success' => false, 'message' => 'Keranjang pesanan kosong!'], 400);
        }

        $pc = PcClient::with('member')->where('pc_code', $pcCode)->firstOrFail();

        $totalPrice = 0;
        $summaryParts = [];
        $validatedItems = [];

        foreach ($itemsInput as $row) {
            $menu = FoodMenu::find($row['menu_id'] ?? 0);
            $qty = max(1, (int) ($row['qty'] ?? 1));
            if (!$menu || $menu->stock < $qty) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stok menu ' . ($menu ? $menu->name : '') . ' tidak mencukupi!',
                ], 400);
            }
            $sub = $menu->price * $qty;
            $totalPrice += $sub;
            $summaryParts[] = "{$qty}x {$menu->name}";
            $validatedItems[] = [
                'menu' => $menu,
                'qty' => $qty,
                'price' => $menu->price,
                'subtotal' => $sub,
            ];
        }

        if ($paymentMethod === 'balance') {
            if (!$pc->member) {
                return response()->json([
                    'success' => false,
                    'message' => 'Harap login dengan Akun Member untuk bayar pakai Saldo, atau pilih QRIS!',
                ], 400);
            }
            if ($pc->member->balance < $totalPrice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Saldo Member tidak cukup! Total pesanan Rp ' . number_format($totalPrice, 0, ',', '.'),
                ], 400);
            }
        }

        $orderCode = 'FNB-' . strtoupper(substr(uniqid(), -5));
        $summaryStr = implode(', ', $summaryParts);

        DB::transaction(function () use ($pc, $pcCode, $paymentMethod, $notes, $totalPrice, $orderCode, $summaryStr, $validatedItems) {
            if ($paymentMethod === 'balance' && $pc->member) {
                $pc->member->decrement('balance', $totalPrice);
            }

            $order = Order::create([
                'order_code' => $orderCode,
                'order_type' => 'food',
                'pc_code' => $pcCode,
                'member_id' => $pc->current_member_id,
                'total_price' => $totalPrice,
                'payment_method' => $paymentMethod,
                'payment_status' => ($paymentMethod === 'balance') ? 'paid' : 'pending',
                'kitchen_status' => 'waiting',
                'notes' => $notes,
                'items_summary' => $summaryStr,
            ]);

            foreach ($validatedItems as $vi) {
                $vi['menu']->decrement('stock', $vi['qty']);
                OrderItem::create([
                    'order_id' => $order->id,
                    'food_menu_id' => $vi['menu']->id,
                    'menu_name' => $vi['menu']->name,
                    'qty' => $vi['qty'],
                    'price' => $vi['price'],
                    'subtotal' => $vi['subtotal'],
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'order_code' => $orderCode,
            'amount' => (int) $totalPrice,
            'message' => 'Pesanan berhasil dikirim ke Antrian Dapur!',
        ]);
    }

    public function confirmQris(Request $request)
    {
        $orderCode = $request->input('order_code');
        $order = Order::with('billingPackage')->where('order_code', $orderCode)->firstOrFail();

        if ($order->payment_status === 'paid') {
            return response()->json(['success' => true, 'message' => 'Sudah lunas.']);
        }

        DB::transaction(function () use ($order) {
            $order->payment_status = 'paid';
            if ($order->order_type === 'billing') {
                $order->kitchen_status = 'completed';
            }
            $order->save();

            // If billing package order, add time to the PC immediately!
            if ($order->order_type === 'billing' && $order->billingPackage) {
                $pc = PcClient::where('pc_code', $order->pc_code)->first();
                if ($pc) {
                    $addSeconds = $order->billingPackage->duration_minutes * 60;
                    $baseTime = ($pc->session_end_at && $pc->session_end_at->isFuture())
                        ? $pc->session_end_at
                        : Carbon::now();

                    $pc->is_locked = false;
                    $pc->status = 'active';
                    $pc->session_end_at = $baseTime->copy()->addSeconds($addSeconds);
                    $pc->remaining_seconds_cache = $pc->computeRemainingSeconds();
                    $pc->save();
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran QRIS berhasil dikonfirmasi!',
        ]);
    }
}
