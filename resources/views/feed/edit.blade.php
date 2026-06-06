<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Podcast - {{ $feed['name'] }}</title>

    @vite(['resources/css/app.css'])
</head>

<body class="bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 min-h-screen">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="py-6 px-4 sm:px-6 lg:px-8 border-b border-white/10">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <a href="/" class="flex items-center space-x-3 hover:opacity-80 transition">
                    <div
                         class="w-10 h-10 bg-gradient-to-r from-red-500 to-pink-500 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                  d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
                        </svg>
                    </div>
                    <span class="text-2xl font-bold text-white">YouCast</span>
                </a>
                <a href="/" class="text-gray-300 hover:text-white transition">Back to Home</a>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 px-4 sm:px-6 lg:px-8 py-12">
            <div class="max-w-4xl mx-auto">
                <!-- Page Title -->
                <div class="mb-8">
                    <h1 class="text-4xl font-bold text-white mb-2">Edit Podcast</h1>
                    <p class="text-gray-400">Manage your podcast episodes and settings</p>
                </div>

                <div class="grid lg:grid-cols-3 gap-8">
                    <!-- Left Column: Podcast Settings -->
                    <div class="lg:col-span-1">
                        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                            <h2 class="text-xl font-semibold text-white mb-6">Podcast Settings</h2>

                            <form action="{{ route('feed.update', ['id' => $feed['id']]) }}" method="POST">
                                @csrf
                                <div class="space-y-4">
                                    <div>
                                        <label for="name" class="block text-sm font-medium text-gray-300 mb-2">
                                            Podcast Name
                                        </label>
                                        <input type="text" id="name" name="name"
                                               value="{{ old('name', $feed['name']) }}"
                                               class="w-full px-4 py-2 bg-white/10 border border-white/30 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition">
                                    </div>

                                    <div>
                                        <label for="description" class="block text-sm font-medium text-gray-300 mb-2">
                                            Description
                                        </label>
                                        <textarea id="description" name="description" rows="4"
                                                  class="w-full px-4 py-2 bg-white/10 border border-white/30 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition resize-none">{{ old('description', $feed['description']) }}</textarea>
                                    </div>

                                    <div>
                                        <label for="author" class="block text-sm font-medium text-gray-300 mb-2">
                                            Author
                                        </label>
                                        <input type="text" id="author" name="author"
                                               value="{{ old('author', $feed['author']) }}"
                                               class="w-full px-4 py-2 bg-white/10 border border-white/30 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition">
                                    </div>

                                    <button type="submit"
                                            class="w-full bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white font-semibold py-2 px-4 rounded-lg transition duration-200">
                                        Save Settings
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- YouTube Authentication -->
                        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20 mt-6">
                            <h2 class="text-xl font-semibold text-white mb-4">YouTube Authentication</h2>
                            
                            <div class="space-y-6">
                                <!-- Cookies Option -->
                                <div>
                                    <p class="text-xs text-gray-400 mb-4">
                                        YouTube often blocks server IPs. To bypass this, paste your YouTube cookies here. 
                                        Use a browser extension like "Cookie Editor" to export cookies in <b>Netscape/yt-dlp</b> format.
                                    </p>
                                    <form action="{{ route('settings.youtube-cookies', ['id' => $feed['id']]) }}" method="POST">
                                        @csrf
                                        <textarea name="cookies" rows="5" 
                                            placeholder="# Netscape HTTP Cookie File..."
                                            class="w-full px-3 py-2 bg-black/30 border border-white/20 rounded-lg text-xs text-gray-300 placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-pink-500 transition mb-2 font-mono">{{ @file_get_contents(storage_path('app/private/feeds/' . $feed['id'] . '.cookies.txt')) }}</textarea>
                                        <button type="submit" class="w-full bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 text-sm">
                                            Save YouTube Cookies
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- RSS Feed URL -->
                        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20 mt-6">
                            <h2 class="text-xl font-semibold text-white mb-4">RSS Feed URL</h2>
                            <div class="bg-black/30 rounded-lg p-3 mb-3">
                                <code
                                      class="text-sm text-green-400 break-all">{{ url('/feeds/' . $feed['id'] . '.rss') }}</code>
                            </div>
                            <p class="text-xs text-gray-400">
                                Use this URL to subscribe in your favorite podcast app.
                            </p>
                        </div>
                    </div>

                    <!-- Right Column: Episodes -->
                    <div class="lg:col-span-2">
                        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                            <h2 class="text-xl font-semibold text-white mb-6">Episodes</h2>

                            <!-- Add Episode Form -->
                            <form action="{{ route('feed.update', ['id' => $feed['id']]) }}" method="POST"
                                  class="mb-8">
                                @csrf
                                <div class="flex gap-3">
                                    <input type="url" id="youtube_url" name="youtube_url"
                                           placeholder="Paste YouTube URL here..."
                                           class="flex-1 px-4 py-2 bg-white/10 border border-white/30 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition">
                                    <button type="submit"
                                            class="bg-gradient-to-r from-red-500 to-pink-500 hover:from-red-600 hover:to-pink-600 text-white font-semibold py-2 px-6 rounded-lg transition duration-200">
                                        Add Episode
                                    </button>
                                </div>
                            </form>

                            <!-- Episodes List -->
                            <div class="space-y-3">
                                @if (count($feed['episodes']) > 0)
                                    @foreach ($feed['episodes'] as $episode)
                                        <div
                                             class="bg-white/5 rounded-lg p-4 flex items-center gap-4 border border-white/10">
                                            @if (isset($episode['thumbnail']) && !empty($episode['thumbnail']))
                                                <img src="{{ $episode['thumbnail'] }}" alt="Video thumbnail"
                                                     class="w-24 h-16 object-cover rounded-lg flex-shrink-0">
                                            @else
                                                <div
                                                     class="w-24 h-16 bg-gradient-to-r from-red-500 to-pink-500 rounded-lg flex-shrink-0 flex items-center justify-center">
                                                    <svg class="w-8 h-8 text-white" fill="currentColor"
                                                         viewBox="0 0 24 24">
                                                        <path d="M8 5v14l11-7z" />
                                                    </svg>
                                                </div>
                                            @endif
                                            <div class="flex-1 min-w-0">
                                                @if (isset($episode['title']) && !empty($episode['title']))
                                                    <p class="text-white font-medium break-words">
                                                        {{ $episode['title'] }}
                                                    </p>
                                                @else
                                                    <p class="text-white font-medium break-words">
                                                        {{ $episode['youtube_url'] }}</p>
                                                @endif
                                                <p class="text-xs text-gray-400 mt-1 break-all">
                                                    {{ $episode['youtube_url'] }}
                                                </p>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    Added:
                                                    {{ \Carbon\Carbon::parse($episode['added_at'])->format('M j, Y g:i A') }}
                                                </p>
                                            </div>
                                            <form action="{{ route('feed.update', ['id' => $feed['id']]) }}"
                                                  method="POST">
                                                @csrf
                                                <input type="hidden" name="remove_episode"
                                                       value="{{ $episode['id'] }}">
                                                <button type="submit"
                                                        class="text-red-400 hover:text-red-300 transition p-2 hover:bg-red-500/10 rounded-lg"
                                                        title="Remove episode">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                         viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                              stroke-width="2"
                                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="text-center py-12">
                                        <svg class="w-16 h-16 text-gray-500 mx-auto mb-4" fill="none"
                                             stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                        <p class="text-gray-400">No episodes yet</p>
                                        <p class="text-gray-500 text-sm mt-1">Add your first YouTube URL above</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // JS logic for checking status removed because OAuth is no longer supported by yt-dlp
        });
    </script>
</body>

</html>
