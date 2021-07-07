@extends('admin::index', ['header' => strip_tags($header)])

@section('content')

<style>
    #menuThree a {
        margin-right: 30px; 
        font-size: 16px; 
        color: #333; 
        height: 60px;
        line-height: 60px;
        padding: 0 10px;
        display: inline-block;
    }
    #menuThree a:hover {color: #3c8dbc;}
    #menuThree.btn-group{
        width: calc(100% - 30px);
        height: 60px;
        line-height: 60px;
        background-color: #fff;
        margin: 10px;
        border-radius: 6px;
        padding: 0 30px;
        box-sizing: border-box;
    }
    .active_nav_item{
        color: #3c8dbc;
        border-bottom: 2px solid #3c8dbc;
        font-weight: 600;
    }
    .content-header h1 {
        font-size: 16px;
        font-weight: 600;
    }
    .skin-blue-light .main-header .navbar {
        background-color: #0549f5;
    }
    .skin-blue-light .main-header .logo {
        background-color: #0549f5;
    }
    .skin-blue-light .main-header .logo:hover {
        background-color: #0549f5
    }
    .box {
        border-top:none;
        box-shadow:none;
        border-radius: 6px;
        overflow: hidden;
    }
    .nav-tabs-custom {
        border-top:none;
        box-shadow:none;
        border-radius: 6px;
        overflow: hidden;
    }
    .btn-instagram {
        background-color: #0549f5
    }
    
    .btn-instagram:focus,.btn-instagram.focus {
        color: #fff;
        background-color: #0549f5;
        border-color: rgba(0,0,0,0.2)
    }

    .btn-instagram:hover {
        color: #fff;
        background-color: #3962ee;
        border-color: rgba(0,0,0,0.2)
    }

    .btn-instagram:active,.btn-instagram.active,.open>.dropdown-toggle.btn-instagram {
        color: #fff;
        background-color: #0549f5;
        border-color: rgba(0,0,0,0.2)
    }

    .btn-instagram:active,.btn-instagram.active,.open>.dropdown-toggle.btn-instagram {
        background-image: none
    }
    .btn-success {
        background-color: #38D350;
        border-color: #38D350;
    }
    .btn-success:hover {
        background-color: #2ec645;
        border-color: #2ec645;
    }
    .active_nav_item {
        border-bottom: 2px solid #0549f5;
    }
    #menuThree a:hover {color: #0549f5;}
    .pagination>.active>a, .pagination>.active>a:focus, .pagination>.active>a:hover, .pagination>.active>span, .pagination>.active>span:focus, .pagination>.active>span:hover {
        background-color: #0549f5;
        border-color: #0549f5;
    }
    a{
        color: #0549f5;
    }
    a:hover{
        color: #3962ee;
    }
    .grid-selector .select-options a.active{
        color: #0549f5 !important;
    }
    .chart_tab span.cur{
        font-weight: 600;
        color: #0549f5 !important;
        border-color: #0549f5 !important;
    }
    .chart_tab span:hover{
        color: #0549f5 !important;
    }
    .chart_tab span{
        margin-right: 20px;
        display: inline-block;
    }
    #app{
       background-color: rgba(239,244,259,.2);
    }
    .btn-primary {
        background-color: #0549f5;
        border-color: #0549f5;
    }
    .btn-primary:hover,.btn-primary:active,.btn-primary.hover {
        background-color: #0549f5
    }
    .btn-info {
        background-color: #0549f5;
        border-color: #0549f5;
    }
    .btn-info:hover, .btn-info:active, .btn-info.hover {
        background-color: #0549f5;
        border-color: #0549f5;
    }
    .bootstrap-switch .bootstrap-switch-handle-off.bootstrap-switch-danger, .bootstrap-switch .bootstrap-switch-handle-on.bootstrap-switch-danger {
        background: #EC6352;
    }
    .btn-warning {
        background-color: #FAC05E;
        border-color: #FAC05E;
    }

    .btn-warning:hover, .btn-warning:active, .btn-warning.hover {
        background-color: #f39c12;
        border-color: #e08e0b;
    }
    .btn-dropbox:hover, .btn-dropbox:active, .btn-dropbox.active, .open>.dropdown-toggle.btn-dropbox, .btn-dropbox {
        background-color: #0549f5;
        border-color: #0549f5;
    }
    .type_tab{
        border-top: 2px solid #38D350 !important;
    }
    .type_tab span.cur{
        background: #38D350 !important;
    }
    .bg-light-blue,.label-primary,.modal-primary .modal-body {
        background-color: #0549f5 !important
    }
    .dd-handle:hover{
        color: #0549f5;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected]{
        background-color: #0549f5 !important;
    }
    /* .menu-open .active{
        background-color: #0549f5 !important;
    } */
    .sidebar-menu .treeview-menu>li{
        height: 40px;
        line-height: 35px;
    }
    .sidebar-menu {
        font-size: 15px;
    }
    /* .menu-open  */
    .menu-open .active a{
        color: #0549f5 !important;
    }
    /* .skin-blue-light .treeview-menu>li.active>a, .skin-blue-light .treeview-menu>li>a:hover{
        color: #fff;
    } */
    
    .empty-grid {
        background-color: #fff !important
    }
    .sidebar-menu li a span {
        margin-left: 10px;
        vertical-align: middle;
        display: inline-block;
        line-height: 20px !important;
    }
    .input-group .input-group-addon{
        border-top-left-radius: 6px;
        border-bottom-left-radius: 6px;
    }
    .input-group .form-control:last-child, .input-group-addon:last-child, .input-group-btn:first-child>.btn-group:not(:first-child)>.btn, .input-group-btn:first-child>.btn:not(:first-child), .input-group-btn:last-child>.btn, .input-group-btn:last-child>.btn-group>.btn, .input-group-btn:last-child>.dropdown-toggle{
        border-top-right-radius: 6px;
        border-bottom-right-radius: 6px;
    }
    .select2-container--default .select2-selection--multiple,
    .select2-container--default .select2-selection--single, .select2-selection .select2-selection--single,
    textarea.form-control{
        border-radius: 6px;
    }
    .m-l-20{
        margin-left: 20px;
    }
</style>

    @if ($menu_three)
        <div class="btn-group" id="menuThree">
        @foreach ($menu_three as $menuItem)
            @if ($menuItem['status'])
                <a href="{{$menuItem['url']}}" class="active_nav_item">{{$menuItem['title']}}</a>
            @else
                <a href="{{$menuItem['url']}}">{{$menuItem['title']}}</a>
            @endif
        @endforeach
        </div>
    @endif
    <section class="content-header">
        <h1>
            {!! $header ?: trans('admin.title') !!}
            <!-- <small>{!! $description ?: trans('admin.description') !!}</small> -->
        </h1>

        <!-- breadcrumb start -->
        <!-- @if ($breadcrumb)
        <ol class="breadcrumb" style="margin-right: 30px;">
            <li><a href="{{ admin_url('/') }}"><i class="fa fa-dashboard"></i> {{__('Homea')}}</a></li>
            @foreach($breadcrumb as $item)
                @if($loop->last)
                    <li class="active">
                        @if (\Illuminate\Support\Arr::has($item, 'icon'))
                            <i class="fa fa-{{ $item['icon'] }}"></i>
                        @endif
                        {{ $item['text'] }}
                    </li>
                @else
                <li>
                    @if (\Illuminate\Support\Arr::has($item, 'url'))
                        <a href="{{ admin_url(\Illuminate\Support\Arr::get($item, 'url')) }}">
                            @if (\Illuminate\Support\Arr::has($item, 'icon'))
                                <i class="fa fa-{{ $item['icon'] }}"></i>
                            @endif
                            {{ $item['text'] }}
                        </a>
                    @else
                        @if (\Illuminate\Support\Arr::has($item, 'icon'))
                            <i class="fa fa-{{ $item['icon'] }}"></i>
                        @endif
                        {{ $item['text'] }}
                    @endif
                </li>
                @endif
            @endforeach
        </ol>
        @elseif(config('admin.enable_default_breadcrumb'))
        <ol class="breadcrumb" style="margin-right: 30px;">
            <li><a href="{{ admin_url('/') }}"><i class="fa fa-dashboard"></i> {{__('Homeb')}}</a></li>
            @for($i = 2; $i <= count(Request::segments()); $i++)
                <li>
                {{ucfirst(Request::segment($i))}}
                </li>
            @endfor
        </ol>
        @endif -->

        <!-- breadcrumb end -->

    </section>

    <section class="content">

        @include('admin::partials.alerts')
        @include('admin::partials.exception')
        @include('admin::partials.toastr')

        @if($_view_)
            @include($_view_['view'], $_view_['data'])
        @else
            {!! $_content_ !!}
        @endif

    </section>
@endsection
