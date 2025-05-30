<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>InvoicePro - Professional Invoicing Made Simple</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet"/>

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: #1f2937;
        }

        .hero-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .feature-card {
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(102, 126, 234, 0.4);
        }

        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 0.6s ease forwards;
        }

        .fade-in:nth-child(1) { animation-delay: 0.1s; }
        .fade-in:nth-child(2) { animation-delay: 0.2s; }
        .fade-in:nth-child(3) { animation-delay: 0.3s; }
        .fade-in:nth-child(4) { animation-delay: 0.4s; }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .floating {
            animation: floating 6s ease-in-out infinite;
        }

        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
    </style>
</head>

<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm fixed w-full top-0 z-50" x-data="{ open: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <svg class="h-8 w-8 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="ml-2 text-xl font-bold text-gray-900">InvoicePro</span>
                    </div>
                </div>

                <div class="hidden md:flex items-center space-x-8">
                    <a href="#features" class="text-gray-600 hover:text-indigo-600 transition duration-300">Features</a>
                    <a href="#pricing" class="text-gray-600 hover:text-indigo-600 transition duration-300">Pricing</a>
                    <a href="#contact" class="text-gray-600 hover:text-indigo-600 transition duration-300">Contact</a>

                    @auth
                        <a href="{{ \Filament\Facades\Filament::getCurrentPanel()->getUrl() }}"
                           class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition duration-300">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ \Filament\Facades\Filament::getCurrentPanel()->getLoginUrl() }}"
                           class="text-indigo-600 hover:text-indigo-800 transition duration-300">
                            Sign In
                        </a>
                        <a href="{{ \Filament\Facades\Filament::getCurrentPanel()->getLoginUrl() }}"
                           class="btn-primary text-white px-6 py-2 rounded-lg font-medium">
                            Get Started
                        </a>
                    @endauth
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button @click="open = !open" class="text-gray-600 hover:text-gray-900 focus:outline-none">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path :class="{'hidden': open, 'inline-flex': !open }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            <path :class="{'hidden': !open, 'inline-flex': open }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div x-show="open" class="md:hidden bg-white border-t border-gray-200">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="#features" class="block px-3 py-2 text-gray-600 hover:text-indigo-600">Features</a>
                <a href="#pricing" class="block px-3 py-2 text-gray-600 hover:text-indigo-600">Pricing</a>
                <a href="#contact" class="block px-3 py-2 text-gray-600 hover:text-indigo-600">Contact</a>
                @auth
                    <a href="{{ \Filament\Facades\Filament::getCurrentPanel()->getUrl() }}" class="block px-3 py-2 text-indigo-600 font-medium">Dashboard</a>
                @else
                    <a href="{{ \Filament\Facades\Filament::getCurrentPanel()->getLoginUrl() }}" class="block px-3 py-2 text-indigo-600">Sign In</a>
                    <a href="{{ \Filament\Facades\Filament::getCurrentPanel()->getLoginUrl() }}" class="block px-3 py-2 text-white bg-indigo-600 rounded-lg font-medium">Get Started</a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-gradient pt-20 pb-20 relative overflow-hidden">
        <div class="absolute inset-0 bg-black opacity-10"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="text-white">
                    <h1 class="text-5xl lg:text-6xl font-bold leading-tight fade-in">
                        Professional Invoicing
                        <span class="text-yellow-300">Made Simple</span>
                    </h1>
                    <p class="text-xl mt-6 text-indigo-100 fade-in">
                        Create, send, and track professional invoices in minutes. Get paid faster with our intuitive invoicing platform designed for modern businesses.
                    </p>
                    <div class="mt-8 flex flex-col sm:flex-row gap-4 fade-in">
                        @guest
                            <a href="{{ \Filament\Facades\Filament::getCurrentPanel()->getLoginUrl() }}"
                               class="bg-white text-indigo-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-300 text-center">
                                Start Free Trial
                            </a>
                            <a href="#features"
                               class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-indigo-600 transition duration-300 text-center">
                                Learn More
                            </a>
                        @else
                            <a href="{{ \Filament\Facades\Filament::getCurrentPanel()->getUrl() }}"
                               class="bg-white text-indigo-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-300 text-center">
                                Go to Dashboard
                            </a>
                        @endguest
                    </div>
                </div>
                <div class="hidden lg:block">
                    <div class="floating">
                        <svg class="w-full h-auto max-w-lg mx-auto" viewBox="0 0 400 300" fill="none">
                            <!-- Invoice mockup -->
                            <rect x="50" y="30" width="300" height="240" fill="white" rx="8" stroke="#e5e7eb" stroke-width="2"/>
                            <rect x="70" y="50" width="80" height="20" fill="#667eea" rx="4"/>
                            <rect x="70" y="80" width="120" height="8" fill="#e5e7eb" rx="4"/>
                            <rect x="70" y="95" width="100" height="8" fill="#e5e7eb" rx="4"/>
                            <rect x="70" y="125" width="260" height="1" fill="#e5e7eb"/>
                            <rect x="70" y="145" width="80" height="8" fill="#f3f4f6" rx="4"/>
                            <rect x="70" y="160" width="120" height="8" fill="#f3f4f6" rx="4"/>
                            <rect x="70" y="175" width="90" height="8" fill="#f3f4f6" rx="4"/>
                            <rect x="70" y="205" width="260" height="1" fill="#e5e7eb"/>
                            <rect x="250" y="220" width="80" height="15" fill="#10b981" rx="4"/>
                            <!-- Floating elements -->
                            <circle cx="320" cy="80" r="25" fill="#fbbf24" opacity="0.8"/>
                            <rect x="30" y="160" width="15" height="15" fill="#ef4444" rx="3" opacity="0.8"/>
                            <polygon points="360,200 370,220 350,220" fill="#8b5cf6" opacity="0.8"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Everything You Need to Get Paid</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Streamline your billing process with powerful features designed to save time and improve cash flow.
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="feature-card bg-white p-8 rounded-xl shadow-lg border border-gray-100 fade-in">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Professional Templates</h3>
                    <p class="text-gray-600">Choose from beautiful, customizable invoice templates that reflect your brand and impress your clients.</p>
                </div>

                <div class="feature-card bg-white p-8 rounded-xl shadow-lg border border-gray-100 fade-in">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Fast Payments</h3>
                    <p class="text-gray-600">Accept payments online with integrated payment processing. Get paid up to 3x faster than traditional methods.</p>
                </div>

                <div class="feature-card bg-white p-8 rounded-xl shadow-lg border border-gray-100 fade-in">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Smart Analytics</h3>
                    <p class="text-gray-600">Track your revenue, monitor payment trends, and gain insights to grow your business with detailed reporting.</p>
                </div>

                <div class="feature-card bg-white p-8 rounded-xl shadow-lg border border-gray-100 fade-in">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Automated Reminders</h3>
                    <p class="text-gray-600">Never chase payments again. Set up automatic reminder emails for overdue invoices and improve cash flow.</p>
                </div>

                <div class="feature-card bg-white p-8 rounded-xl shadow-lg border border-gray-100 fade-in">
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Client Management</h3>
                    <p class="text-gray-600">Keep all your client information organized. Store contact details, payment history, and preferences in one place.</p>
                </div>

                <div class="feature-card bg-white p-8 rounded-xl shadow-lg border border-gray-100 fade-in">
                    <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Secure & Reliable</h3>
                    <p class="text-gray-600">Your data is protected with bank-level security. Automatic backups ensure your information is always safe.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8 text-center">
                <div class="fade-in">
                    <div class="text-4xl font-bold text-indigo-600 mb-2">50K+</div>
                    <div class="text-gray-600">Happy Clients</div>
                </div>
                <div class="fade-in">
                    <div class="text-4xl font-bold text-indigo-600 mb-2">$2M+</div>
                    <div class="text-gray-600">Processed Monthly</div>
                </div>
                <div class="fade-in">
                    <div class="text-4xl font-bold text-indigo-600 mb-2">99.9%</div>
                    <div class="text-gray-600">Uptime</div>
                </div>
                <div class="fade-in">
                    <div class="text-4xl font-bold text-indigo-600 mb-2">24/7</div>
                    <div class="text-gray-600">Support</div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-indigo-600">
        <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-bold text-white mb-6">Ready to Transform Your Invoicing?</h2>
            <p class="text-xl text-indigo-100 mb-8">
                Join thousands of businesses that trust InvoicePro for their billing needs. Start your free trial today.
            </p>
            @guest
                <a href="{{ \Filament\Facades\Filament::getCurrentPanel()->getLoginUrl() }}"
                   class="bg-white text-indigo-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-300 inline-block">
                    Start Free Trial - No Credit Card Required
                </a>
            @else
                <a href="{{ \Filament\Facades\Filament::getCurrentPanel()->getUrl() }}"
                   class="bg-white text-indigo-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-300 inline-block">
                    Access Your Dashboard
                </a>
            @endguest
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center mb-4">
                        <svg class="h-8 w-8 text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="ml-2 text-xl font-bold">InvoicePro</span>
                    </div>
                    <p class="text-gray-400">Professional invoicing made simple for modern businesses.</p>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Product</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition duration-300">Features</a></li>
                        <li><a href="#" class="hover:text-white transition duration-300">Pricing</a></li>
                        <li><a href="#" class="hover:text-white transition duration-300">Templates</a></li>
                        <li><a href="#" class="hover:text-white transition duration-300">Integrations</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Company</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition duration-300">About</a></li>
                        <li><a href="#" class="hover:text-white transition duration-300">Blog</a></li>
                        <li><a href="#" class="hover:text-white transition duration-300">Careers</a></li>
                        <li><a href="#" class="hover:text-white transition duration-300">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Support</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition duration-300">Help Center</a></li>
                        <li><a href="#" class="hover:text-white transition duration-300">Documentation</a></li>
                        <li><a href="#" class="hover:text-white transition duration-300">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-white transition duration-300">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; {{ date('Y') }} InvoicePro. All rights reserved. Built with Laravel v{{ Illuminate\Foundation\Application::VERSION }}</p>
            </div>
        </div>
    </footer>
</body>
</html>
