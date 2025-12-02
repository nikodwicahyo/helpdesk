<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $article->title }} - Knowledge Base</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 210mm;
            margin: 0 auto;
            padding: 20mm;
        }
        .header {
            border-bottom: 3px solid #4F46E5;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .header h1 {
            color: #4F46E5;
            font-size: 28px;
            margin: 0 0 10px 0;
        }
        .meta-info {
            font-size: 12px;
            color: #666;
            margin-bottom: 20px;
        }
        .meta-info span {
            margin-right: 20px;
            display: inline-block;
        }
        .meta-info strong {
            color: #333;
        }
        .content {
            margin: 25px 0;
            text-align: justify;
        }
        .content h2, .content h3 {
            color: #4F46E5;
            margin-top: 20px;
        }
        .content p {
            margin-bottom: 12px;
        }
        .content ul, .content ol {
            margin-left: 20px;
            margin-bottom: 15px;
        }
        .content code {
            background-color: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .content pre {
            background-color: #f4f4f4;
            padding: 12px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .tags {
            margin: 20px 0;
            padding: 15px;
            background-color: #f9fafb;
            border-radius: 5px;
        }
        .tag {
            display: inline-block;
            background-color: #4F46E5;
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            margin-right: 8px;
            margin-bottom: 5px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 11px;
            color: #666;
            text-align: center;
        }
        .stats {
            margin: 15px 0;
            padding: 12px;
            background-color: #f0f9ff;
            border-left: 4px solid #4F46E5;
            font-size: 13px;
        }
        .stats span {
            margin-right: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        table th {
            background-color: #4F46E5;
            color: white;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $article->title }}</h1>
        <div class="meta-info">
            @if($article->author)
            <span><strong>Author:</strong> {{ $article->author->name }}</span>
            @endif
            @if($article->aplikasi)
            <span><strong>Application:</strong> {{ $article->aplikasi->name }}</span>
            @endif
            @if($article->kategoriMasalah)
            <span><strong>Category:</strong> {{ $article->kategoriMasalah->name }}</span>
            @endif
            <span><strong>Status:</strong> {{ ucfirst($article->status) }}</span>
        </div>
    </div>

    @if($article->summary)
    <div style="background-color: #fffbeb; padding: 15px; border-left: 4px solid #f59e0b; margin-bottom: 20px;">
        <strong style="color: #92400e;">Summary:</strong>
        <p style="margin: 5px 0 0 0; color: #78350f;">{{ $article->summary }}</p>
    </div>
    @endif

    <div class="stats">
        <span><strong>Views:</strong> {{ $article->view_count ?? 0 }}</span>
        <span><strong>Helpful Votes:</strong> {{ $article->helpful_count ?? 0 }}</span>
        <span><strong>Reading Time:</strong> ~{{ $article->reading_time }} min</span>
        @if($article->is_featured)
        <span style="color: #d97706;"><strong>⭐ Featured Article</strong></span>
        @endif
    </div>

    <div class="content">
        {!! $article->content !!}
    </div>

    @if($article->tags && count($article->tags) > 0)
    <div class="tags">
        <strong style="color: #4F46E5;">Tags:</strong><br>
        @foreach($article->tags as $tag)
            <span class="tag">{{ $tag }}</span>
        @endforeach
    </div>
    @endif

    <div class="footer">
        <p>
            <strong>HelpDesk Kemlu - Knowledge Base</strong><br>
            Kementerian Luar Negeri Republik Indonesia<br>
            Generated on: {{ $generated_at }}<br>
            Created: {{ $article->created_at->format('d M Y H:i') }} | 
            Last Updated: {{ $article->updated_at->format('d M Y H:i') }}
        </p>
        <p style="font-size: 10px; color: #999;">
            This document is confidential and intended for internal use only.
        </p>
    </div>
</body>
</html>
