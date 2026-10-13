<?php

namespace App\Livewire;

use App\Services\BlogPostRepository;
use Illuminate\Support\Str;
use Livewire\Component;

class BlogShow extends Component
{
    public array $post = [];

    public string $html = '';

    public function mount(string $slug): void
    {
        $this->post = app(BlogPostRepository::class)->findOrFail($slug);
        // Prose tables scroll sideways on phones, so they take keyboard focus.
        $this->html = str_replace('<table>', '<table tabindex="0">', Str::markdown($this->post['content'], [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]));
    }

    public function render()
    {
        return view('livewire.blog-show');
    }
}
