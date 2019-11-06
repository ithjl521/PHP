@extends('master')

@section('title',$product->name)



@section('content')
<div class="page bk_content">
    <div class="weui_cells_title">
        <span class="bk_title">{{$product->name}}</span>
        <span class="bk_price" style="float:right">￥{{$product->price}}</span>
    </div>
    <div class="weui_cells">
        <div class="weui_cell">
            <p class="bk_summary">{{$product->summary}}</p>
        </div>
    </div>

    <div class="weui_cells_title">详细介绍</div>
    <div class="weui_cells">
        <div class="weui_cell">
            <p>
                {!! $pdt_content->content !!}
            </p>
        </div>
    </div>
</div>
@endsection


@section('my-js')

@endsection


