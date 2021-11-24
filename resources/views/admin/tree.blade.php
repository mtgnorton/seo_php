<div class="box content_box">
    <div class="box-header">
        <div class="btn-group" id="content_types">
            <a class="content-a" href="/admin/content-categories?type=title&group_id=">标题库</a>
            <a class="content-a" href="/admin/content-categories?type=article&group_id=">文章库</a>
            <a class="content-a" href="/admin/content-categories?type=website_name&group_id=">网站名称库</a>
            <a class="content-a" href="/admin/content-categories?type=column&group_id=">栏目库</a>
            <a class="content-a" href="/admin/content-categories?type=sentence&group_id=">句子库</a>
            <a class="content-a" href="/admin/content-categories?type=image&group_id=">图片库</a>
            <!-- <a class="content-a" href="/admin/content-categories?type=video&group_id=">视频库</a> -->
            <a class="content-a" href="/admin/content-categories?type=keyword&group_id=">关键词库</a>
            <a class="content-a" href="/admin/content-categories?type=diy&group_id=">自定义库</a>
        </div>
        <br />
        <div class="btn-group" style="margin-left: 30px">
            <a class="btn btn-primary {{ $id }}-tree-tools" data-action="expand" title="{{ trans('admin.expand') }}">
                <i class="fa fa-plus-square-o" style="font-size: 20px; vertical-align:text-top"></i>
                <span>&nbsp;{{ trans('admin.expand') }}</span>
            </a>
        </div>
        
        <div class="btn-group" style="margin-left: 10px">
            <a class="btn btn-primary {{ $id }}-tree-tools" data-action="collapse" title="{{ trans('admin.collapse') }}">
                <i class="fa fa-minus-square-o" style="font-size: 20px; vertical-align:text-top"></i>
                <span>&nbsp;{{ trans('admin.collapse') }}</span>
            </a>
        </div>
        

        @if($useSave)
        <div class="btn-group" style="margin-left: 10px">
            <a class="btn btn-warning {{ $id }}-save" title="{{ trans('admin.save') }}">
                <!-- <i class="fa fa-save"></i> -->
                <span class="hidden-xs">&nbsp;{{ trans('admin.save') }}</span>
            </a>
        </div>
        @endif

        @if($useRefresh)
        <div class="btn-group" style="margin-left: 10px">
            <a class="btn btn-success {{ $id }}-refresh" title="{{ trans('admin.refresh') }}">
                <!-- <i class="fa fa-refresh"></i> -->
                <span class="hidden-xs">&nbsp;{{ trans('admin.refresh') }}</span>
            </a>
        </div>
        @endif

        <div class="btn-group">
            {!! $tools !!}
        </div>

        @if($useCreate)
        <div class="btn-group pull-right">
            <a class="btn btn-success" href="{{ url($path) }}/create"><i class="fa fa-save"></i><span class="hidden-xs">&nbsp;{{ trans('admin.new') }}</span></a>
        </div>
        @endif

    </div>
    <!-- /.box-header -->
    <div class="box-body table-responsive no-padding">
        <div class="dd" id="{{ $id }}">
            <ol class="dd-list">
                @each($branchView, $items, 'branch')
            </ol>
        </div>
    </div>
    <!-- /.box-body -->
</div>

<script>
$(function () {
    if (window.location.pathname == '/admin/content-categories') {
        var groupId = getQueryString('group_id');
        $('#content_types').css('display', 'block');

        var myCollection = document.getElementsByClassName("content-a");
        var i;
        for (i=0; i< myCollection.length; i++) {
            myCollection[i].href = myCollection[i].href+groupId;
            if (myCollection[i].href == window.location.href) {
                myCollection[i].style.color = '#3E4AF5';
                myCollection[i].style.borderBottom = '2px solid #3E4AF5';
            }
        }
    }
});

function getQueryString(name) {
    var reg = new RegExp('(^|&)' + name + '=([^&]*)(&|$)', 'i');
    var r = window.location.search.substr(1).match(reg);
    if (r != null) {
        return unescape(r[2]);
    }
    return null;
}
</script>
