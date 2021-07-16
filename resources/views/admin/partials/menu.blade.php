@if(Admin::user()->visible(\Illuminate\Support\Arr::get($item, 'roles', [])) && Admin::user()->can(\Illuminate\Support\Arr::get($item, 'permission')))
    @if(!isset($item['children']) || $item['parent_id'] != 0)
        <li>
            @if(url()->isValidUrl($item['uri']))
                <a href="{{ $item['uri'] }}" target="_blank">
            @else
                 <a href="{{ admin_url($item['uri']) }}">
            @endif
                <!-- <i class="fa {{$item['icon']}}"></i> -->
                @if ($item['parent_id'] == 0)
                    <img src="{{asset($item['icon'])}}" data-dafault="{{asset($item['icon'])}}" data-src="{{asset($item['icon_selected'])}}"  alt="">
                @else
                <img src="{{asset($item['icon'])}}" alt="" style="width: 14px; height: 14px;">
                @endif
                @if (Lang::has($titleTranslation = 'admin.menu_titles.' . trim(str_replace(' ', '_', strtolower($item['title'])))))
                    <span>{{ __($titleTranslation) }}</span>
                @else
                    <span>{{ admin_trans($item['title']) }}</span>
                @endif
            </a>
        </li>
    @else
        <li class="treeview">
            <a href="#">
                <!-- <i class="fa {{ $item['icon'] }}"></i> -->
                <img src="{{asset($item['icon'])}}" data-dafault="{{asset($item['icon'])}}" data-src="{{asset($item['icon_selected'])}}" alt="">
                @if (Lang::has($titleTranslation = 'admin.menu_titles.' . trim(str_replace(' ', '_', strtolower($item['title'])))))
                    <span>{{ __($titleTranslation) }}</span>
                @else
                    <span>{{ admin_trans($item['title']) }}</span>
                @endif
                <i class="fa fa-angle-left pull-right"></i>
            </a>
            <ul class="treeview-menu">
                @foreach($item['children'] as $item)
                    @include('admin::partials.menu', $item)
                @endforeach
            </ul>
        </li>
    @endif
@endif
