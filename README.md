# YouCast

YouCast is a simple web application that allows you to turn YouTube videos into private RSS feeds (podcasts). Simply paste a YouTube URL, and YouCast will provide an audio stream that you can subscribe to in any podcast app.

## Features

- **Quick Conversion:** Turn any YouTube video into a podcast episode by pasting its URL.
- **Direct Streaming:** Audio is extracted and streamed in real-time, meaning no long wait times or high server storage usage.
- **Private Feeds:** Each podcast you create has a unique, private RSS URL.
- **Podcast App Compatible:** Works with Apple Podcasts, Spotify, Overcast, and any other RSS-compliant podcast reader.

## Getting Started

### Prerequisites

- PHP 8.3+
- Composer
- Node.js & NPM
- `yt-dlp` installed and available in your PATH

### Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/your-username/youcast.git
   cd youcast
   ```

2. Run the setup command:
   ```bash
   composer run setup
   ```

3. Start the development server:
   ```bash
   composer run dev
   ```

## License

The YouCast project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
