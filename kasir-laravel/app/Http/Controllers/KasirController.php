<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PcClient;
use App\Models\Member;
use App\Models\BillingPackage;
use App\Models\FoodMenu;
use App\Models\Order;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class KasirController extends Controller
{
    public function index()
    {
        $pcs = PcClient::with('member')->orderBy('pc_code')->get();
        $members = Member::orderBy('username')->get();
        $packages = BillingPackage::orderBy('price')->get();
        $menus = FoodMenu::orderBy('category')->orderBy('name')->get();
        $orders = Order::with('member')->orderByDesc('id')->limit(25)->get();

        $totalRevenueToday = Order::where('payment_status', 'paid')->sum('total_price');
        $activePcsCount = $pcs->where('is_locked', false)->count();
        $regulerPcsCount = $pcs->where('pc_type', 'reguler')->count();
        $vipPcsCount = $pcs->where('pc_type', 'vip')->count();
        $vipPlusPcsCount = $pcs->where('pc_type', 'vip++')->count();
        $kitchenQueueCount = Order::where('order_type', 'food')
            ->whereIn('kitchen_status', ['waiting', 'cooking', 'ready'])
            ->count();
        $pendingQrisCount = Order::where('payment_status', 'pending')->count();

        $waitingOrders = Order::with(['items', 'member'])
            ->where('order_type', 'food')
            ->where('kitchen_status', 'waiting')
            ->orderBy('id')
            ->get();

        $cookingOrders = Order::with(['items', 'member'])
            ->where('order_type', 'food')
            ->where('kitchen_status', 'cooking')
            ->orderBy('id')
            ->get();

        $readyOrders = Order::with(['items', 'member'])
            ->where('order_type', 'food')
            ->where('kitchen_status', 'ready')
            ->orderBy('id')
            ->get();

        return view('kasir.dashboard', compact(
            'pcs',
            'members',
            'packages',
            'menus',
            'orders',
            'totalRevenueToday',
            'activePcsCount',
            'regulerPcsCount',
            'vipPcsCount',
            'vipPlusPcsCount',
            'kitchenQueueCount',
            'pendingQrisCount',
            'waitingOrders',
            'cookingOrders',
            'readyOrders'
        ));
    }

    public function dapur()
    {
        $waitingOrders = Order::with(['items', 'member'])
            ->where('order_type', 'food')
            ->where('kitchen_status', 'waiting')
            ->orderBy('id')
            ->get();

        $cookingOrders = Order::with(['items', 'member'])
            ->where('order_type', 'food')
            ->where('kitchen_status', 'cooking')
            ->orderBy('id')
            ->get();

        $readyOrders = Order::with(['items', 'member'])
            ->where('order_type', 'food')
            ->where('kitchen_status', 'ready')
            ->orderBy('id')
            ->get();

        $completedOrders = Order::with(['items', 'member'])
            ->where('order_type', 'food')
            ->where('kitchen_status', 'completed')
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        return view('kasir.dapur', compact(
            'waitingOrders',
            'cookingOrders',
            'readyOrders',
            'completedOrders'
        ));
    }

    public function liveState()
    {
        $pcs = PcClient::with('member')->orderBy('pc_code')->get()->map(function ($pc) {
            $rem = $pc->computeRemainingSeconds();
            if (!$pc->is_locked && $rem <= 0 && $pc->session_end_at) {
                $pc->is_locked = true;
                $pc->status = 'locked';
                $pc->current_member_id = null;
                $pc->session_end_at = null;
                $pc->remaining_seconds_cache = 0;
                $pc->save();
            }
            return [
                'id' => $pc->id,
                'pc_code' => $pc->pc_code,
                'name' => $pc->name,
                'pc_type' => $pc->pc_type ?? 'reguler',
                'hourly_rate' => (int) ($pc->hourly_rate ?? 5000),
                'specs' => $pc->specs ?? '',
                'is_locked' => (bool) $pc->is_locked,
                'is_online' => $pc->isOnline(),
                'remaining_seconds' => $rem,
                'remaining_formatted' => gmdate('H:i:s', $rem),
                'member_name' => $pc->member ? $pc->member->name : 'Guest / Kosong',
                'member_username' => $pc->member ? $pc->member->username : '-',
                'member_balance' => $pc->member ? (int) $pc->member->balance : 0,
            ];
        });

        $orders = Order::orderByDesc('id')->limit(15)->get();

        return response()->json([
            'pcs' => $pcs,
            'orders' => $orders,
            'total_revenue' => Order::where('payment_status', 'paid')->sum('total_price'),
            'total_pcs' => $pcs->count(),
            'active_pcs' => $pcs->where('is_locked', false)->count(),
            'count_reguler' => $pcs->where('pc_type', 'reguler')->count(),
            'count_vip' => $pcs->where('pc_type', 'vip')->count(),
            'count_vip_plus' => $pcs->where('pc_type', 'vip++')->count(),
            'kitchen_queue' => Order::where('order_type', 'food')->whereIn('kitchen_status', ['waiting', 'cooking', 'ready'])->count(),
            'pending_qris' => Order::where('payment_status', 'pending')->count(),
        ]);
    }

    public function controlPc(Request $request)
    {
        $pc = PcClient::with('member')->findOrFail($request->input('pc_id'));
        $action = $request->input('action');

        if ($action === 'unlock_package') {
            $pkg = BillingPackage::findOrFail($request->input('package_id'));
            $memberId = $request->input('member_id') ?: $pc->current_member_id;
            $addSeconds = $pkg->duration_minutes * 60;

            $baseTime = (!$pc->is_locked && $pc->session_end_at && $pc->session_end_at->isFuture())
                ? $pc->session_end_at
                : Carbon::now();

            $pc->is_locked = false;
            $pc->status = 'active';
            if ($memberId) {
                $pc->current_member_id = $memberId;
            }
            $pc->session_end_at = $baseTime->copy()->addSeconds($addSeconds);
            $pc->remaining_seconds_cache = $pc->computeRemainingSeconds();
            $pc->save();

            if ($pc->member) {
                $pc->member->update(['saved_seconds' => $pc->remaining_seconds_cache]);
            }

            Order::create([
                'order_code' => 'KSR-' . strtoupper(substr(uniqid(), -5)),
                'order_type' => 'billing',
                'pc_code' => $pc->pc_code,
                'member_id' => $pc->current_member_id,
                'billing_package_id' => $pkg->id,
                'total_price' => $pkg->price,
                'payment_method' => 'cash',
                'payment_status' => 'paid',
                'kitchen_status' => 'completed',
                'items_summary' => "Kasir Unlock/Tambah: {$pkg->name} (+{$pkg->duration_minutes}m)",
            ]);

            return back()->with('success', "Berhasil membuka dan menambah {$pkg->name} ke {$pc->pc_code}.");
        }

        if ($action === 'lock') {
            $rem = $pc->computeRemainingSeconds();
            if ($pc->member) {
                $pc->member->update(['saved_seconds' => $rem]);
            }
            $pc->is_locked = true;
            $pc->status = 'locked';
            $pc->current_member_id = null;
            $pc->session_end_at = null;
            $pc->remaining_seconds_cache = 0;
            $pc->admin_message = null; // Bersihkan pesan admin
            $pc->save();

            return back()->with('success', "Terminal {$pc->pc_code} berhasil dikunci.");
        }

        if ($action === 'add_minutes') {
            $minutes = (int) $request->input('minutes', 30);
            $addSeconds = $minutes * 60;
            $baseTime = (!$pc->is_locked && $pc->session_end_at && $pc->session_end_at->isFuture())
                ? $pc->session_end_at
                : Carbon::now();

            $pc->is_locked = false;
            $pc->status = 'active';
            $pc->session_end_at = $baseTime->copy()->addSeconds($addSeconds);
            $pc->remaining_seconds_cache = $pc->computeRemainingSeconds();
            $pc->save();

            if ($pc->member) {
                $pc->member->update(['saved_seconds' => $pc->remaining_seconds_cache]);
            }

            return back()->with('success', "Waktu {$pc->pc_code} berhasil ditambah +{$minutes} menit.");
        }

        if ($action === 'send_message') {
            $msg = trim($request->input('message', ''));
            if ($msg !== '') {
                $pc->admin_message = $msg;
                $pc->message_id = ((int) $pc->message_id) + 1;
                $pc->save();
            }
            return back()->with('success', "Pesan pop-up berhasil dikirim ke layar {$pc->pc_code}.");
        }

        return back();
    }

    public function topupMember(Request $request)
    {
        $member = Member::findOrFail($request->input('member_id'));
        $packageId = $request->input('package_id');
        $paymentMethod = $request->input('payment_method', 'cash'); // cash or balance

        // Case 1: Top-Up Member Langsung Pilih Paket Billing
        if (!empty($packageId)) {
            $pkg = BillingPackage::findOrFail($packageId);
            $addSeconds = $pkg->duration_minutes * 60;

            if ($paymentMethod === 'balance') {
                if ($member->balance < $pkg->price) {
                    return back()->with('error', "Saldo member {$member->username} tidak mencukupi untuk membeli {$pkg->name} (Harga: Rp " . number_format($pkg->price, 0, ',', '.') . ").");
                }
                $member->decrement('balance', $pkg->price);
            }

            $member->increment('saved_seconds', $addSeconds);

            // If member is currently playing on an active PC, add time to that PC session too
            $activePc = PcClient::where('current_member_id', $member->id)->where('is_locked', false)->first();
            if ($activePc && $activePc->session_end_at) {
                $activePc->session_end_at = $activePc->session_end_at->copy()->addSeconds($addSeconds);
                $activePc->remaining_seconds_cache = $activePc->computeRemainingSeconds();
                $activePc->save();
            }

            Order::create([
                'order_code' => 'PKT-' . strtoupper(substr(uniqid(), -5)),
                'order_type' => 'billing',
                'pc_code' => $activePc ? $activePc->pc_code : 'MEMBER',
                'member_id' => $member->id,
                'billing_package_id' => $pkg->id,
                'total_price' => $pkg->price,
                'payment_method' => $paymentMethod,
                'payment_status' => 'paid',
                'kitchen_status' => 'completed',
                'items_summary' => "Top-Up Paket Member ({$member->username}): {$pkg->name} (+{$pkg->duration_minutes}m)",
            ]);

            return back()->with('success', "Paket {$pkg->name} (+{$pkg->duration_minutes} menit) berhasil ditambahkan ke akun member {$member->username}.");
        }

        // Case 2: Top-Up Saldo (Rp) atau Menit Manual
        $amount = max(0, (int) $request->input('amount', 0));
        $addMinutes = max(0, (int) $request->input('add_minutes', 0));

        if ($amount > 0) {
            $member->increment('balance', $amount);

            Order::create([
                'order_code' => 'TOP-' . strtoupper(substr(uniqid(), -5)),
                'order_type' => 'billing',
                'pc_code' => 'KASIR',
                'member_id' => $member->id,
                'total_price' => $amount,
                'payment_method' => 'cash',
                'payment_status' => 'paid',
                'kitchen_status' => 'completed',
                'items_summary' => "Top-Up Saldo Member ({$member->username}): Rp " . number_format($amount, 0, ',', '.'),
            ]);
        }

        if ($addMinutes > 0) {
            $member->increment('saved_seconds', $addMinutes * 60);
            $activePc = PcClient::where('current_member_id', $member->id)->where('is_locked', false)->first();
            if ($activePc && $activePc->session_end_at) {
                $activePc->session_end_at = $activePc->session_end_at->copy()->addSeconds($addMinutes * 60);
                $activePc->remaining_seconds_cache = $activePc->computeRemainingSeconds();
                $activePc->save();
            }
        }

        return back()->with('success', "Top-up akun member {$member->username} berhasil diproses.");
    }

    public function storeMember(Request $request)
    {
        $request->validate([
            'username' => 'required|unique:members,username',
            'name' => 'required',
        ]);

        $member = Member::create([
            'username' => strtolower(trim($request->input('username'))),
            'name' => $request->input('name'),
            'password' => Hash::make($request->input('password', 'password')),
            'balance' => max(0, (int) $request->input('balance', 0)),
            'saved_seconds' => max(0, (int) $request->input('minutes', 0)) * 60,
        ]);

        // Optional: if a package_id was also chosen during registration, apply it
        if ($request->filled('package_id')) {
            $pkg = BillingPackage::find($request->input('package_id'));
            if ($pkg) {
                $member->increment('saved_seconds', $pkg->duration_minutes * 60);
            }
        }

        return back()->with('success', "Member baru ({$member->username}) berhasil didaftarkan.");
    }

    public function updateMember(Request $request, Member $member)
    {
        $request->validate([
            'username' => 'required|unique:members,username,' . $member->id,
            'name' => 'required',
        ]);

        $member->username = strtolower(trim($request->input('username')));
        $member->name = $request->input('name');
        $member->balance = max(0, (int) $request->input('balance', 0));
        $member->saved_seconds = max(0, (int) $request->input('saved_minutes', 0)) * 60;

        if ($request->filled('password')) {
            $member->password = Hash::make($request->input('password'));
        }
        $member->save();

        // Sync active PC timer if member is currently logged in on an active PC
        $activePc = PcClient::where('current_member_id', $member->id)->where('is_locked', false)->first();
        if ($activePc) {
            $activePc->session_end_at = Carbon::now()->addSeconds($member->saved_seconds);
            $activePc->remaining_seconds_cache = $member->saved_seconds;
            $activePc->save();
        }

        return back()->with('success', "Data member {$member->username} berhasil diperbarui.");
    }

    public function destroyMember(Member $member)
    {
        $uname = $member->username;
        $member->delete();
        return back()->with('success', "Member {$uname} berhasil dihapus.");
    }

    public function storeMenu(Request $request)
    {
        FoodMenu::create([
            'name' => $request->input('name'),
            'category' => $request->input('category', 'makanan'),
            'price' => max(0, (int) $request->input('price', 10000)),
            'stock' => max(0, (int) $request->input('stock', 50)),
            'is_available' => true,
        ]);

        return back()->with('success', 'Menu makanan/minuman baru berhasil ditambahkan.');
    }

    public function updateMenu(Request $request, FoodMenu $menu)
    {
        $menu->update([
            'name' => $request->input('name'),
            'category' => $request->input('category', 'makanan'),
            'price' => max(0, (int) $request->input('price', 0)),
            'stock' => max(0, (int) $request->input('stock', 0)),
        ]);

        return back()->with('success', "Menu {$menu->name} berhasil diperbarui.");
    }

    public function destroyMenu(FoodMenu $menu)
    {
        $name = $menu->name;
        $menu->delete();
        return back()->with('success', "Menu {$name} berhasil dihapus.");
    }

    public function storePackage(Request $request)
    {
        BillingPackage::create([
            'name' => $request->input('name'),
            'duration_minutes' => max(1, (int) $request->input('duration_minutes', 60)),
            'pc_type' => $request->input('pc_type', 'all'),
            'price' => max(0, (int) $request->input('price', 7000)),
            'description' => $request->input('description', 'Paket billing warnet'),
            'is_active' => true,
        ]);

        return back()->with('success', 'Paket billing baru berhasil ditambahkan.');
    }

    public function updatePackage(Request $request, BillingPackage $package)
    {
        $package->update([
            'name' => $request->input('name'),
            'duration_minutes' => max(1, (int) $request->input('duration_minutes', 60)),
            'pc_type' => $request->input('pc_type', 'all'),
            'price' => max(0, (int) $request->input('price', 0)),
            'description' => $request->input('description', ''),
        ]);

        return back()->with('success', "Paket billing {$package->name} berhasil diperbarui.");
    }

    public function destroyPackage(BillingPackage $package)
    {
        $name = $package->name;
        $package->delete();
        return back()->with('success', "Paket billing {$name} berhasil dihapus.");
    }

    public function updateKitchenStatus(Request $request, Order $order)
    {
        $status = $request->input('kitchen_status');
        $order->kitchen_status = $status;
        if ($request->has('mark_paid') && $request->input('mark_paid') == '1') {
            $order->payment_status = 'paid';
        }
        $order->save();

        $pc = PcClient::where('pc_code', $order->pc_code)->first();
        if ($pc && !$pc->is_locked) {
            // Hanya kirim notifikasi jika pemakai PC saat ini adalah pemesan yang sama
            $isSameUser = ($order->member_id && $pc->current_member_id == $order->member_id)
                       || (!$order->member_id && !$pc->current_member_id);

            if ($isSameUser) {
                if ($status === 'cooking') {
                    $pc->admin_message = "Pesanan {$order->order_code} ({$order->items_summary}) sedang dimasak di dapur!";
                    $pc->message_id = ((int) $pc->message_id) + 1;
                    $pc->save();
                } elseif ($status === 'ready') {
                    $pc->admin_message = "Pesanan {$order->order_code} ({$order->items_summary}) SUDAH SIAP dan sedang diantar ke {$pc->pc_code}!";
                    $pc->message_id = ((int) $pc->message_id) + 1;
                    $pc->save();
                }
            }
        }

        return back()->with('success', "Status pesanan {$order->order_code} diperbarui menjadi " . strtoupper($status) . ".");
    }

    public function confirmOrderPayment(Order $order)
    {
        if ($order->payment_status !== 'paid') {
            $order->payment_status = 'paid';
            if ($order->order_type === 'billing') {
                $order->kitchen_status = 'completed';
            }
            $order->save();

            if ($order->order_type === 'billing' && $order->billingPackage) {
                $pc = PcClient::where('pc_code', $order->pc_code)->first();
                if ($pc) {
                    $addSeconds = $order->billingPackage->duration_minutes * 60;
                    $baseTime = (!$pc->is_locked && $pc->session_end_at && $pc->session_end_at->isFuture())
                        ? $pc->session_end_at
                        : Carbon::now();
                    $pc->is_locked = false;
                    $pc->status = 'active';
                    $pc->session_end_at = $baseTime->copy()->addSeconds($addSeconds);
                    $pc->remaining_seconds_cache = $pc->computeRemainingSeconds();
                    $pc->save();
                }
            }
        }

        return back()->with('success', "Pembayaran untuk {$order->order_code} berhasil dikonfirmasi LUNAS.");
    }

    public function storePc(Request $request)
    {
        $request->validate([
            'pc_code' => 'required|unique:pc_clients,pc_code',
            'pc_type' => 'required|in:reguler,vip,vip++',
        ]);

        $type = $request->input('pc_type', 'reguler');
        $defaultRate = match ($type) {
            'vip' => 8000,
            'vip++' => 12000,
            default => 5000,
        };

        $code = strtoupper(trim($request->input('pc_code')));
        $name = trim($request->input('name')) ?: "Terminal $code";

        $pc = PcClient::create([
            'pc_code' => $code,
            'name' => $name,
            'pc_type' => $type,
            'hourly_rate' => max(0, (int) $request->input('hourly_rate', $defaultRate)),
            'specs' => $request->input('specs'),
            'status' => 'locked',
            'is_locked' => true,
            'remaining_seconds_cache' => 0,
        ]);

        return back()->with('success', "Terminal {$pc->pc_code} ({$pc->pc_type}) berhasil ditambahkan.");
    }

    public function updatePc(Request $request, PcClient $pc)
    {
        $request->validate([
            'pc_code' => 'required|unique:pc_clients,pc_code,' . $pc->id,
            'pc_type' => 'required|in:reguler,vip,vip++',
        ]);

        $pc->update([
            'pc_code' => strtoupper(trim($request->input('pc_code'))),
            'name' => trim($request->input('name')) ?: $pc->name,
            'pc_type' => $request->input('pc_type'),
            'hourly_rate' => max(0, (int) $request->input('hourly_rate', $pc->hourly_rate)),
            'specs' => $request->input('specs'),
        ]);

        return back()->with('success', "Data terminal {$pc->pc_code} berhasil diperbarui.");
    }

    public function destroyPc(PcClient $pc)
    {
        if (!$pc->is_locked) {
            return back()->with('error', "Terminal {$pc->pc_code} sedang aktif digunakan, silakan kunci terlebih dahulu sebelum menghapus.");
        }

        $code = $pc->pc_code;
        $pc->delete();
        return back()->with('success', "Terminal {$code} berhasil dihapus.");
    }

    public function batchGeneratePcs(Request $request)
    {
        $regCount = max(0, min(50, (int) $request->input('count_reguler', 6)));
        $vipCount = max(0, min(50, (int) $request->input('count_vip', 3)));
        $vipPlusCount = max(0, min(50, (int) $request->input('count_vip_plus', 1)));

        $totalDesired = $regCount + $vipCount + $vipPlusCount;
        if ($totalDesired < 1) {
            return back()->with('error', 'Jumlah total terminal PC minimal 1 unit.');
        }

        $index = 1;

        // 1. Reguler PCs
        for ($i = 0; $i < $regCount; $i++, $index++) {
            $code = sprintf('PC-%02d', $index);
            $pc = PcClient::firstOrNew(['pc_code' => $code]);
            if (!$pc->exists) {
                $pc->name = "Terminal Reguler #$index";
                $pc->status = 'locked';
                $pc->is_locked = true;
            }
            $pc->pc_type = 'reguler';
            $pc->hourly_rate = (int) $request->input('rate_reguler', 5000);
            if ($request->filled('specs_reguler') || !$pc->specs) {
                $pc->specs = $request->input('specs_reguler', 'Core i5-12400F / RTX 3060 12GB / RAM 16GB / Monitor 165Hz');
            }
            $pc->save();
        }

        // 2. VIP PCs
        for ($i = 0; $i < $vipCount; $i++, $index++) {
            $code = sprintf('PC-%02d', $index);
            $pc = PcClient::firstOrNew(['pc_code' => $code]);
            if (!$pc->exists) {
                $pc->name = "Terminal VIP #$index";
                $pc->status = 'locked';
                $pc->is_locked = true;
            }
            $pc->pc_type = 'vip';
            $pc->hourly_rate = (int) $request->input('rate_vip', 8000);
            if ($request->filled('specs_vip') || !$pc->specs) {
                $pc->specs = $request->input('specs_vip', 'Core i7-13700F / RTX 4070 12GB / RAM 32GB / Monitor 240Hz Curved');
            }
            $pc->save();
        }

        // 3. VIP++ PCs
        for ($i = 0; $i < $vipPlusCount; $i++, $index++) {
            $code = sprintf('PC-%02d', $index);
            $pc = PcClient::firstOrNew(['pc_code' => $code]);
            if (!$pc->exists) {
                $pc->name = "Terminal VIP++ Sultan #$index";
                $pc->status = 'locked';
                $pc->is_locked = true;
            }
            $pc->pc_type = 'vip++';
            $pc->hourly_rate = (int) $request->input('rate_vip_plus', 12000);
            if ($request->filled('specs_vip_plus') || !$pc->specs) {
                $pc->specs = $request->input('specs_vip_plus', 'Core i9-14900K / RTX 4090 24GB / RAM 64GB / Monitor 360Hz ROG + Kursi Gaming Titan');
            }
            $pc->save();
        }

        // Opsi pangkas PC berlebih jika dipilih
        if ($request->input('trim_excess') == '1') {
            $extraPcs = PcClient::where('is_locked', true)
                ->where('pc_code', '>', sprintf('PC-%02d', $totalDesired))
                ->get();
            foreach ($extraPcs as $extra) {
                $extra->delete();
            }
        }

        return back()->with('success', "Konfigurasi terminal berhasil diterapkan: $regCount Reguler, $vipCount VIP, $vipPlusCount VIP++ (Total $totalDesired PC).");
    }
}
