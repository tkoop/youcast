<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>YouCast - Create Podcasts from YouTube Videos</title>

    @vite(['resources/css/app.css'])
</head>

<body class="bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 min-h-screen">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="py-6 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div
                         class="w-10 h-10 bg-gradient-to-r from-red-500 to-pink-500 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                  d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
                        </svg>
                    </div>
                    <span class="text-2xl font-bold text-white">YouCast</span>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 flex items-center justify-center px-4 sm:px-6 lg:px-8 py-12">
            <div class="max-w-4xl w-full">
                <!-- Hero Section -->
                <div class="text-center mb-16">
                    <h1 class="text-5xl sm:text-6xl font-extrabold text-white mb-6">
                        Turn YouTube Videos into
                        <span class="bg-gradient-to-r from-red-400 to-pink-400 bg-clip-text text-transparent">
                            Podcasts
                        </span>
                    </h1>
                    <p class="text-xl text-gray-300 max-w-2xl mx-auto mb-8">
                        Create your own RSS feed (podcast) containing your favorite YouTube videos as audio.
                        Simply paste YouTube URLs and listen in your favorite podcast app.
                    </p>
                </div>

                <!-- Features -->
                <div class="grid md:grid-cols-3 gap-8 mb-16">
                    <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                        <div
                             class="w-12 h-12 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-white mb-2">Paste YouTube URLs</h3>
                        <p class="text-gray-300">Add any YouTube video to your podcast by simply pasting the URL.</p>
                    </div>

                    <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                        <div
                             class="w-12 h-12 bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-white mb-2">Audio-Only Playback</h3>
                        <p class="text-gray-300">Listen to your favorite YouTube content as audio, perfect for
                            on-the-go.</p>
                    </div>

                    <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                        <div
                             class="w-12 h-12 bg-gradient-to-r from-orange-500 to-red-500 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-white mb-2">Any Podcast App</h3>
                        <p class="text-gray-300">Subscribe to your feed in Apple Podcasts, Spotify, or any RSS reader.
                        </p>
                    </div>
                </div>

                <!-- Get Started Section -->
                <div class="bg-white/10 backdrop-blur-lg rounded-3xl p-8 sm:p-12 border border-white/20">
                    <h2 class="text-3xl font-bold text-white mb-6 text-center">Get Started</h2>
                    <p class="text-gray-300 text-center mb-8">Create your first podcast feed in seconds</p>

                    <form action="{{ route('feed.create') }}" method="POST" class="max-w-md mx-auto">
                        @csrf
                        <div class="mb-6">
                            <label for="name" class="block text-sm font-medium text-gray-300 mb-2">
                                Podcast Name
                            </label>
                            <input type="text" id="name" name="name" required
                                   class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition"
                                   placeholder="My Awesome Podcast">
                        </div>
                        <button type="submit"
                                class="w-full bg-gradient-to-r from-red-500 to-pink-500 hover:from-red-600 hover:to-pink-600 text-white font-semibold py-3 px-6 rounded-xl transition duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:ring-offset-2 focus:ring-offset-slate-900">
                            Create Podcast
                        </button>
                    </form>
                </div>

                <!-- My Podcasts Section -->
                @if (isset($feeds) && count($feeds) > 0)
                    <div class="mt-16">
                        <h2 class="text-3xl font-bold text-white mb-8 text-center">My Podcasts</h2>
                        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach ($feeds as $feed)
                                <a href="{{ route('feed.edit', ['id' => $feed['id']]) }}"
                                   class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20 hover:bg-white/15 transition duration-200 group">
                                    <div class="flex items-start justify-between mb-4">
                                        <div
                                             class="w-12 h-12 bg-gradient-to-r from-red-500 to-pink-500 rounded-xl flex items-center justify-center">
                                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                <path
                                                      d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
                                            </svg>
                                        </div>
                                        <span class="text-xs text-gray-400">{{ count($feed['episodes']) }}
                                            episode{{ count($feed['episodes']) !== 1 ? 's' : '' }}</span>
                                    </div>
                                    <h3
                                        class="text-xl font-semibold text-white mb-2 group-hover:text-pink-400 transition">
                                        {{ $feed['name'] }}</h3>
                                    @if (!empty($feed['description']))
                                        <p class="text-gray-400 text-sm line-clamp-2">{{ $feed['description'] }}</p>
                                    @endif
                                    <p class="text-xs text-gray-500 mt-3">
                                        Created {{ \Carbon\Carbon::parse($feed['created_at'])->format('M j, Y') }}
                                    </p>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </main>

        <!-- Footer -->
        <footer class="py-6 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto text-center text-gray-400 text-sm">
                <p>No account required. Your podcasts are private and accessible only via their unique feed URL.</p>
            </div>
        </footer>
    </div>
</body>

</html>
