@extends('layouts.site')
@section('title', $page->seo_title ?: $page->title)
@section('description', $page->seo_description ?? '')
@section('content')
    <article class="article-body">
        <h1>
            {{ $page->title }}
        </h1>
        <div class="prose">
            {!! $content['html'] !!}
        </div>
    </article>
@endsection
