
@extends('errors::minimal')

@section('title', __('Page Expired'))
@section('code', '419')
@section('message', __('页面已经过期,请点击浏览器后退'))

<script>
    location.href = '/admin'
</script>
