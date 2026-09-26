<?php

namespace App\Http\Controllers;

use App\Models\Live;
use App\Models\Post;
use App\Models\Video;

/** Intégrations médias : lecteurs intégrables (iframe) pour les sites de presse et partenaires. */
class EmbedController extends Controller
{
    public function video(Video $video)
    {
        abort_if($video->is_hidden || $video->visibility === 'private', 404);
        $video->load(['user', 'subtitles']);

        return view('embed.video', compact('video'));
    }

    public function live(Live $live)
    {
        abort_if($live->is_hidden, 404);
        $live->load(['user', 'replay.subtitles']);

        return view('embed.live', compact('live'));
    }

    public function post(Post $post)
    {
        abort_if($post->is_hidden || $post->visibility !== 'public', 404);
        $post->load(['user', 'media']);

        return view('embed.post', compact('post'));
    }
}
