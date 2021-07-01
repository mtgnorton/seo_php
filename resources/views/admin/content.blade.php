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
