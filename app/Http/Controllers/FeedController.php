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

        return view('feed.edit', ['feed' => $feedData]);
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

        return response()->stream(function () use ($youtubeUrl, $ytDlpPath) {
            // Step 1: Get the direct stream URL from yt-dlp
            // This is much more reliable than piping raw data between processes
            $urlCommand = sprintf(
                '%s -f bestaudio --get-url --no-playlist --no-warnings %s',
                escapeshellarg($ytDlpPath),
                escapeshellarg($youtubeUrl)
            );

            $directUrl = trim(shell_exec($urlCommand));

            if (empty($directUrl) || !filter_var($directUrl, FILTER_VALIDATE_URL)) {
                Log::error("Could not get direct URL for: " . $youtubeUrl);
                return;
            }

            // Step 2: Stream the direct URL through ffmpeg to convert to MP3 on the fly
            // Using direct URL allows ffmpeg to handle the stream much better
            $ffmpegCommand = sprintf(
                'ffmpeg -i %s -f mp3 -b:a 128k -map 0:a -',
                escapeshellarg($directUrl)
            );

            $descriptorspec = [
                0 => ["pipe", "r"], // stdin
                1 => ["pipe", "w"], // stdout
                2 => ["pipe", "w"]  // stderr
            ];

            $process = proc_open($ffmpegCommand, $descriptorspec, $pipes);

            if (is_resource($process)) {
                // We don't need stdin
                fclose($pipes[0]);

                // Stream the output from ffmpeg's stdout to the browser
                while (!feof($pipes[1])) {
                    if (connection_aborted()) {
                        break;
                    }
                    echo fread($pipes[1], 16384);
                    flush();
                }

                $errors = stream_get_contents($pipes[2]);
                if ($errors && !feof($pipes[1])) {
                     Log::error("FFmpeg error: " . $errors);
                }

                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($process);
            }
        }, 200, [
            'Content-Type' => 'audio/mpeg',
            'Content-Disposition' => 'attachment; filename="episode_' . $episodeId . '.mp3"',
            'X-Accel-Buffering' => 'no',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }
}
