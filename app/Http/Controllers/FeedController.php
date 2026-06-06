<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class FeedController extends Controller
{
    private function convertJsonCookiesToNetscape($jsonPath)
    {
        if (!file_exists($jsonPath)) {
            return null;
        }

        try {
            $jsonContent = file_get_contents($jsonPath);
            $cookies = json_decode($jsonContent, true);

            if (!is_array($cookies)) {
                return null;
            }

            // Create Netscape format header
            $netscape = "# Netscape HTTP Cookie File\n";
            $netscape .= "# http://curl.haxx.se/rfc/cookie_spec.html\n";
            $netscape .= "# This is a generated file!  Do not edit.\n\n";

            // Convert each cookie to Netscape format
            foreach ($cookies as $cookie) {
                // Skip if required fields are missing
                if (!isset($cookie['name']) || !isset($cookie['value'])) {
                    continue;
                }

                $domain = $cookie['domain'] ?? '.youtube.com';
                $domainSpecified = ($cookie['hostOnly'] ?? false) ? 'FALSE' : 'TRUE';
                $path = $cookie['path'] ?? '/';
                $secure = ($cookie['secure'] ?? false) ? 'TRUE' : 'FALSE';
                $expiration = (int)($cookie['expirationDate'] ?? 0);
                $name = $cookie['name'] ?? '';
                $value = $cookie['value'] ?? '';

                // Format: domain domain_specified path secure expiration name value
                $netscape .= sprintf(
                    "%s\t%s\t%s\t%s\t%d\t%s\t%s\n",
                    $domain,
                    $domainSpecified,
                    $path,
                    $secure,
                    $expiration,
                    $name,
                    $value
                );
            }

            return $netscape;
        } catch (\Exception $e) {
            Log::error("Failed to convert cookies from JSON: " . $e->getMessage());
            return null;
        }
    }

    private function extractYouTubeId($url)
    {
        $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
        preg_match($pattern, $url, $matches);
        return $matches[1] ?? null;
    }

    private function fetchYouTubeMetadata($videoId)
    {
        try {
            $response = Http::get("https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v={$videoId}&format=json");
            
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'title' => $data['title'] ?? null,
                    'thumbnail' => $data['thumbnail_url'] ?? "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg",
                ];
            }
        } catch (\Exception $e) {
            // Fallback to default thumbnail if API fails
        }

        return [
            'title' => null,
            'thumbnail' => "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg",
        ];
    }

    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // Generate a unique ID for the feed
        $feedId = Str::random(32);

        // Create the feed data structure
        $feedData = [
            'id' => $feedId,
            'name' => $request->name,
            'description' => '',
            'author' => '',
            'language' => 'en',
            'created_at' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
            'episodes' => [],
        ];

        // Ensure the feeds directory exists
        $feedsDir = storage_path('app/private/feeds');
        if (!is_dir($feedsDir)) {
            mkdir($feedsDir, 0755, true);
        }

        // Save the feed data to a JSON file
        $feedFile = $feedsDir . '/' . $feedId . '.json';
        file_put_contents($feedFile, json_encode($feedData, JSON_PRETTY_PRINT));

        // Redirect to the edit page
        return redirect()->route('feed.edit', ['id' => $feedId]);
    }

    public function edit($id)
    {
        $feedFile = storage_path('app/private/feeds/' . $id . '.json');
        
        if (!file_exists($feedFile)) {
            abort(404, 'Feed not found');
        }

        $feedData = json_decode(file_get_contents($feedFile), true);

        // Try to load cookies from the JSON file
        $cookiesJsonPath = storage_path('app/private/cookies.json');
        $currentCookies = '';
        if (file_exists($cookiesJsonPath)) {
            $convertedCookies = $this->convertJsonCookiesToNetscape($cookiesJsonPath);
            if ($convertedCookies !== null) {
                $currentCookies = $convertedCookies;
            }
        }

        // Fall back to feed-specific cookies file if JSON conversion failed
        if (empty($currentCookies)) {
            $feedCookiesPath = storage_path('app/private/feeds/' . $id . '.cookies.txt');
            if (file_exists($feedCookiesPath)) {
                $currentCookies = file_get_contents($feedCookiesPath);
            }
        }

        return view('feed.edit', [
            'feed' => $feedData,
            'currentCookies' => $currentCookies,
        ]);
    }

    public function update(Request $request, $id)
    {
        $feedFile = storage_path('app/private/feeds/' . $id . '.json');
        
        if (!file_exists($feedFile)) {
            abort(404, 'Feed not found');
        }

        $feedData = json_decode(file_get_contents($feedFile), true);

        // Update feed metadata
        $feedData['name'] = $request->input('name', $feedData['name']);
        $feedData['description'] = $request->input('description', $feedData['description']);
        $feedData['author'] = $request->input('author', $feedData['author']);
        $feedData['updated_at'] = now()->toISOString();

        // Handle YouTube URL additions
        if ($request->has('youtube_url') && !empty($request->input('youtube_url'))) {
            $youtubeUrl = $request->input('youtube_url');
            $videoId = $this->extractYouTubeId($youtubeUrl);
            $metadata = $videoId ? $this->fetchYouTubeMetadata($videoId) : ['title' => null, 'thumbnail' => null];
            
            $feedData['episodes'][] = [
                'id' => Str::random(16),
                'youtube_url' => $youtubeUrl,
                'video_id' => $videoId,
                'title' => $metadata['title'],
                'thumbnail' => $metadata['thumbnail'],
                'added_at' => now()->toISOString(),
            ];
        }

        // Handle episode removal
        if ($request->has('remove_episode')) {
            $episodeIdToRemove = $request->input('remove_episode');
            $feedData['episodes'] = array_filter($feedData['episodes'], function($episode) use ($episodeIdToRemove) {
                return $episode['id'] !== $episodeIdToRemove;
            });
            $feedData['episodes'] = array_values($feedData['episodes']);
        }

        file_put_contents($feedFile, json_encode($feedData, JSON_PRETTY_PRINT));

        return redirect()->route('feed.edit', ['id' => $id]);
    }

    public function index()
    {
        $feedsDir = storage_path('app/private/feeds');
        $feeds = [];

        if (is_dir($feedsDir)) {
            $files = glob($feedsDir . '/*.json');
            foreach ($files as $file) {
                $feedData = json_decode(file_get_contents($file), true);
                if ($feedData) {
                    $feeds[] = $feedData;
                }
            }
        }

        // Sort by created date, newest first
        usort($feeds, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return view('home', ['feeds' => $feeds]);
    }

    public function rss($id)
    {
        $feedFile = storage_path('app/private/feeds/' . $id . '.json');
        
        if (!file_exists($feedFile)) {
            abort(404, 'Feed not found');
        }

        $feedData = json_decode(file_get_contents($feedFile), true);

        // Generate RSS XML
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rss version="2.0" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"></rss>');
        
        $channel = $xml->addChild('channel');
        $channel->addChild('title', htmlspecialchars($feedData['name']));
        $editUrl = route('feed.edit', ['id' => $id]);
        $description = ($feedData['description'] ?? '') . "\n\nEdit this podcast: " . $editUrl;
        $channel->addChild('description', htmlspecialchars($description));
        $channel->addChild('link', url('/feeds/' . $id . '.rss'));
        $channel->addChild('language', htmlspecialchars($feedData['language'] ?? 'en'));
        
        if (!empty($feedData['author'])) {
            $channel->addChild('itunes:author', htmlspecialchars($feedData['author']), 'http://www.itunes.com/dtds/podcast-1.0.dtd');
        }

        // Add episodes
        foreach ($feedData['episodes'] as $episode) {
            $item = $channel->addChild('item');
            
            $title = $episode['title'] ?? 'Unknown Title';
            $item->addChild('title', htmlspecialchars($title));
            
            $description = $episode['youtube_url'] ?? '';
            $item->addChild('description', htmlspecialchars($description));
            
            // Link to the audio streaming endpoint instead of YouTube
            $audioUrl = url('/feeds/' . $id . '/episodes/' . $episode['id'] . '/audio');
            $item->addChild('link', htmlspecialchars($audioUrl));
            
            $guidValue = $episode['id'] ?? uniqid();
            $guidElement = $item->addChild('guid', $guidValue);
            $guidElement->addAttribute('isPermaLink', 'false');
            
            // Use the added_at date as pubDate
            if (isset($episode['added_at'])) {
                $pubDate = date('r', strtotime($episode['added_at']));
                $item->addChild('pubDate', $pubDate);
            }
            
            // Add enclosure for podcast apps
            $audioUrl = url('/feeds/' . $id . '/episodes/' . $episode['id'] . '/audio');
            $enclosure = $item->addChild('enclosure');
            $enclosure->addAttribute('url', $audioUrl);
            $enclosure->addAttribute('type', 'audio/mpeg');
            $enclosure->addAttribute('length', '0'); // Will be updated if we can get the file size
        }

        // Return XML response
        return response($xml->asXML(), 200)
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }

    public function audio($id, $episodeId)
    {
        $feedFile = storage_path('app/private/feeds/' . $id . '.json');
        
        if (!file_exists($feedFile)) {
            abort(404, 'Feed not found');
        }

        $feedData = json_decode(file_get_contents($feedFile), true);

        // Find the episode
        $episode = null;
        foreach ($feedData['episodes'] as $ep) {
            if ($ep['id'] === $episodeId) {
                $episode = $ep;
                break;
            }
        }

        if (!$episode) {
            abort(404, 'Episode not found');
        }

        $youtubeUrl = $episode['youtube_url'];

        // Use local bin/yt-dlp if it exists, otherwise use system yt-dlp
        $ytDlpPath = 'yt-dlp';
        if (file_exists(base_path('bin/yt-dlp'))) {
            $ytDlpPath = base_path('bin/yt-dlp');
        }

        return response()->stream(function () use ($youtubeUrl, $ytDlpPath, $episodeId, $id) {
            // Check for per-feed cookies file
            $cookiesPath = storage_path('app/private/feeds/' . $id . '.cookies.txt');
            
            $authArg = '';
            $usingCookies = false;
            if (file_exists($cookiesPath)) {
                $authArg = '--cookies ' . escapeshellarg($cookiesPath);
                $usingCookies = true;
            }

            // Stream from yt-dlp directly into ffmpeg
            // Added --extractor-args to try and bypass bot detection
            $command = sprintf(
                '%s %s -f "ba/b" --no-playlist --no-warnings --extractor-args "youtube:player_client=android,web" %s -o - | ffmpeg -i pipe:0 -f mp3 -b:a 128k -map 0:a -',
                escapeshellarg($ytDlpPath),
                $authArg,
                escapeshellarg($youtubeUrl)
            );

            Log::info("Starting audio stream for episode {$episodeId} in feed {$id}", [
                'url' => $youtubeUrl,
                'command' => $command,
                'using_cookies' => $usingCookies
            ]);

            $descriptorspec = [
                0 => ["pipe", "r"], // stdin
                1 => ["pipe", "w"], // stdout
                2 => ["pipe", "w"]  // stderr
            ];

            $process = proc_open($command, $descriptorspec, $pipes);

            if (is_resource($process)) {
                // We don't need stdin
                fclose($pipes[0]);

                $totalBytes = 0;
                $chunkCount = 0;

                // Stream the output from the pipeline to the browser
                while (!feof($pipes[1])) {
                    if (connection_aborted()) {
                        Log::warning("Connection aborted while streaming episode {$episodeId}");
                        break;
                    }
                    $chunk = fread($pipes[1], 16384);
                    $bytes = strlen($chunk);
                    $totalBytes += $bytes;
                    $chunkCount++;
                    
                    echo $chunk;
                    flush();
                }

                $errors = stream_get_contents($pipes[2]);
                $exitCode = proc_close($process);

                Log::info("Finished streaming episode {$episodeId}", [
                    'total_bytes' => $totalBytes,
                    'chunks' => $chunkCount,
                    'exit_code' => $exitCode
                ]);

                if (!empty($errors)) {
                     Log::error("Stream stderr for episode {$episodeId}: " . $errors);
                }
            } else {
                Log::error("Failed to start streaming process for episode {$episodeId}");
            }
        }, 200, [
            'Content-Type' => 'audio/mpeg',
            'Content-Disposition' => 'attachment; filename="episode_' . $episodeId . '.mp3"',
            'X-Accel-Buffering' => 'no',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    public function saveCookies(Request $request, $id)
    {
        $cookiesJsonPath = storage_path('app/private/cookies.json');
        
        // Convert cookies from JSON file to Netscape format
        $netscapeCookies = $this->convertJsonCookiesToNetscape($cookiesJsonPath);
        
        if ($netscapeCookies === null) {
            return back()->with('error', 'Cookies file not found or invalid.');
        }

        // Save the converted cookies to the feed-specific cookies file
        $cookiesPath = storage_path('app/private/feeds/' . $id . '.cookies.txt');
        file_put_contents($cookiesPath, $netscapeCookies);

        return back()->with('success', 'YouTube cookies loaded and saved from cookies.json file.');
    }
}
