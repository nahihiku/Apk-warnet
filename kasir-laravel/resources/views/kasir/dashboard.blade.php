<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Kasir & Kontrol Warnet - Ukikura iCafe</title>
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
        open: localStorage.getItem('sidebar_open') !== 'false',
        selected: localStorage.getItem('active_sidebar_tab') || 'Dashboard',
        pcFilter: 'all',
        showBatchPcModal: false,
        showAddPcModal: false,
        editPcId: null,
        editMemberId: null,
        editMenuId: null,
        editPkgId: null,
        setTab(tab) {
            this.selected = tab;
            localStorage.setItem('active_sidebar_tab', tab);
        },
        toggleSidebar() {
            this.open = !this.open;
            localStorage.setItem('sidebar_open', this.open);
        },
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
    class="antialiased selection:bg-blue-500 selection:text-white">

    <div :class="isDark ? 'dark' : ''" class="flex min-h-screen w-full">
        <div class="flex w-full bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100">

            <!-- ========================================================= -->
            <!-- COLLAPSIBLE SIDEBAR (Dari Komponen dashboard.txt)         -->
            <!-- ========================================================= -->
            <nav :class="open ? 'w-64' : 'w-16'"
                 class="sticky top-0 h-screen shrink-0 border-r transition-all duration-300 ease-in-out border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-2 shadow-sm flex flex-col justify-between z-30">

                <div>
                    <!-- TitleSection -->
                    <div class="mb-6 border-b border-gray-200 dark:border-gray-800 pb-4">
                        <div class="flex cursor-pointer items-center justify-between rounded-md p-2 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="grid size-10 shrink-0 place-content-center rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 shadow-sm">
                                    <svg width="20" height="auto" viewBox="0 0 50 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="fill-white">
                                        <path d="M16.4992 2H37.5808L22.0816 24.9729H1L16.4992 2Z" />
                                        <path d="M17.4224 27.102L11.4192 36H33.5008L49 13.0271H32.7024L23.2064 27.102H17.4224Z" />
                                    </svg>
                                </div>
                                <div x-show="open" x-transition.opacity class="truncate">
                                    <span class="block text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                                        Ukikura iCafe
                                    </span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400 truncate">
                                        Kasir & Server Pro
                                    </span>
                                </div>
                            </div>
                            <svg x-show="open" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 dark:text-gray-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m6 9 6 6 6-6"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Navigation Menu Items -->
                    <div class="space-y-1 mb-8">
                        <!-- 1. Dashboard -->
                        <button @click="setTab('Dashboard')"
                                :class="selected === 'Dashboard'
                                    ? 'bg-blue-50 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 shadow-sm border-l-2 border-blue-500'
                                    : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-200'"
                                class="relative flex h-11 w-full items-center rounded-md transition-all duration-200">
                            <div class="grid h-full w-12 shrink-0 place-content-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
                                </svg>
                            </div>
                            <span x-show="open" class="text-sm font-medium truncate">Dashboard</span>
                        </button>

                        <!-- 2. Terminal PC -->
                        <button @click="setTab('Terminal PC')"
                                :class="selected === 'Terminal PC'
                                    ? 'bg-blue-50 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 shadow-sm border-l-2 border-blue-500'
                                    : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-200'"
                                class="relative flex h-11 w-full items-center rounded-md transition-all duration-200">
                            <div class="grid h-full w-12 shrink-0 place-content-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/>
                                </svg>
                            </div>
                            <span x-show="open" class="text-sm font-medium truncate">Terminal PC</span>
                            <span x-show="open" id="sidebar-active-pcs" class="absolute right-3 flex h-5 min-w-5 px-1.5 items-center justify-center rounded-full bg-blue-500 dark:bg-blue-600 text-xs text-white font-medium">
                                {{ $activePcsCount }}
                            </span>
                        </button>

                        <!-- 3. Transaksi & QRIS -->
                        <button @click="setTab('Transaksi & QRIS')"
                                :class="selected === 'Transaksi & QRIS'
                                    ? 'bg-blue-50 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 shadow-sm border-l-2 border-blue-500'
                                    : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-200'"
                                class="relative flex h-11 w-full items-center rounded-md transition-all duration-200">
                            <div class="grid h-full w-12 shrink-0 place-content-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                </svg>
                            </div>
                            <span x-show="open" class="text-sm font-medium truncate">Transaksi & QRIS</span>
                            <span x-show="open" id="sidebar-pending-qris" class="absolute right-3 flex h-5 min-w-5 px-1.5 items-center justify-center rounded-full bg-blue-500 dark:bg-blue-600 text-xs text-white font-medium">
                                {{ $pendingQrisCount }}
                            </span>
                        </button>

                        <!-- 4. Antrian Dapur -->
                        <button @click="setTab('Antrian Dapur')"
                                :class="selected === 'Antrian Dapur'
                                    ? 'bg-blue-50 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 shadow-sm border-l-2 border-blue-500'
                                    : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-200'"
                                class="relative flex h-11 w-full items-center rounded-md transition-all duration-200">
                            <div class="grid h-full w-12 shrink-0 place-content-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                                </svg>
                            </div>
                            <span x-show="open" class="text-sm font-medium truncate">Antrian Dapur</span>
                            <span x-show="open" id="sidebar-kitchen-badge" class="absolute right-3 flex h-5 min-w-5 px-1.5 items-center justify-center rounded-full bg-blue-500 dark:bg-blue-600 text-xs text-white font-medium">
                                {{ $kitchenQueueCount }}
                            </span>
                        </button>

                        <!-- 5. Menu & Paket -->
                        <button @click="setTab('Menu & Paket')"
                                :class="selected === 'Menu & Paket'
                                    ? 'bg-blue-50 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 shadow-sm border-l-2 border-blue-500'
                                    : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-200'"
                                class="relative flex h-11 w-full items-center rounded-md transition-all duration-200">
                            <div class="grid h-full w-12 shrink-0 place-content-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/>
                                </svg>
                            </div>
                            <span x-show="open" class="text-sm font-medium truncate">Menu & Paket</span>
                        </button>

                        <!-- 6. Members -->
                        <button @click="setTab('Members')"
                                :class="selected === 'Members'
                                    ? 'bg-blue-50 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 shadow-sm border-l-2 border-blue-500'
                                    : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-200'"
                                class="relative flex h-11 w-full items-center rounded-md transition-all duration-200">
                            <div class="grid h-full w-12 shrink-0 place-content-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                </svg>
                            </div>
                            <span x-show="open" class="text-sm font-medium truncate">Members</span>
                            <span x-show="open" class="absolute right-3 flex h-5 min-w-5 px-1.5 items-center justify-center rounded-full bg-blue-500 dark:bg-blue-600 text-xs text-white font-medium">
                                {{ $members->count() }}
                            </span>
                        </button>
                    </div>

                    <!-- Secondary Account / System Links -->
                    <div x-show="open" class="border-t border-gray-200 dark:border-gray-800 pt-4 space-y-1">
                        <div class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                            Layar Eksternal
                        </div>
                        <a href="{{ route('kasir.dapur') }}"
                           class="relative flex h-11 w-full items-center rounded-md transition-all duration-200 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-200">
                            <div class="grid h-full w-12 shrink-0 place-content-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>
                                </svg>
                            </div>
                            <span class="text-sm font-medium truncate">Monitor KDS Dapur</span>
                        </a>
                    </div>
                </div>

                <!-- ToggleClose Footer -->
                <button @click="toggleSidebar()"
                        class="border-t border-gray-200 dark:border-gray-800 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800 rounded-md">
                    <div class="flex items-center p-2">
                        <div class="grid size-10 shrink-0 place-content-center">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                 :class="open ? 'rotate-180' : ''"
                                 class="h-4 w-4 transition-transform duration-300 text-gray-500 dark:text-gray-400"
                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m6 17 5-5-5-5"/><path d="m13 17 5-5-5-5"/>
                            </svg>
                        </div>
                        <span x-show="open" class="text-sm font-medium text-gray-600 dark:text-gray-300">
                            Hide
                        </span>
                    </div>
                </button>
            </nav>

            <!-- ========================================================= -->
            <!-- MAIN CONTENT AREA                                         -->
            <!-- ========================================================= -->
            <div class="flex-1 bg-gray-50 dark:bg-gray-950 p-6 overflow-auto">

                <!-- Top Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100" x-text="selected">Dashboard</h1>
                        <p class="text-gray-600 dark:text-gray-400 mt-1 text-sm" x-show="selected === 'Dashboard'">
                            Ringkasan performa warnet, aktivitas terbaru, dan statistik sistem real-time
                        </p>
                        <p class="text-gray-600 dark:text-gray-400 mt-1 text-sm" x-show="selected === 'Terminal PC'" x-cloak>
                            Kontrol penuh seluruh unit PC Client C++: buka paket, tambah waktu, kunci layar, dan kirim pesan
                        </p>
                        <p class="text-gray-600 dark:text-gray-400 mt-1 text-sm" x-show="selected === 'Transaksi & QRIS'" x-cloak>
                            Daftar pembayaran QRIS, potongan saldo member, serta riwayat pembelian billing dan makanan
                        </p>
                        <p class="text-gray-600 dark:text-gray-400 mt-1 text-sm" x-show="selected === 'Antrian Dapur'" x-cloak>
                            Papan kontrol pesanan makanan dan minuman dari PC Client C++ (Masuk, Dimasak, Siap Diantar)
                        </p>
                        <p class="text-gray-600 dark:text-gray-400 mt-1 text-sm" x-show="selected === 'Menu & Paket'" x-cloak>
                            Kelola katalog makanan, minuman, edit harga/stok, serta top-up paket billing langsung ke member
                        </p>
                        <p class="text-gray-600 dark:text-gray-400 mt-1 text-sm" x-show="selected === 'Members'" x-cloak>
                            Top-up paket billing langsung ke member, isi saldo/waktu, edit data member, dan registrasi baru
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="hidden md:flex items-center gap-2 px-3 py-2 rounded-lg bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 text-xs font-medium text-gray-600 dark:text-gray-400">
                            <span class="h-2 w-2 rounded-full bg-green-500 animate-pulse"></span>
                            <span>Sync Client C++ (2s)</span>
                        </div>

                        <button @click="setTab('Transaksi & QRIS')"
                                title="Lihat Transaksi & QRIS"
                                class="relative p-2.5 rounded-lg bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
                            </svg>
                            <span class="absolute -top-1 -right-1 h-3 w-3 bg-red-500 rounded-full"></span>
                        </button>

                        <button @click="toggleTheme()"
                                title="Ganti Tema Terang / Gelap"
                                class="flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
                            <svg x-show="isDark" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>
                            </svg>
                            <svg x-show="!isDark" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
                            </svg>
                        </button>

                        <button @click="setTab('Members')"
                                title="Manajemen Member"
                                class="p-2.5 rounded-lg bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                            </svg>
                        </button>
                    </div>
                </div>

                @if(session('success'))
                    <div class="mb-6 p-4 rounded-xl border border-green-200 dark:border-green-800/60 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 text-sm font-medium flex items-center justify-between">
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 p-4 rounded-xl border border-red-200 dark:border-red-800/60 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 text-sm font-medium flex items-center justify-between">
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                <!-- ========================================================= -->
                <!-- HALAMAN 1: DASHBOARD                                      -->
                <!-- ========================================================= -->
                <div x-show="selected === 'Dashboard'">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div class="p-6 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 dark:text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                    </svg>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>
                                </svg>
                            </div>
                            <h3 class="font-medium text-gray-600 dark:text-gray-400 mb-1 text-sm">Total Omzet (Lunas)</h3>
                            <p id="kpi-revenue" class="text-2xl font-bold text-gray-900 dark:text-gray-100 mono">
                                Rp {{ number_format($totalRevenueToday, 0, ',', '.') }}
                            </p>
                            <p class="text-xs text-green-600 dark:text-green-400 mt-1">Billing Warnet & Pesanan Makanan</p>
                        </div>

                        <div @click="setTab('Terminal PC')" class="cursor-pointer p-6 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-2 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600 dark:text-green-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/>
                                    </svg>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>
                                </svg>
                            </div>
                            <h3 class="font-medium text-gray-600 dark:text-gray-400 mb-1 text-sm">Terminal PC Aktif</h3>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mono">
                                <span id="kpi-active-pcs">{{ $activePcsCount }}</span> / <span id="kpi-total-pcs">{{ $pcs->count() }}</span> PC
                            </p>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1.5 flex items-center gap-1.5 flex-wrap">
                                <span class="px-1.5 py-0.5 rounded bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-bold">{{ $regulerPcsCount }} Reguler</span>
                                <span class="px-1.5 py-0.5 rounded bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 font-bold">{{ $vipPcsCount }} VIP</span>
                                <span class="px-1.5 py-0.5 rounded bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 font-bold">{{ $vipPlusPcsCount }} VIP++</span>
                            </p>
                        </div>

                        <div @click="setTab('Antrian Dapur')" class="cursor-pointer p-6 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-2 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600 dark:text-purple-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                                    </svg>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>
                                </svg>
                            </div>
                            <h3 class="font-medium text-gray-600 dark:text-gray-400 mb-1 text-sm">Antrian Dapur (KDS)</h3>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mono">
                                <span id="kpi-kitchen">{{ $kitchenQueueCount }}</span> Pesanan
                            </p>
                            <p class="text-xs text-green-600 dark:text-green-400 mt-1">Klik untuk kelola antrian masak</p>
                        </div>

                        <div @click="setTab('Transaksi & QRIS')" class="cursor-pointer p-6 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-2 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-orange-600 dark:text-orange-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>
                                    </svg>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>
                                </svg>
                            </div>
                            <h3 class="font-medium text-gray-600 dark:text-gray-400 mb-1 text-sm">Menunggu Konfirmasi QRIS</h3>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mono">
                                <span id="kpi-qris">{{ $pendingQrisCount }}</span> Transaksi
                            </p>
                            <p class="text-xs text-orange-600 dark:text-orange-400 mt-1">Klik untuk konfirmasi pembayaran</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div class="lg:col-span-2 space-y-6">
                            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Recent Activity</h3>
                                    <button @click="setTab('Transaksi & QRIS')" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium">
                                        Lihat Semua Transaksi
                                    </button>
                                </div>
                                <div class="space-y-3">
                                    @forelse($orders->take(6) as $ord)
                                        @php
                                            $isFood = $ord->order_type === 'food';
                                            $isPending = $ord->payment_status !== 'paid';
                                        @endphp
                                        <div class="flex items-center space-x-4 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                            <div class="p-2 rounded-lg shrink-0 {{ $isPending ? 'bg-orange-50 dark:bg-orange-900/20 text-orange-600 dark:text-orange-400' : ($isFood ? 'bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400' : 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400') }}">
                                                @if($isFood)
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                                                    </svg>
                                                @else
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                                    </svg>
                                                @endif
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                                    [{{ $ord->pc_code }}] {{ $ord->items_summary }}
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                    {{ $ord->order_code }} • {{ strtoupper($ord->payment_method) }} ({{ $ord->payment_status === 'paid' ? 'LUNAS' : 'PENDING QRIS' }})
                                                </p>
                                            </div>
                                            <div class="text-right shrink-0">
                                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 mono">Rp {{ number_format($ord->total_price, 0, ',', '.') }}</p>
                                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ $ord->created_at?->format('H:i:s') }}</p>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-sm text-gray-500 dark:text-gray-400 py-6 text-center">Belum ada aktivitas transaksi hari ini.</p>
                                    @endforelse
                                </div>
                            </div>

                            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Status Ringkas Terminal PC</h3>
                                    <button @click="setTab('Terminal PC')" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium">
                                        Buka Kontrol Penuh PC
                                    </button>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                                    @foreach($pcs as $pc)
                                        @php $rem = $pc->computeRemainingSeconds(); @endphp
                                        <div @click="setTab('Terminal PC')"
                                             class="cursor-pointer p-3 rounded-lg border transition-colors {{ $pc->is_locked ? 'border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950' : 'border-blue-500/50 bg-blue-50/20 dark:bg-blue-950/30' }}">
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="font-bold text-xs mono text-gray-900 dark:text-gray-100">{{ $pc->pc_code }}</span>
                                                <span class="text-[10px] px-1.5 py-0.5 rounded font-semibold {{ $pc->is_locked ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400' : 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400' }}">
                                                    {{ $pc->is_locked ? 'LOCKED' : 'AKTIF' }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $pc->member ? $pc->member->username : 'Kosong' }}</p>
                                            <p class="text-xs font-bold mono mt-1 {{ $rem > 0 ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400' }}">{{ gmdate('H:i:s', $rem) }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        @php
                            $occupancyPct = $pcs->count() > 0 ? round(($activePcsCount / $pcs->count()) * 100) : 0;
                            $paidCount = $orders->where('payment_status', 'paid')->count();
                            $paidPct = $orders->count() > 0 ? round(($paidCount / $orders->count()) * 100) : 100;
                        @endphp
                        <div class="space-y-6">
                            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Quick Stats</h3>
                                <div class="space-y-4">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Okupansi PC Aktif</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $occupancyPct }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div class="bg-blue-500 h-2 rounded-full" style="width: {{ $occupancyPct }}%"></div>
                                    </div>

                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Antrian Dapur Aktif</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $kitchenQueueCount }} Pesanan</span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div class="bg-orange-500 h-2 rounded-full" style="width: {{ min(100, $kitchenQueueCount * 20) }}%"></div>
                                    </div>

                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Rasio Transaksi Lunas</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $paidPct }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div class="bg-green-500 h-2 rounded-full" style="width: {{ $paidPct }}%"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Top Menu & Paket</h3>
                                    <button @click="setTab('Menu & Paket')" class="text-xs text-blue-600 dark:text-blue-400 hover:underline font-medium">Kelola</button>
                                </div>
                                <div class="space-y-3">
                                    @foreach($menus->take(5) as $menu)
                                        <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-800/60 last:border-0">
                                            <span class="text-sm text-gray-600 dark:text-gray-400 truncate pr-2">{{ $menu->name }}</span>
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100 mono shrink-0">
                                                Rp {{ number_format($menu->price, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- HALAMAN 2: TERMINAL PC                                    -->
                <!-- ========================================================= -->
                <div x-show="selected === 'Terminal PC'" x-cloak>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6 pb-6 border-b border-gray-200 dark:border-gray-800">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    Kontrol Penuh Seluruh Unit Terminal PC (Client C++)
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    Atur jumlah terminal PC, klasifikasikan tipe Reguler / VIP / VIP++, buka paket billing, atau kirim pesan pop-up ke layar user.
                                </p>
                            </div>

                            <div class="flex items-center gap-2 flex-wrap">
                                <button type="button" @click="showBatchPcModal = true"
                                        class="px-3.5 py-2 rounded-lg bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-700 text-xs font-semibold shadow-sm transition">
                                    Atur Jumlah PC
                                </button>
                                <button type="button" @click="showAddPcModal = true"
                                        class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold shadow-sm transition">
                                    + Tambah PC Baru
                                </button>
                            </div>
                        </div>

                        <!-- Filter Tabs: Semua, Reguler, VIP, VIP++ -->
                        <div class="flex items-center gap-2 mb-6 overflow-x-auto pb-1">
                            <button type="button" @click="pcFilter = 'all'"
                                    :class="pcFilter === 'all'
                                        ? 'bg-blue-600 text-white font-bold'
                                        : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'"
                                    class="px-3.5 py-1.5 rounded-lg text-xs transition">
                                Semua Terminal ({{ $pcs->count() }})
                            </button>
                            <button type="button" @click="pcFilter = 'reguler'"
                                    :class="pcFilter === 'reguler'
                                        ? 'bg-blue-600 text-white font-bold'
                                        : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'"
                                    class="px-3.5 py-1.5 rounded-lg text-xs transition flex items-center gap-1.5">
                                <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                                <span>Reguler ({{ $regulerPcsCount }})</span>
                            </button>
                            <button type="button" @click="pcFilter = 'vip'"
                                    :class="pcFilter === 'vip'
                                        ? 'bg-amber-600 text-white font-bold'
                                        : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'"
                                    class="px-3.5 py-1.5 rounded-lg text-xs transition flex items-center gap-1.5">
                                <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                                <span>VIP ({{ $vipPcsCount }})</span>
                            </button>
                            <button type="button" @click="pcFilter = 'vip++'"
                                    :class="pcFilter === 'vip++'
                                        ? 'bg-purple-600 text-white font-bold'
                                        : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'"
                                    class="px-3.5 py-1.5 rounded-lg text-xs transition flex items-center gap-1.5">
                                <span class="h-2 w-2 rounded-full bg-purple-500"></span>
                                <span>VIP++ ({{ $vipPlusPcsCount }})</span>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                            @foreach($pcs as $pc)
                                @php
                                    $rem = $pc->computeRemainingSeconds();
                                    $online = $pc->isOnline();
                                    $tierStyles = match($pc->pc_type) {
                                        'vip' => 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800/60',
                                        'vip++' => 'bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-800/60',
                                        default => 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800/60',
                                    };
                                @endphp
                                <div id="pc-card-{{ $pc->id }}"
                                     x-show="pcFilter === 'all' || pcFilter === '{{ $pc->pc_type }}'"
                                     class="rounded-xl border p-4 transition-all flex flex-col justify-between overflow-hidden {{ $pc->is_locked ? 'border-gray-200 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-950/60' : 'border-blue-500/60 dark:border-blue-500/60 bg-blue-50/20 dark:bg-blue-950/20 shadow-sm' }}">

                                    <div>
                                        <div class="flex items-center justify-between gap-1 mb-2.5">
                                            <div class="flex items-center gap-1.5 min-w-0 flex-wrap">
                                                <span id="pc-badge-{{ $pc->id }}"
                                                      class="px-2 py-0.5 rounded-md font-bold text-xs mono shrink-0 {{ $pc->is_locked ? 'bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-200' : 'bg-blue-600 text-white' }}">
                                                    {{ $pc->pc_code }}
                                                </span>

                                                <span class="text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded border {{ $tierStyles }} shrink-0">
                                                    {{ strtoupper($pc->pc_type ?? 'reguler') }}
                                                </span>

                                                <span id="pc-online-{{ $pc->id }}"
                                                      class="text-[10px] px-1.5 py-0.5 rounded font-medium truncate {{ $online ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-gray-200 dark:bg-gray-800 text-gray-500 dark:text-gray-400' }}">
                                                    {{ $online ? 'ONLINE' : 'STANDBY' }}
                                                </span>
                                            </div>

                                            <div class="flex items-center gap-1 shrink-0">
                                                <span id="pc-lock-label-{{ $pc->id }}"
                                                      class="text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded {{ $pc->is_locked ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400' : 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' }}">
                                                    {{ $pc->is_locked ? 'TERKUNCI' : 'AKTIF' }}
                                                </span>
                                                <button type="button" title="Edit Terminal PC"
                                                        @click="editPcId = (editPcId === {{ $pc->id }} ? null : {{ $pc->id }})"
                                                        class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-800 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                                    </svg>
                                                </button>
                                                @if($pc->is_locked)
                                                    <form action="{{ route('kasir.pc.delete', $pc) }}" method="POST" onsubmit="return confirm('Hapus unit {{ $pc->pc_code }}?')">
                                                        @csrf
                                                        <button type="submit" title="Hapus Terminal PC"
                                                                class="p-1 rounded hover:bg-red-100 dark:hover:bg-red-900/40 text-gray-400 hover:text-red-600">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                <path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Form Inline Edit PC -->
                                        <form x-show="editPcId === {{ $pc->id }}" x-cloak
                                              action="{{ route('kasir.pc.update', $pc) }}" method="POST"
                                              class="p-3 mb-3 rounded-lg bg-gray-100 dark:bg-gray-900 border border-blue-500/50 space-y-2">
                                            @csrf
                                            <div class="text-[11px] font-bold text-blue-600 dark:text-blue-400 uppercase">Edit Terminal {{ $pc->pc_code }}</div>
                                            <div class="grid grid-cols-2 gap-2">
                                                <div>
                                                    <label class="text-[10px] text-gray-500 block">Kode PC</label>
                                                    <input type="text" name="pc_code" value="{{ $pc->pc_code }}" required
                                                           class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded px-2 py-1 text-xs mono">
                                                </div>
                                                <div>
                                                    <label class="text-[10px] text-gray-500 block">Jenis PC</label>
                                                    <select name="pc_type" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded px-2 py-1 text-xs font-semibold">
                                                        <option value="reguler" {{ $pc->pc_type === 'reguler' ? 'selected' : '' }}>Reguler</option>
                                                        <option value="vip" {{ $pc->pc_type === 'vip' ? 'selected' : '' }}>VIP</option>
                                                        <option value="vip++" {{ $pc->pc_type === 'vip++' ? 'selected' : '' }}>VIP++</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-2 gap-2">
                                                <div>
                                                    <label class="text-[10px] text-gray-500 block">Nama Terminal</label>
                                                    <input type="text" name="name" value="{{ $pc->name }}"
                                                           class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded px-2 py-1 text-xs">
                                                </div>
                                                <div>
                                                    <label class="text-[10px] text-gray-500 block">Tarif/Jam (Rp)</label>
                                                    <input type="number" name="hourly_rate" value="{{ $pc->hourly_rate }}"
                                                           class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded px-2 py-1 text-xs mono">
                                                </div>
                                            </div>
                                            <div>
                                                <label class="text-[10px] text-gray-500 block">Spesifikasi Hardware</label>
                                                <input type="text" name="specs" value="{{ $pc->specs }}" placeholder="Contoh: i7 / RTX 4070 / 240Hz"
                                                       class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded px-2 py-1 text-xs">
                                            </div>
                                            <div class="flex justify-end gap-1.5 pt-1">
                                                <button type="button" @click="editPcId = null" class="px-2 py-1 rounded border border-gray-300 dark:border-gray-700 text-[11px] text-gray-600 dark:text-gray-400">Batal</button>
                                                <button type="submit" class="px-2.5 py-1 rounded bg-blue-600 text-white text-[11px] font-semibold">Simpan</button>
                                            </div>
                                        </form>

                                        <!-- Info Box -->
                                        <div class="rounded-lg p-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 mb-3 space-y-1.5">
                                            <div class="flex justify-between items-center text-xs gap-2">
                                                <span class="text-gray-500 dark:text-gray-400 shrink-0">User:</span>
                                                <span id="pc-member-{{ $pc->id }}" class="font-semibold text-gray-900 dark:text-gray-100 truncate text-right">
                                                    {{ $pc->member ? $pc->member->name . ' (' . $pc->member->username . ')' : 'Belum Login' }}
                                                </span>
                                            </div>
                                            <div class="flex justify-between items-center text-xs gap-2">
                                                <span class="text-gray-500 dark:text-gray-400 shrink-0">Tarif:</span>
                                                <span class="font-semibold text-gray-700 dark:text-gray-300 mono text-right">
                                                    Rp {{ number_format($pc->hourly_rate, 0, ',', '.') }}/jam
                                                </span>
                                            </div>
                                            @if($pc->specs)
                                                <div class="text-[10px] text-gray-500 dark:text-gray-400 truncate" title="{{ $pc->specs }}">
                                                    {{ $pc->specs }}
                                                </div>
                                            @endif
                                            <div class="flex justify-between items-center text-xs gap-2">
                                                <span class="text-gray-500 dark:text-gray-400 shrink-0">Saldo:</span>
                                                <span id="pc-balance-{{ $pc->id }}" class="font-semibold text-green-600 dark:text-green-400 mono truncate text-right">
                                                    Rp {{ number_format($pc->member ? $pc->member->balance : 0, 0, ',', '.') }}
                                                </span>
                                            </div>
                                            <div class="pt-1.5 border-t border-gray-100 dark:border-gray-800 flex justify-between items-center">
                                                <span class="text-[10px] text-gray-500 dark:text-gray-400 font-semibold uppercase tracking-wider">Sisa Waktu</span>
                                                <span id="pc-timer-{{ $pc->id }}"
                                                      class="text-base font-bold mono {{ $rem > 0 ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-gray-600' }}">
                                                    {{ gmdate('H:i:s', $rem) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <form action="{{ route('kasir.pc.control') }}" method="POST" class="space-y-1.5">
                                            @csrf
                                            <input type="hidden" name="pc_id" value="{{ $pc->id }}">
                                            <input type="hidden" name="action" value="unlock_package">

                                            <select name="package_id" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg px-2.5 py-1.5 text-xs text-gray-800 dark:text-gray-200 focus:border-blue-500 focus:outline-none">
                                                @php
                                                    $matchingPkgs = $packages->filter(fn($p) => in_array($p->pc_type, ['all', $pc->pc_type]));
                                                    $otherPkgs = $packages->reject(fn($p) => in_array($p->pc_type, ['all', $pc->pc_type]));
                                                @endphp
                                                <optgroup label="Paket Sesuai ({{ strtoupper($pc->pc_type) }})">
                                                    @foreach($matchingPkgs as $pkg)
                                                        <option value="{{ $pkg->id }}">
                                                            {{ $pkg->name }} (+{{ $pkg->duration_minutes }}m - Rp {{ number_format($pkg->price, 0, ',', '.') }})
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                                @if($otherPkgs->count() > 0)
                                                    <optgroup label="Paket Terminal Lain">
                                                        @foreach($otherPkgs as $pkg)
                                                            <option value="{{ $pkg->id }}">
                                                                [{{ strtoupper($pkg->pc_type) }}] {{ $pkg->name }} (+{{ $pkg->duration_minutes }}m - Rp {{ number_format($pkg->price, 0, ',', '.') }})
                                                            </option>
                                                        @endforeach
                                                    </optgroup>
                                                @endif
                                            </select>

                                            <select name="member_id" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg px-2.5 py-1.5 text-xs text-gray-700 dark:text-gray-300 focus:border-blue-500 focus:outline-none">
                                                <option value="">-- Guest / Member Saat Ini --</option>
                                                @foreach($members as $m)
                                                    <option value="{{ $m->id }}" {{ $pc->current_member_id == $m->id ? 'selected' : '' }}>
                                                        {{ $m->name }} ({{ $m->username }})
                                                    </option>
                                                @endforeach
                                            </select>

                                            <button type="submit"
                                                    class="w-full py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs transition shadow-sm">
                                                Buka / Tambah Paket
                                            </button>
                                        </form>

                                        <div class="grid grid-cols-3 gap-1.5">
                                            <form action="{{ route('kasir.pc.control') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="pc_id" value="{{ $pc->id }}">
                                                <input type="hidden" name="action" value="add_minutes">
                                                <input type="hidden" name="minutes" value="30">
                                                <button type="submit" class="w-full py-1.5 rounded-lg bg-white dark:bg-gray-900 hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 text-[11px] font-medium border border-gray-200 dark:border-gray-800 transition">
                                                    +30m
                                                </button>
                                            </form>

                                            <form action="{{ route('kasir.pc.control') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="pc_id" value="{{ $pc->id }}">
                                                <input type="hidden" name="action" value="add_minutes">
                                                <input type="hidden" name="minutes" value="60">
                                                <button type="submit" class="w-full py-1.5 rounded-lg bg-white dark:bg-gray-900 hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 text-[11px] font-medium border border-gray-200 dark:border-gray-800 transition">
                                                    +1 Jam
                                                </button>
                                            </form>

                                            <form action="{{ route('kasir.pc.control') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="pc_id" value="{{ $pc->id }}">
                                                <input type="hidden" name="action" value="lock">
                                                <button type="submit" class="w-full py-1.5 rounded-lg bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800/50 text-[11px] font-semibold transition">
                                                    Kunci
                                                </button>
                                            </form>
                                        </div>

                                        <form action="{{ route('kasir.pc.control') }}" method="POST" class="flex gap-1.5">
                                            @csrf
                                            <input type="hidden" name="pc_id" value="{{ $pc->id }}">
                                            <input type="hidden" name="action" value="send_message">
                                            <input type="text" name="message" placeholder="Pesan ke {{ $pc->pc_code }}..."
                                                   class="min-w-0 flex-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg px-2.5 py-1.5 text-xs text-gray-800 dark:text-gray-200 placeholder-gray-400 focus:border-blue-500 focus:outline-none">
                                            <button type="submit" class="shrink-0 px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 text-xs font-semibold transition">
                                                Kirim
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- MODAL: ATUR JUMLAH TERMINAL PC (BATCH SIZER)              -->
                <!-- ========================================================= -->
                <div x-show="showBatchPcModal" x-cloak
                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
                    <div @click.away="showBatchPcModal = false"
                         class="w-full max-w-lg rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 p-6 shadow-2xl space-y-4">
                        <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-800 pb-3">
                            <h4 class="text-base font-bold text-gray-900 dark:text-gray-100">
                                Atur Jumlah & Komposisi Terminal PC
                            </h4>
                            <button @click="showBatchPcModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Tentukan berapa unit PC yang tersedia di warnet untuk masing-masing tier (Reguler, VIP, VIP++). Sistem akan otomatis membuat dan memberi nomor PC secara berurutan.
                        </p>

                        <form action="{{ route('kasir.pc.batch') }}" method="POST" class="space-y-4">
                            @csrf
                            <div class="grid grid-cols-3 gap-3">
                                <div class="p-3 rounded-xl border border-blue-200 dark:border-blue-900/50 bg-blue-50/30 dark:bg-blue-950/20">
                                    <label class="text-[11px] font-bold text-blue-600 dark:text-blue-400 block mb-1">Unit Reguler</label>
                                    <input type="number" name="count_reguler" value="{{ $regulerPcsCount }}" min="0" max="50" required
                                           class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg px-2.5 py-1.5 text-sm font-bold mono">
                                    <span class="text-[10px] text-gray-500 block mt-1">Tarif default: Rp 5.000/j</span>
                                </div>
                                <div class="p-3 rounded-xl border border-amber-200 dark:border-amber-900/50 bg-amber-50/30 dark:bg-amber-950/20">
                                    <label class="text-[11px] font-bold text-amber-600 dark:text-amber-400 block mb-1">Unit VIP</label>
                                    <input type="number" name="count_vip" value="{{ $vipPcsCount }}" min="0" max="50" required
                                           class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg px-2.5 py-1.5 text-sm font-bold mono">
                                    <span class="text-[10px] text-gray-500 block mt-1">Tarif default: Rp 8.000/j</span>
                                </div>
                                <div class="p-3 rounded-xl border border-purple-200 dark:border-purple-900/50 bg-purple-50/30 dark:bg-purple-950/20">
                                    <label class="text-[11px] font-bold text-purple-600 dark:text-purple-400 block mb-1">Unit VIP++</label>
                                    <input type="number" name="count_vip_plus" value="{{ $vipPlusPcsCount }}" min="0" max="50" required
                                           class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg px-2.5 py-1.5 text-sm font-bold mono">
                                    <span class="text-[10px] text-gray-500 block mt-1">Tarif default: Rp 12.000/j</span>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Spesifikasi Default Tiap Jenis PC:</label>
                                <div class="space-y-1.5 text-xs">
                                    <input type="text" name="specs_reguler" value="Core i5-12400F / RTX 3060 12GB / RAM 16GB / Monitor 165Hz"
                                           placeholder="Spek Reguler" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-1.5 text-xs">
                                    <input type="text" name="specs_vip" value="Core i7-13700F / RTX 4070 12GB / RAM 32GB / Monitor 240Hz Curved"
                                           placeholder="Spek VIP" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-1.5 text-xs">
                                    <input type="text" name="specs_vip_plus" value="Core i9-14900K / RTX 4090 24GB / RAM 64GB / Monitor 360Hz ROG + Kursi Gaming Titan"
                                           placeholder="Spek VIP++" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-1.5 text-xs">
                                </div>
                            </div>

                            <div class="flex items-center gap-2 p-3 rounded-lg bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700">
                                <input type="checkbox" name="trim_excess" value="1" id="trim_excess_check" checked
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <label for="trim_excess_check" class="text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                                    Pangkas unit PC lama di atas total baru jika tidak sedang aktif digunakan
                                </label>
                            </div>

                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" @click="showBatchPcModal = false"
                                        class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-xs font-medium text-gray-700 dark:text-gray-300">
                                    Batal
                                </button>
                                <button type="submit"
                                        class="px-5 py-2 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold shadow-sm">
                                    Terapkan Jumlah Terminal PC
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- MODAL: TAMBAH TERMINAL PC SATUAN                          -->
                <!-- ========================================================= -->
                <div x-show="showAddPcModal" x-cloak
                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
                    <div @click.away="showAddPcModal = false"
                         class="w-full max-w-md rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 p-6 shadow-2xl space-y-4">
                        <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-800 pb-3">
                            <h4 class="text-base font-bold text-gray-900 dark:text-gray-100">
                                Tambah Terminal PC Baru
                            </h4>
                            <button @click="showAddPcModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                            </button>
                        </div>

                        <form action="{{ route('kasir.pc.store') }}" method="POST" class="space-y-3">
                            @csrf
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-[11px] font-semibold text-gray-500 uppercase block mb-1">Kode Terminal</label>
                                    <input type="text" name="pc_code" placeholder="Contoh: PC-11" required
                                           class="w-full bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 text-xs font-bold mono">
                                </div>
                                <div>
                                    <label class="text-[11px] font-semibold text-gray-500 uppercase block mb-1">Jenis PC</label>
                                    <select name="pc_type" required class="w-full bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 text-xs font-bold">
                                        <option value="reguler">Reguler</option>
                                        <option value="vip">VIP</option>
                                        <option value="vip++">VIP++</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-[11px] font-semibold text-gray-500 uppercase block mb-1">Nama Terminal</label>
                                    <input type="text" name="name" placeholder="Opsional (Otomatis)"
                                           class="w-full bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 text-xs">
                                </div>
                                <div>
                                    <label class="text-[11px] font-semibold text-gray-500 uppercase block mb-1">Tarif/Jam (Rp)</label>
                                    <input type="number" name="hourly_rate" placeholder="Contoh: 5000"
                                           class="w-full bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 text-xs mono">
                                </div>
                            </div>

                            <div>
                                <label class="text-[11px] font-semibold text-gray-500 uppercase block mb-1">Spesifikasi Hardware</label>
                                <input type="text" name="specs" placeholder="Contoh: Core i5 / RTX 3060 / 165Hz"
                                       class="w-full bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 text-xs">
                            </div>

                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" @click="showAddPcModal = false"
                                        class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-xs font-medium text-gray-700 dark:text-gray-300">
                                    Batal
                                </button>
                                <button type="submit"
                                        class="px-5 py-2 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold shadow-sm">
                                    Simpan Terminal Baru
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- HALAMAN 3: TRANSAKSI & QRIS                               -->
                <!-- ========================================================= -->
                <div x-show="selected === 'Transaksi & QRIS'" x-cloak>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    Daftar Transaksi Billing & Makanan (Konfirmasi QRIS)
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    Konfirmasi transaksi berstatus PENDING QRIS agar waktu billing atau status lunas langsung aktif di PC Client.
                                </p>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-800 text-gray-500 dark:text-gray-400 uppercase text-[11px]">
                                        <th class="py-3 px-4">Kode / Waktu</th>
                                        <th class="py-3 px-4">Terminal PC</th>
                                        <th class="py-3 px-4">Tipe</th>
                                        <th class="py-3 px-4">Rincian Item / Paket</th>
                                        <th class="py-3 px-4">Total Harga</th>
                                        <th class="py-3 px-4">Metode & Status Bayar</th>
                                        <th class="py-3 px-4 text-right">Aksi Kasir</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                                    @forelse($orders as $ord)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                            <td class="py-3.5 px-4">
                                                <div class="font-bold text-gray-900 dark:text-gray-100 mono">{{ $ord->order_code }}</div>
                                                <div class="text-[11px] text-gray-400">{{ $ord->created_at?->format('H:i:s') }}</div>
                                            </td>
                                            <td class="py-3.5 px-4">
                                                <span class="px-2.5 py-1 rounded bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 font-bold mono">
                                                    {{ $ord->pc_code }}
                                                </span>
                                            </td>
                                            <td class="py-3.5 px-4 uppercase font-semibold text-gray-600 dark:text-gray-300">
                                                {{ $ord->order_type === 'food' ? 'Makanan (F&B)' : 'Paket Billing' }}
                                            </td>
                                            <td class="py-3.5 px-4">
                                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $ord->items_summary }}</div>
                                                @if($ord->notes)
                                                    <div class="text-[11px] text-amber-600 dark:text-amber-400">Catatan: "{{ $ord->notes }}"</div>
                                                @endif
                                            </td>
                                            <td class="py-3.5 px-4 font-bold text-green-600 dark:text-green-400 mono whitespace-nowrap">
                                                Rp {{ number_format($ord->total_price, 0, ',', '.') }}
                                            </td>
                                            <td class="py-3.5 px-4">
                                                <span class="px-2.5 py-1 rounded text-[10px] font-bold uppercase {{ $ord->payment_status === 'paid' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300' }}">
                                                    {{ strtoupper($ord->payment_method) }} - {{ $ord->payment_status === 'paid' ? 'LUNAS' : 'PENDING QRIS' }}
                                                </span>
                                            </td>
                                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                                @if($ord->payment_status !== 'paid')
                                                    <form action="{{ route('kasir.order.pay', $ord) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit"
                                                                class="px-3.5 py-1.5 rounded-lg bg-green-600 hover:bg-green-500 text-white font-semibold text-xs shadow-sm transition">
                                                            Konfirmasi Lunas
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-gray-400 dark:text-gray-500 font-medium">Lunas</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="py-10 text-center text-gray-500 dark:text-gray-400">Belum ada transaksi hari ini.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- HALAMAN 4: ANTRIAN DAPUR                                  -->
                <!-- ========================================================= -->
                <div x-show="selected === 'Antrian Dapur'" x-cloak>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
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
                                            <span class="px-3 py-1 rounded-md bg-blue-600 text-white font-bold text-xs mono">{{ $ord->pc_code }}</span>
                                            <span class="text-xs font-semibold text-gray-500 mono">{{ $ord->order_code }} ({{ $ord->created_at?->format('H:i') }})</span>
                                        </div>
                                        <div class="rounded-lg bg-white dark:bg-gray-900 p-3 border border-gray-200 dark:border-gray-800 space-y-1">
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
                                                Catatan: "{{ $ord->notes }}"
                                            </div>
                                        @endif
                                        <form action="{{ route('kasir.order.kitchen', $ord) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="kitchen_status" value="cooking">
                                            <button type="submit" class="w-full py-2 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 font-semibold text-xs transition shadow-sm">
                                                Mulai Masak Sekarang
                                            </button>
                                        </form>
                                    </div>
                                @empty
                                    <div class="py-12 text-center text-gray-400 dark:text-gray-500 text-xs">Tidak ada antrian pesanan baru.</div>
                                @endforelse
                            </div>
                        </div>

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
                                            <span class="px-3 py-1 rounded-md bg-orange-500 text-white font-bold text-xs mono">{{ $ord->pc_code }}</span>
                                            <span class="text-xs font-semibold text-gray-500 mono">{{ $ord->order_code }}</span>
                                        </div>
                                        <div class="rounded-lg bg-white dark:bg-gray-900 p-3 border border-gray-200 dark:border-gray-800">
                                            <div class="text-xs font-semibold text-gray-900 dark:text-gray-100">{{ $ord->items_summary }}</div>
                                        </div>
                                        @if($ord->notes)
                                            <div class="p-2.5 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/50 text-xs text-amber-700 dark:text-amber-300 font-medium">
                                                Catatan: "{{ $ord->notes }}"
                                            </div>
                                        @endif
                                        <form action="{{ route('kasir.order.kitchen', $ord) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="kitchen_status" value="ready">
                                            <button type="submit" class="w-full py-2 rounded-lg bg-green-600 hover:bg-green-500 text-white font-semibold text-xs transition shadow-sm">
                                                Makanan Siap Diantar
                                            </button>
                                        </form>
                                    </div>
                                @empty
                                    <div class="py-12 text-center text-gray-400 dark:text-gray-500 text-xs">Belum ada pesanan yang sedang dimasak.</div>
                                @endforelse
                            </div>
                        </div>

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
                                            <span class="px-3 py-1 rounded-md bg-green-600 text-white font-bold text-xs mono">ANTAR KE {{ $ord->pc_code }}</span>
                                            <span class="text-xs font-semibold text-gray-500 mono">{{ $ord->order_code }}</span>
                                        </div>
                                        <div class="rounded-lg bg-white dark:bg-gray-900 p-3 border border-gray-200 dark:border-gray-800">
                                            <div class="text-xs font-semibold text-gray-900 dark:text-gray-100">{{ $ord->items_summary }}</div>
                                            <div class="text-xs text-green-600 dark:text-green-400 font-semibold mt-1 mono">
                                                Total: Rp {{ number_format($ord->total_price, 0, ',', '.') }} ({{ strtoupper($ord->payment_method) }})
                                            </div>
                                        </div>
                                        <form action="{{ route('kasir.order.kitchen', $ord) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="kitchen_status" value="completed">
                                            <input type="hidden" name="mark_paid" value="1">
                                            <button type="submit" class="w-full py-2 rounded-lg bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs transition shadow-sm">
                                                Sudah Diantar ke {{ $ord->pc_code }} (Selesai)
                                            </button>
                                        </form>
                                    </div>
                                @empty
                                    <div class="py-12 text-center text-gray-400 dark:text-gray-500 text-xs">Tidak ada pesanan yang menunggu diantar.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- HALAMAN 5: MENU & PAKET (DENGAN FITUR EDIT & TOPUP PAKET) -->
                <!-- ========================================================= -->
                <div x-show="selected === 'Menu & Paket'" x-cloak>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                        <!-- Kolom Kiri: Katalog Makanan & Minuman -->
                        <div class="space-y-6">
                            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">Tambah Menu Makanan & Minuman</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Menu yang ditambahkan langsung tampil di aplikasi C++ seluruh terminal PC.</p>

                                <form action="{{ route('kasir.menu.store') }}" method="POST" class="space-y-3">
                                    @csrf
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <input type="text" name="name" placeholder="Nama Makanan / Minuman" required
                                               class="bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:outline-none">
                                        <select name="category" class="bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:outline-none">
                                            <option value="makanan">Kategori: Makanan Berat</option>
                                            <option value="minuman">Kategori: Minuman Segar</option>
                                            <option value="snack">Kategori: Snack & Cemilan</option>
                                        </select>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <input type="number" name="price" placeholder="Harga (Rp)" value="15000" required
                                               class="bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-gray-100 mono focus:border-blue-500 focus:outline-none">
                                        <input type="number" name="stock" placeholder="Stok" value="50" required
                                               class="bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-gray-100 mono focus:border-blue-500 focus:outline-none">
                                    </div>
                                    <button type="submit" class="w-full py-2 rounded-lg bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs transition shadow-sm">
                                        Simpan Menu ke Katalog Client C++
                                    </button>
                                </form>
                            </div>

                            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Daftar Menu F&B Aktif ({{ $menus->count() }} Item)</h3>
                                <div class="divide-y divide-gray-200 dark:divide-gray-800">
                                    @foreach($menus as $menu)
                                        <div class="py-3.5 space-y-3">
                                            <div class="flex items-center justify-between gap-4">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $menu->name }}</p>
                                                    <p class="text-xs text-gray-500 uppercase">{{ $menu->category }} • Stok: {{ $menu->stock }} • <span class="text-green-600 dark:text-green-400 font-bold mono">Rp {{ number_format($menu->price, 0, ',', '.') }}</span></p>
                                                </div>
                                                <div class="flex items-center gap-2 shrink-0">
                                                    <button type="button"
                                                            @click="editMenuId = (editMenuId === {{ $menu->id }} ? null : {{ $menu->id }})"
                                                            class="px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-xs font-semibold text-gray-700 dark:text-gray-200 transition">
                                                        Edit
                                                    </button>
                                                    <form action="{{ route('kasir.menu.delete', $menu) }}" method="POST" onsubmit="return confirm('Hapus menu {{ $menu->name }}?')">
                                                        @csrf
                                                        <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800/50 text-xs font-semibold transition">
                                                            Hapus
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>

                                            <!-- Form Inline Edit Menu -->
                                            <form x-show="editMenuId === {{ $menu->id }}" x-cloak
                                                  action="{{ route('kasir.menu.update', $menu) }}" method="POST"
                                                  class="p-3.5 rounded-lg bg-gray-50 dark:bg-gray-950 border border-blue-500/40 space-y-2.5">
                                                @csrf
                                                <div class="text-xs font-semibold text-blue-600 dark:text-blue-400">Edit Menu: {{ $menu->name }}</div>
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                                    <input type="text" name="name" value="{{ $menu->name }}" required
                                                           class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg px-2.5 py-1.5 text-xs text-gray-900 dark:text-gray-100">
                                                    <select name="category" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg px-2.5 py-1.5 text-xs text-gray-900 dark:text-gray-100">
                                                        <option value="makanan" {{ $menu->category === 'makanan' ? 'selected' : '' }}>Makanan</option>
                                                        <option value="minuman" {{ $menu->category === 'minuman' ? 'selected' : '' }}>Minuman</option>
                                                        <option value="snack" {{ $menu->category === 'snack' ? 'selected' : '' }}>Snack</option>
                                                    </select>
                                                </div>
                                                <div class="grid grid-cols-2 gap-2.5">
                                                    <div>
                                                        <label class="text-[10px] text-gray-500 uppercase">Harga (Rp)</label>
                                                        <input type="number" name="price" value="{{ $menu->price }}" required
                                                               class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg px-2.5 py-1.5 text-xs text-gray-900 dark:text-gray-100 mono">
                                                    </div>
                                                    <div>
                                                        <label class="text-[10px] text-gray-500 uppercase">Stok</label>
                                                        <input type="number" name="stock" value="{{ $menu->stock }}" required
                                                               class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg px-2.5 py-1.5 text-xs text-gray-900 dark:text-gray-100 mono">
                                                    </div>
                                                </div>
                                                <div class="flex justify-end gap-2">
                                                    <button type="button" @click="editMenuId = null" class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-xs text-gray-600 dark:text-gray-400">Batal</button>
                                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold">Simpan Perubahan</button>
                                                </div>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Kolom Kanan: Paket Billing Warnet (Bisa Edit & Bisa Langsung Top-Up ke Member) -->
                        <div class="space-y-6">
                            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">Tambah Paket Billing Baru</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Paket waktu bermain yang dapat dibeli langsung oleh member dari PC Client C++ atau Kasir.</p>

                                <form action="{{ route('kasir.package.store') }}" method="POST" class="space-y-3">
                                    @csrf
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <input type="text" name="name" placeholder="Nama Paket (Contoh: Paket 4 Jam)" required
                                               class="bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:outline-none">
                                        <input type="number" name="duration_minutes" placeholder="Durasi (Menit)" value="240" required
                                               class="bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-gray-100 mono focus:border-blue-500 focus:outline-none">
                                        <select name="pc_type" class="bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-gray-100 font-semibold focus:border-blue-500 focus:outline-none">
                                            <option value="all">Semua PC (Universal)</option>
                                            <option value="reguler">Khusus PC Reguler</option>
                                            <option value="vip">Khusus PC VIP</option>
                                            <option value="vip++">Khusus PC VIP++</option>
                                        </select>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <input type="number" name="price" placeholder="Harga Paket (Rp)" value="22000" required
                                               class="bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-gray-100 mono focus:border-blue-500 focus:outline-none">
                                        <input type="text" name="description" placeholder="Keterangan Singkat" value="Paket billing warnet"
                                               class="bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:outline-none">
                                    </div>
                                    <button type="submit" class="w-full py-2 rounded-lg bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs transition shadow-sm">
                                        Simpan Paket Billing Baru
                                    </button>
                                </form>
                            </div>

                            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">Daftar Paket Billing ({{ $packages->count() }} Paket)</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Anda dapat mengedit paket atau langsung mengisi (top-up) paket ini ke akun Member.</p>

                                <div class="divide-y divide-gray-200 dark:divide-gray-800">
                                    @foreach($packages as $pkg)
                                        @php
                                            $pkgTierStyles = match($pkg->pc_type) {
                                                'vip' => 'bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 border-amber-200 dark:border-amber-800',
                                                'vip++' => 'bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 border-purple-200 dark:border-purple-800',
                                                'reguler' => 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 border-blue-200 dark:border-blue-800',
                                                default => 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-700',
                                            };
                                        @endphp
                                        <div class="py-4 space-y-3">
                                            <div class="flex items-start justify-between gap-4">
                                                <div>
                                                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2 flex-wrap">
                                                        <span>{{ $pkg->name }}</span>
                                                        <span class="text-xs font-normal text-gray-500">({{ $pkg->duration_minutes }} Menit)</span>
                                                        <span class="text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded border {{ $pkgTierStyles }}">
                                                            {{ $pkg->pc_type === 'all' ? 'SEMUA PC' : strtoupper($pkg->pc_type) }}
                                                        </span>
                                                    </p>
                                                    <p class="text-xs text-gray-500">{{ $pkg->description }}</p>
                                                    <p class="text-sm font-bold text-blue-600 dark:text-blue-400 mono mt-0.5">
                                                        Rp {{ number_format($pkg->price, 0, ',', '.') }}
                                                    </p>
                                                </div>
                                                <div class="flex items-center gap-2 shrink-0">
                                                    <button type="button"
                                                            @click="editPkgId = (editPkgId === {{ $pkg->id }} ? null : {{ $pkg->id }})"
                                                            class="px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-xs font-semibold text-gray-700 dark:text-gray-200 transition">
                                                        Edit
                                                    </button>
                                                    <form action="{{ route('kasir.package.delete', $pkg) }}" method="POST" onsubmit="return confirm('Hapus paket {{ $pkg->name }}?')">
                                                        @csrf
                                                        <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800/50 text-xs font-semibold transition">
                                                            Hapus
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>

                                            <!-- Langsung Top-Up Paket Ini ke Member -->
                                            <form action="{{ route('kasir.member.topup') }}" method="POST" class="flex flex-wrap sm:flex-nowrap items-center gap-2 pt-1">
                                                @csrf
                                                <input type="hidden" name="package_id" value="{{ $pkg->id }}">
                                                <select name="member_id" required class="min-w-0 flex-1 bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-2.5 py-1.5 text-xs text-gray-800 dark:text-gray-200 focus:border-blue-500 focus:outline-none">
                                                    @foreach($members as $m)
                                                        <option value="{{ $m->id }}">Top-Up ke: {{ $m->name }} ({{ $m->username }})</option>
                                                    @endforeach
                                                </select>
                                                <select name="payment_method" class="bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-2.5 py-1.5 text-xs text-gray-800 dark:text-gray-200 focus:border-blue-500 focus:outline-none">
                                                    <option value="cash">Bayar Tunai/QRIS</option>
                                                    <option value="balance">Potong Saldo Member</option>
                                                </select>
                                                <button type="submit" class="shrink-0 px-3 py-1.5 rounded-lg bg-green-600 hover:bg-green-500 text-white text-xs font-semibold transition shadow-sm">
                                                    Isi Paket ke Member
                                                </button>
                                            </form>

                                            <!-- Form Inline Edit Paket -->
                                            <form x-show="editPkgId === {{ $pkg->id }}" x-cloak
                                                  action="{{ route('kasir.package.update', $pkg) }}" method="POST"
                                                  class="p-3.5 rounded-lg bg-gray-50 dark:bg-gray-950 border border-blue-500/40 space-y-2.5">
                                                @csrf
                                                <div class="text-xs font-semibold text-blue-600 dark:text-blue-400">Edit Paket: {{ $pkg->name }}</div>
                                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                                    <div>
                                                        <label class="text-[10px] text-gray-500 uppercase">Nama Paket</label>
                                                        <input type="text" name="name" value="{{ $pkg->name }}" required
                                                               class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg px-2.5 py-1.5 text-xs text-gray-900 dark:text-gray-100">
                                                    </div>
                                                    <div>
                                                        <label class="text-[10px] text-gray-500 uppercase">Durasi (Menit)</label>
                                                        <input type="number" name="duration_minutes" value="{{ $pkg->duration_minutes }}" required
                                                               class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg px-2.5 py-1.5 text-xs text-gray-900 dark:text-gray-100 mono">
                                                    </div>
                                                    <div>
                                                        <label class="text-[10px] text-gray-500 uppercase">Jenis PC</label>
                                                        <select name="pc_type" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg px-2.5 py-1.5 text-xs font-semibold">
                                                            <option value="all" {{ $pkg->pc_type === 'all' ? 'selected' : '' }}>Semua PC</option>
                                                            <option value="reguler" {{ $pkg->pc_type === 'reguler' ? 'selected' : '' }}>Reguler</option>
                                                            <option value="vip" {{ $pkg->pc_type === 'vip' ? 'selected' : '' }}>VIP</option>
                                                            <option value="vip++" {{ $pkg->pc_type === 'vip++' ? 'selected' : '' }}>VIP++</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                                    <div>
                                                        <label class="text-[10px] text-gray-500 uppercase">Harga (Rp)</label>
                                                        <input type="number" name="price" value="{{ $pkg->price }}" required
                                                               class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg px-2.5 py-1.5 text-xs text-gray-900 dark:text-gray-100 mono">
                                                    </div>
                                                    <div>
                                                        <label class="text-[10px] text-gray-500 uppercase">Keterangan</label>
                                                        <input type="text" name="description" value="{{ $pkg->description }}"
                                                               class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg px-2.5 py-1.5 text-xs text-gray-900 dark:text-gray-100">
                                                    </div>
                                                </div>
                                                <div class="flex justify-end gap-2">
                                                    <button type="button" @click="editPkgId = null" class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-xs text-gray-600 dark:text-gray-400">Batal</button>
                                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold">Simpan Perubahan</button>
                                                </div>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- HALAMAN 6: MEMBERS (TOP-UP PAKET, SALDO, & EDIT MEMBER)   -->
                <!-- ========================================================= -->
                <div x-show="selected === 'Members'" x-cloak>
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                        <!-- Kolom Kiri (4 Cols): Top-Up Paket Langsung + Top-Up Saldo + Registrasi -->
                        <div class="lg:col-span-4 space-y-6">

                            <!-- Card 1: Top-Up Member Langsung Pilih Paket Billing -->
                            <div class="rounded-xl border border-blue-500/40 bg-white dark:bg-gray-900 p-6 shadow-sm space-y-4">
                                <div>
                                    <span class="px-2 py-0.5 rounded bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 text-[10px] font-bold uppercase tracking-wider">
                                        Isi Paket Instan
                                    </span>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mt-1">Top-Up Paket Billing ke Member</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        Pilih member dan paket billing untuk langsung menambah jam bermain member.
                                    </p>
                                </div>

                                <form action="{{ route('kasir.member.topup') }}" method="POST" class="space-y-3">
                                    @csrf
                                    <div>
                                        <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Pilih Akun Member</label>
                                        <select name="member_id" required class="w-full mt-1 bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:outline-none">
                                            @foreach($members as $m)
                                                <option value="{{ $m->id }}">
                                                    {{ $m->name }} ({{ $m->username }}) - Saldo: Rp {{ number_format($m->balance, 0, ',', '.') }} | {{ floor($m->saved_seconds / 60) }}m
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Pilih Paket Billing</label>
                                        <select name="package_id" required class="w-full mt-1 bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:outline-none">
                                            @foreach($packages as $pkg)
                                                <option value="{{ $pkg->id }}">
                                                    {{ $pkg->name }} (+{{ $pkg->duration_minutes }} Menit) - Rp {{ number_format($pkg->price, 0, ',', '.') }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Metode Pembayaran</label>
                                        <select name="payment_method" class="w-full mt-1 bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:outline-none">
                                            <option value="cash">Bayar Tunai / QRIS di Kasir</option>
                                            <option value="balance">Potong dari Saldo Member</option>
                                        </select>
                                    </div>

                                    <button type="submit" class="w-full py-2.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs transition shadow-sm">
                                        Isi Paket ke Member Sekarang
                                    </button>
                                </form>
                            </div>

                            <!-- Card 2: Top-Up Saldo Rupiah & Waktu Manual -->
                            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm space-y-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Top-Up Saldo (Rp) & Waktu Manual</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Isi deposit saldo rupiah atau menit custom.</p>
                                </div>

                                <form action="{{ route('kasir.member.topup') }}" method="POST" class="space-y-3">
                                    @csrf
                                    <div>
                                        <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Pilih Akun Member</label>
                                        <select name="member_id" class="w-full mt-1 bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:outline-none">
                                            @foreach($members as $m)
                                                <option value="{{ $m->id }}">
                                                    {{ $m->name }} ({{ $m->username }}) - Rp {{ number_format($m->balance, 0, ',', '.') }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="text-xs font-medium text-gray-600 dark:text-gray-400">+ Saldo (Rp)</label>
                                            <input type="number" name="amount" value="50000" step="1000"
                                                   class="w-full mt-1 bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-gray-100 mono focus:border-blue-500 focus:outline-none">
                                        </div>
                                        <div>
                                            <label class="text-xs font-medium text-gray-600 dark:text-gray-400">+ Waktu (Menit)</label>
                                            <input type="number" name="add_minutes" value="0"
                                                   class="w-full mt-1 bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-gray-100 mono focus:border-blue-500 focus:outline-none">
                                        </div>
                                    </div>

                                    <button type="submit" class="w-full py-2 rounded-lg bg-green-600 hover:bg-green-500 text-white font-semibold text-xs transition shadow-sm">
                                        Proses Top-Up Saldo / Waktu
                                    </button>
                                </form>
                            </div>

                            <!-- Card 3: Registrasi Member Baru -->
                            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm space-y-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Registrasi Member Baru</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Buat akun baru untuk login di PC Client C++.</p>
                                </div>
                                <form action="{{ route('kasir.member.store') }}" method="POST" class="space-y-3">
                                    @csrf
                                    <div class="grid grid-cols-2 gap-2.5">
                                        <input type="text" name="username" placeholder="Username Member" required
                                               class="bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:outline-none">
                                        <input type="text" name="name" placeholder="Nama Lengkap" required
                                               class="bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:outline-none">
                                    </div>
                                    <input type="text" name="password" placeholder="Password Awal (Default: password)"
                                           class="w-full bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:outline-none">
                                    <div class="grid grid-cols-2 gap-2.5">
                                        <div>
                                            <label class="text-[10px] text-gray-500 uppercase">Saldo Awal (Rp)</label>
                                            <input type="number" name="balance" value="25000"
                                                   class="w-full mt-0.5 bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-gray-100 mono focus:border-blue-500 focus:outline-none">
                                        </div>
                                        <div>
                                            <label class="text-[10px] text-gray-500 uppercase">Pilih Paket Awal</label>
                                            <select name="package_id" class="w-full mt-0.5 bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-2.5 py-2 text-xs text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:outline-none">
                                                <option value="">-- Tanpa Paket --</option>
                                                @foreach($packages as $pkg)
                                                    <option value="{{ $pkg->id }}">{{ $pkg->name }} (+{{ $pkg->duration_minutes }}m)</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <button type="submit" class="w-full py-2 rounded-lg bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 font-semibold text-xs transition">
                                        Daftarkan Member Baru
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Kolom Kanan (8 Cols): Tabel Daftar Member Lengkap dengan Edit & Top-Up Paket Cepat -->
                        <div class="lg:col-span-8">
                            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-6">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                            Daftar Seluruh Akun Member ({{ $members->count() }} Member)
                                        </h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                            Klik tombol <strong>Edit</strong> untuk mengubah profil, saldo, atau simpanan waktu member.
                                        </p>
                                    </div>
                                </div>

                                <div class="divide-y divide-gray-200 dark:divide-gray-800">
                                    @foreach($members as $m)
                                        @php
                                            $savedMins = floor($m->saved_seconds / 60);
                                        @endphp
                                        <div class="py-4 space-y-3">
                                            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4">
                                                <!-- Info Member -->
                                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 flex-1">
                                                    <div>
                                                        <span class="text-[10px] font-semibold uppercase text-gray-400 block">Username</span>
                                                        <span class="font-bold text-sm text-blue-600 dark:text-blue-400 mono">{{ $m->username }}</span>
                                                    </div>
                                                    <div>
                                                        <span class="text-[10px] font-semibold uppercase text-gray-400 block">Nama Lengkap</span>
                                                        <span class="font-medium text-sm text-gray-900 dark:text-gray-100">{{ $m->name }}</span>
                                                    </div>
                                                    <div>
                                                        <span class="text-[10px] font-semibold uppercase text-gray-400 block">Saldo Member</span>
                                                        <span class="font-bold text-sm text-green-600 dark:text-green-400 mono">Rp {{ number_format($m->balance, 0, ',', '.') }}</span>
                                                    </div>
                                                    <div>
                                                        <span class="text-[10px] font-semibold uppercase text-gray-400 block">Simpanan Waktu</span>
                                                        <span class="font-semibold text-sm text-gray-800 dark:text-gray-200 mono">{{ $savedMins }} Menit ({{ gmdate('H:i:s', $m->saved_seconds) }})</span>
                                                    </div>
                                                </div>

                                                <!-- Edit/Delete Buttons -->
                                                <div class="flex items-center gap-2 shrink-0">
                                                    <button type="button"
                                                            @click="editMemberId = (editMemberId === {{ $m->id }} ? null : {{ $m->id }})"
                                                            class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-xs font-semibold text-gray-700 dark:text-gray-200 transition">
                                                        Edit
                                                    </button>

                                                    <form action="{{ route('kasir.member.delete', $m) }}" method="POST" onsubmit="return confirm('Hapus member {{ $m->username }}?')">
                                                        @csrf
                                                        <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800/50 text-xs font-semibold transition">
                                                            Hapus
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>

                                            <!-- Form Inline Edit Member -->
                                            <form x-show="editMemberId === {{ $m->id }}" x-cloak
                                                  action="{{ route('kasir.member.update', $m) }}" method="POST"
                                                  class="p-4 rounded-xl bg-gray-50 dark:bg-gray-950 border border-blue-500/50 space-y-3">
                                                @csrf
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">
                                                        Edit Data Member: {{ $m->username }}
                                                    </span>
                                                </div>

                                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                                                    <div>
                                                        <label class="text-[10px] font-semibold uppercase text-gray-500">Username</label>
                                                        <input type="text" name="username" value="{{ $m->username }}" required
                                                               class="w-full mt-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg px-2.5 py-1.5 text-xs text-gray-900 dark:text-gray-100 mono">
                                                    </div>
                                                    <div>
                                                        <label class="text-[10px] font-semibold uppercase text-gray-500">Nama Lengkap</label>
                                                        <input type="text" name="name" value="{{ $m->name }}" required
                                                               class="w-full mt-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg px-2.5 py-1.5 text-xs text-gray-900 dark:text-gray-100">
                                                    </div>
                                                    <div>
                                                        <label class="text-[10px] font-semibold uppercase text-gray-500">Saldo Member (Rp)</label>
                                                        <input type="number" name="balance" value="{{ $m->balance }}" required
                                                               class="w-full mt-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg px-2.5 py-1.5 text-xs text-gray-900 dark:text-gray-100 mono">
                                                    </div>
                                                    <div>
                                                        <label class="text-[10px] font-semibold uppercase text-gray-500">Simpanan Waktu (Menit)</label>
                                                        <input type="number" name="saved_minutes" value="{{ $savedMins }}" required
                                                               class="w-full mt-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg px-2.5 py-1.5 text-xs text-gray-900 dark:text-gray-100 mono">
                                                    </div>
                                                    <div>
                                                        <label class="text-[10px] font-semibold uppercase text-gray-500">Password Baru (Opsional)</label>
                                                        <input type="text" name="password" placeholder="Kosongkan jika tetap"
                                                               class="w-full mt-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg px-2.5 py-1.5 text-xs text-gray-900 dark:text-gray-100">
                                                    </div>
                                                </div>

                                                <div class="flex justify-end gap-2 pt-1">
                                                    <button type="button" @click="editMemberId = null"
                                                            class="px-3.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-xs font-medium text-gray-600 dark:text-gray-400">
                                                        Batal
                                                    </button>
                                                    <button type="submit"
                                                            class="px-4 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold shadow-sm">
                                                        Simpan Perubahan Member
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Live Polling Script -->
    <script>
        function formatRupiah(num) {
            return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
        }

        async function pollLiveState() {
            try {
                const res = await fetch('{{ route("kasir.live") }}');
                if (!res.ok) return;
                const data = await res.json();

                const revEl = document.getElementById('kpi-revenue');
                const actEl = document.getElementById('kpi-active-pcs');
                const totEl = document.getElementById('kpi-total-pcs');
                const kitEl = document.getElementById('kpi-kitchen');
                const qrisEl = document.getElementById('kpi-qris');
                if (revEl) revEl.textContent = formatRupiah(data.total_revenue);
                if (actEl) actEl.textContent = data.active_pcs;
                if (totEl && data.total_pcs) totEl.textContent = data.total_pcs;
                if (kitEl) kitEl.textContent = data.kitchen_queue;
                if (qrisEl) qrisEl.textContent = data.pending_qris;

                const sbActive = document.getElementById('sidebar-active-pcs');
                const sbQris = document.getElementById('sidebar-pending-qris');
                const sbKitchen = document.getElementById('sidebar-kitchen-badge');
                if (sbActive) sbActive.textContent = data.active_pcs;
                if (sbQris) sbQris.textContent = data.pending_qris;
                if (sbKitchen) sbKitchen.textContent = data.kitchen_queue;

                for (const pc of data.pcs) {
                    const timerEl = document.getElementById('pc-timer-' + pc.id);
                    const memberEl = document.getElementById('pc-member-' + pc.id);
                    const balanceEl = document.getElementById('pc-balance-' + pc.id);
                    const lockLabelEl = document.getElementById('pc-lock-label-' + pc.id);
                    const onlineEl = document.getElementById('pc-online-' + pc.id);

                    if (timerEl) timerEl.textContent = pc.remaining_formatted;
                    if (memberEl) memberEl.textContent = pc.member_username !== '-' ? `${pc.member_name} (${pc.member_username})` : 'Belum Login';
                    if (balanceEl) balanceEl.textContent = formatRupiah(pc.member_balance);
                    if (lockLabelEl) {
                        lockLabelEl.textContent = pc.is_locked ? 'TERKUNCI' : 'AKTIF';
                        lockLabelEl.className = 'text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded shrink-0 ' +
                            (pc.is_locked ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400' : 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400');
                    }
                    if (onlineEl) {
                        onlineEl.textContent = pc.is_online ? 'ONLINE' : 'STANDBY';
                        onlineEl.className = 'text-[10px] px-2 py-0.5 rounded font-medium truncate ' +
                            (pc.is_online ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-gray-200 dark:bg-gray-800 text-gray-500 dark:text-gray-400');
                    }
                }
            } catch (e) {
                console.error(e);
            }
        }

        setInterval(pollLiveState, 2000);
    </script>
</body>
</html>
