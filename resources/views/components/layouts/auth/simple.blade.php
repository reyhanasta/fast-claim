<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen antialiased">
    <div class="flex min-h-svh">
        {{-- Left Side - Decorative Panel --}}
        <div
            class="hidden lg:flex lg:w-1/2 relative overflow-hidden bg-gradient-to-br from-emerald-600 via-emerald-700 to-teal-700">
            {{-- Background Pattern --}}
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <defs>
                        <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                            <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5" />
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#grid)" />
                </svg>
            </div>

            {{-- Floating Shapes --}}
            <div class="absolute top-20 left-10 w-72 h-72 bg-white/10 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-20 right-10 w-96 h-96 bg-teal-400/20 rounded-full blur-3xl animate-pulse"
                style="animation-delay: 1s"></div>
            <div class="absolute top-1/2 left-1/3 w-48 h-48 bg-emerald-300/20 rounded-full blur-2xl animate-pulse"
                style="animation-delay: 2s"></div>

            {{-- Content --}}
            <div class="relative z-10 flex flex-col justify-center items-center w-full p-12 text-white gap-4">
                {{-- App Icon --}}
                <div class="flex flex-col gap-4 items-center justify-center">

                    <div class="flex flex-row gap-2 items-center">
                        <div class="p-3 bg-white/95 backdrop-blur-sm rounded-3xl border border-white/20 shadow-2xl">
                            <img src="{{ asset('FastClaim_Icon.png') }}" alt="FastClaim"
                                class="w-12 h-12 object-contain" />
                        </div>
                        <h1 class="text-4xl font-bold ">{{ config('app.name', 'Fast Claim') }}</h1>
                    </div>
                    <p class="text-lg text-white/80 text-center max-w-md ">
                        Sistem Klaim BPJS yang Cepat, Mudah, dan Terintegrasi
                    </p>
                </div>

                {{-- Features --}}
                <div class="space-y-4 max-w-sm">
                    <div
                        class="flex items-center gap-4 bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                        <div class="p-2 bg-white/20 rounded-lg">
                            <flux:icon.bolt class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="font-semibold">Proses Cepat</h3>
                            <p class="text-sm text-white/70">Upload dan merge dokumen dalam hitungan detik</p>
                        </div>
                    </div>
                    <div
                        class="flex items-center gap-4 bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                        <div class="p-2 bg-white/20 rounded-lg">
                            <flux:icon.shield-check class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="font-semibold">Aman & Terpercaya</h3>
                            <p class="text-sm text-white/70">Data tersimpan dengan enkripsi tingkat tinggi</p>
                        </div>
                    </div>
                    <div
                        class="flex items-center gap-4 bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                        <div class="p-2 bg-white/20 rounded-lg">
                            <flux:icon.chart-bar class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="font-semibold">Laporan Lengkap</h3>
                            <p class="text-sm text-white/70">Dashboard analitik untuk monitoring klaim</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Side - Login Form --}}
        <div
            class="flex-1 flex flex-col items-center justify-center p-6 md:p-10 bg-gray-50 dark:bg-gradient-to-br dark:from-gray-900 dark:via-gray-900 dark:to-gray-800">
            <div class="w-full max-w-md">
                {{-- Mobile Logo --}}
                <div class="lg:hidden flex flex-col items-center mb-8">
                    <div class="p-2 bg-white dark:bg-gray-800 rounded-2xl shadow-xl mb-4">
                        <img src="{{ asset('FastClaim_Icon.png') }}" alt="FastClaim"
                            class="w-16 h-16 object-contain dark:hidden" />
                        <img src="{{ asset('FastClaim_Icon_dark.png') }}" alt="FastClaim"
                            class="w-16 h-16 object-contain hidden dark:block" />
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-50">
                        {{ config('app.name', 'Fast Claim') }}
                    </h1>
                </div>

                <div class="flex flex-col gap-6">
                    {{ $slot }}
                </div>

                {{-- Footer --}}
                <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        © {{ date('Y') }} {{ config('app.name', 'Fast Claim') }}. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </div>
    @fluxScripts
</body>

</html>