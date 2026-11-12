<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="5">
    <title>Layar Antrian Dapur (KDS) - Ukikura iCafe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .mono { font-family: 'JetBrains Mono', monospace; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body x-data="{
        isDark: localStorage.getItem('theme_dark') !== 'false',
        toggleTheme() {
            this.isDark = !this.isDark;
            localStorage.setItem('theme_dark', this.isDark);
            if (this.isDark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
    }"
    x-init="if (isDark) document.documentElement.classList.add('dark'); else document.documentElement.classList.remove('dark');"
    class="antialiased">

    <div :class="isDark ? 'dark' : ''" class="min-h-screen w-full bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 flex flex-col">

        <!-- Header -->
        <header class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 px-6 py-4 shadow-sm">
            <div class="max-w-[1600px] mx-auto flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="grid size-10 shrink-0 place-content-center rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 shadow-sm">
                        <svg width="20" height="auto" viewBox="0 0 50 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="fill-white">
                            <path d="M16.4992 2H37.5808L22.0816 24.9729H1L16.4992 2Z" />
                            <path d="M17.4224 27.102L11.4192 36H33.5008L49 13.0271H32.7024L23.2064 27.102H17.4224Z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100">Layar Antrian Dapur (KDS)</h1>
                            <span class="px-2.5 py-0.5 rounded-full bg-blue-50 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 text-xs font-medium">
                                Auto-Refresh 5 Detik
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Pesanan dari PC Client C++ tampil otomatis dan mengirim status balik ke layar PC pemesan
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button @click="toggleTheme()"
                            title="Ganti Tema Terang / Gelap"
                            class="flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <svg x-show="isDark" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>
                        </svg>
                        <svg x-show="!isDark" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
                        </svg>
                    </button>

                    <a href="{{ route('kasir.dashboard') }}"
                       class="px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-200 text-xs font-semibold transition">
                        Kembali ke Dashboard Kasir
                    </a>

                    <a href="{{ route('kasir.dapur') }}"
                       class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold shadow-sm transition">
                        Muat Ulang
                    </a>
                </div>
            </div>
        </header>

        <main class="max-w-[1600px] w-full mx-auto p-6 flex-1 space-y-6">
            @if(session('success'))
                <div class="p-4 rounded-xl border border-green-200 dark:border-green-800/60 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 text-sm font-medium">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- COLUMN 1: PESANAN BARU MASUK -->
                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 shadow-sm flex flex-col">
                    <div class="flex items-center justify-between pb-4 mb-4 border-b border-gray-200 dark:border-gray-800">
                        <div class="flex items-center gap-2.5">
                            <span class="h-2.5 w-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                            <h2 class="font-semibold text-sm text-gray-900 dark:text-gray-100">1. Pesanan Baru Masuk</h2>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 text-xs font-semibold">
                            {{ $waitingOrders->count() }} Antrian
                        </span>
                    </div>

                    <div class="space-y-4 flex-1">
                        @forelse($waitingOrders as $ord)
                            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950 p-4 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="px-3 py-1 rounded-md bg-blue-600 text-white font-bold text-xs mono">
                                        {{ $ord->pc_code }}
                                    </span>
                                    <div class="text-right">
                                        <div class="text-xs font-semibold text-gray-800 dark:text-gray-200 mono">{{ $ord->order_code }}</div>
                                        <div class="text-[11px] text-gray-400">{{ $ord->created_at?->format('H:i:s') }}</div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-500 dark:text-gray-400">Pemesan: <strong class="text-gray-900 dark:text-gray-100">{{ $ord->member ? $ord->member->name : 'Guest' }}</strong></span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $ord->payment_status === 'paid' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400' }}">
                                        {{ strtoupper($ord->payment_method) }} - {{ $ord->payment_status === 'paid' ? 'LUNAS' : 'PENDING' }}
                                    </span>
                                </div>

                                <div class="rounded-lg bg-white dark:bg-gray-900 p-3 border border-gray-200 dark:border-gray-800 space-y-1.5">
                                    @foreach($ord->items as $item)
                                        <div class="flex justify-between text-xs font-semibold text-gray-900 dark:text-gray-100">
                                            <span>{{ $item->qty }}x {{ $item->menu_name }}</span>
                                            <span class="text-gray-500 mono">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                                        </div>
                                    @endforeach
                                    @if($ord->items->isEmpty())
                                        <div class="text-xs font-semibold text-gray-900 dark:text-gray-100">{{ $ord->items_summary }}</div>
                                    @endif
                                </div>

                                @if($ord->notes)
                                    <div class="p-2.5 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/50 text-xs text-amber-700 dark:text-amber-300 font-medium">
                                        Catatan Dapur: "{{ $ord->notes }}"
                                    </div>
                                @endif

                                <form action="{{ route('kasir.order.kitchen', $ord) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="kitchen_status" value="cooking">
                                    <button type="submit"
                                            class="w-full py-2 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 font-semibold text-xs transition shadow-sm">
                                        Mulai Masak Sekarang
                                    </button>
                                </form>
                            </div>
                        @empty
                            <div class="py-16 text-center text-gray-400 dark:text-gray-500 text-xs">
                                Tidak ada antrian pesanan baru.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- COLUMN 2: SEDANG DIMASAK -->
                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 shadow-sm flex flex-col">
                    <div class="flex items-center justify-between pb-4 mb-4 border-b border-gray-200 dark:border-gray-800">
                        <div class="flex items-center gap-2.5">
                            <span class="h-2.5 w-2.5 rounded-full bg-orange-500"></span>
                            <h2 class="font-semibold text-sm text-gray-900 dark:text-gray-100">2. Sedang Dimasak di Dapur</h2>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300 text-xs font-semibold">
                            {{ $cookingOrders->count() }} Dimasak
                        </span>
                    </div>

                    <div class="space-y-4 flex-1">
                        @forelse($cookingOrders as $ord)
                            <div class="rounded-xl border border-orange-200 dark:border-orange-800/50 bg-gray-50 dark:bg-gray-950 p-4 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="px-3 py-1 rounded-md bg-orange-500 text-white font-bold text-xs mono">
                                        {{ $ord->pc_code }}
                                    </span>
                                    <span class="text-xs font-semibold text-gray-500 mono">{{ $ord->order_code }}</span>
                                </div>

                                <div class="rounded-lg bg-white dark:bg-gray-900 p-3 border border-gray-200 dark:border-gray-800 space-y-1.5">
                                    @foreach($ord->items as $item)
                                        <div class="flex justify-between text-xs font-semibold text-gray-900 dark:text-gray-100">
                                            <span>{{ $item->qty }}x {{ $item->menu_name }}</span>
                                        </div>
                                    @endforeach
                                    @if($ord->items->isEmpty())
                                        <div class="text-xs font-semibold text-gray-900 dark:text-gray-100">{{ $ord->items_summary }}</div>
                                    @endif
                                </div>

                                @if($ord->notes)
                                    <div class="p-2.5 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/50 text-xs text-amber-700 dark:text-amber-300 font-medium">
                                        Catatan: "{{ $ord->notes }}"
                                    </div>
                                @endif

                                <form action="{{ route('kasir.order.kitchen', $ord) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="kitchen_status" value="ready">
                                    <button type="submit"
                                            class="w-full py-2 rounded-lg bg-green-600 hover:bg-green-500 text-white font-semibold text-xs transition shadow-sm">
                                        Makanan Siap Diantar
                                    </button>
                                </form>
                            </div>
                        @empty
                            <div class="py-16 text-center text-gray-400 dark:text-gray-500 text-xs">
                                Belum ada pesanan yang sedang dimasak.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- COLUMN 3: SIAP DIANTAR -->
                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 shadow-sm flex flex-col">
                    <div class="flex items-center justify-between pb-4 mb-4 border-b border-gray-200 dark:border-gray-800">
                        <div class="flex items-center gap-2.5">
                            <span class="h-2.5 w-2.5 rounded-full bg-green-500"></span>
                            <h2 class="font-semibold text-sm text-gray-900 dark:text-gray-100">3. Siap Diantar ke Terminal PC</h2>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 text-xs font-semibold">
                            {{ $readyOrders->count() }} Siap Antar
                        </span>
                    </div>

                    <div class="space-y-4 flex-1">
                        @forelse($readyOrders as $ord)
                            <div class="rounded-xl border border-green-200 dark:border-green-800/50 bg-gray-50 dark:bg-gray-950 p-4 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="px-3 py-1 rounded-md bg-green-600 text-white font-bold text-xs mono">
                                        ANTAR KE {{ $ord->pc_code }}
                                    </span>
                                    <span class="text-xs font-semibold text-gray-500 mono">{{ $ord->order_code }}</span>
                                </div>

                                <div class="rounded-lg bg-white dark:bg-gray-900 p-3 border border-gray-200 dark:border-gray-800">
                                    <div class="text-xs font-semibold text-gray-900 dark:text-gray-100">{{ $ord->items_summary }}</div>
                                    <div class="text-xs text-green-600 dark:text-green-400 font-semibold mt-1 mono">
                                        Total: Rp {{ number_format($ord->total_price, 0, ',', '.') }} ({{ strtoupper($ord->payment_method) }} - {{ strtoupper($ord->payment_status) }})
                                    </div>
                                </div>

                                <form action="{{ route('kasir.order.kitchen', $ord) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="kitchen_status" value="completed">
                                    <input type="hidden" name="mark_paid" value="1">
                                    <button type="submit"
                                            class="w-full py-2 rounded-lg bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs transition shadow-sm">
                                        Sudah Diantar ke {{ $ord->pc_code }} (Selesai)
                                    </button>
                                </form>
                            </div>
                        @empty
                            <div class="py-16 text-center text-gray-400 dark:text-gray-500 text-xs">
                                Tidak ada pesanan yang menunggu diantar.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
