<style>
    .page-link-box img{
        width: 30px;
        height: 30px;
    }
    .page-ml{
        /* margin-left: 9px */
    }
    .page-mr{
        /* margin-right: 10px; */
    }
    .page-item a, .page-item span{
        border-radius: 5px !important;
        margin-right: 5px;
        font-size: 14px !important;
    }
    .page-item span{
        background: #DBE3E9!important;
        color: #21354D !important;
        border: 1px solid #DBE3E9 !important;
    }
    .page-link-box{
        padding: 0 !important;
        border: none !important;
    }
</style>
<ul class="pagination pagination-sm no-margin pull-right">
    <!-- Previous Page Link -->
    @if ($paginator->onFirstPage())
        <li class="page-item disabled">
            <span class="page-link page-link-box">
                <img class="page-mr" src="/asset/imgs/default_icon/7.png" alt="">
            </span>
        </li>
    @else
        <li class="page-item">
            <a class="page-link page-link-box" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                <img class="page-mr" src="/asset/imgs/default_icon/7.png" alt="">
            </a>
        </li>
    @endif

    <!-- Pagination Elements -->
    @foreach ($elements as $element)
        <!-- "Three Dots" Separator -->
        @if (is_string($element))
            <li class="page-item disabled"><span class="page-link">{{ $element }}</span></li>
        @endif

        <!-- Array Of Links -->
        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <li class="page-item active"><span class="page-link">{{ $page }}</span></li>
                @else
                    <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                @endif
            @endforeach
        @endif
    @endforeach

    <!-- Next Page Link -->
    @if ($paginator->hasMorePages())
        <li class="page-item">
            <a class="page-link page-link-box" href="{{ $paginator->nextPageUrl() }}" rel="next">
                <img class="page-ml" src="/asset/imgs/default_icon/8.png" alt="">
            </a>
        </li>
    @else
        <li class="page-item disabled">
            <span class="page-link page-link-box">
                <img class="page-ml" src="/asset/imgs/default_icon/8.png" alt="">
            </span>
        </li>
    @endif
</ul>
