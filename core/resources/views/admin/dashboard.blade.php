@extends('admin.layouts.app')
@section('panel')
    @if(@json_decode($general->system_info)->version > systemDetails()['version'])
    <div class="row">
        <div class="col-md-12">
            <div class="card text-white bg-warning mb-3">
                <div class="card-header">
                    <h3 class="card-title"> @lang('New Version Available') <button class="btn btn--dark float-end">@lang('Version') {{json_decode($general->system_info)->version}}</button> </h3>
                </div>
                <div class="card-body">
                    <h5 class="card-title text-dark">@lang('What is the Update ?')</h5>
                    <p><pre  class="f-size--24">{{json_decode($general->system_info)->details}}</pre></p>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if(@json_decode($general->system_info)->message)
    <div class="row">
        @foreach(json_decode($general->system_info)->message as $msg)
            <div class="col-md-12">
                <div class="alert border border--primary" role="alert">
                    <div class="alert__icon bg--primary">
                        <i class="far fa-bell"></i></div>
                        <p class="alert__message">@php echo $msg; @endphp</p>
                        <button type="button" class="close" data-bs-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">×</span></button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @endif

    <div class="row mb-none-30 mb-3 align-items-center gy-4">
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                style="2"
                link="{{route('admin.users.all')}}"
                icon="las la-users f-size--56"
                icon_style="false"
                title="Total Users"
                value="{{$widget['total_users']}}"
                color="primary"
            />
        </div><!-- dashboard-w1 end -->
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                style="2"
                link="{{route('admin.users.active')}}"
                icon="las la-user-check f-size--56"
                title="Active Users"
                icon_style="false"
                value="{{$widget['verified_users']}}"
                color="success"
            />
        </div>
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                style="2"
                link="{{route('admin.users.email.unverified')}}"
                icon="lar la-envelope f-size--56"
                icon_style="false"
                title="Email Unverified Users"
                value="{{$widget['email_unverified_users']}}"
                color="danger"/>
        </div>
        <div class="col-xxl-3 col-sm-6">
            <x-widget style="2"
                icon_style="false"
                link="{{route('admin.users.mobile.unverified')}}"
                icon="las la-comment-slash f-size--56"
                title="Mobile Unverified Users"
                value="{{$widget['mobile_unverified_users']}}"
                color="red"/>
        </div>
    </div>

    <div class="row mb-none-30 mb-3 align-items-center gy-4">
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                style="2"
                link="{{route('admin.trade.history')}}"
                icon="las la-sync"
                icon_style="false"
                title="Total Trade"
                value="{{$widget['total_trade']}}"
                color="primary"
            />
        </div><!-- dashboard-w1 end -->
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                style="2"
                link="#"
                icon="las la-coins"
                title="Total Currencies"
                icon_style="false"
                value="{{$widget['total_currency']}}"
                color="primary"
            />
        </div><!-- dashboard-w1 end -->
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                style="2"
                link="{{route('admin.currency.crypto')}}"
                icon="lab la-bitcoin"
                icon_style="false"
                title="Total Crypto Currencies"
                value="{{$widget['total_crypto_currency']}}"
                color="primary"
            />
        </div><!-- dashboard-w1 end -->
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                style="2"
                icon_style="false"
                link="{{route('admin.currency.fiat')}}"
                icon="las la-dollar-sign"
                title="Total Fiat Currencies"
                value="{{$widget['total_fiat_currency']}}"
                color="primary"
            />
        </div><!-- dashboard-w1 end -->
    </div>
    
    <div class="row mb-none-30 mb-3 align-items-center gy-4">
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                style="2"
                link="{{route('admin.p2p.trade.index','running')}}"
                icon="fas fa-spinner"
                icon_style="false"
                title="P2P Running Trade"
                value="{{$widget['p2p']['running_trade']}}"
                color="warning"
            />
        </div>
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                style="2"
                link="{{route('admin.p2p.trade.index','completed')}}"
                icon="las la-check-circle"
                title="P2P Completed Trade"
                icon_style="false"
                value="{{$widget['p2p']['completed_trade']}}"
                color="success"
            />
        </div>
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                style="2"
                link="{{route('admin.p2p.trade.index','completed')}}"
                icon="la la-list"
                icon_style="false"
                title="P2P completed Trade"
                value="{{$widget['p2p']['total_trade']}}"
                color="primary"
            />
        </div>
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                style="2"
                icon_style="false"
                link="{{route('admin.p2p.ad.index')}}"
                icon="las la-ad"
                title="P2P Total Ad"
                value="{{$widget['p2p']['total_ad']}}"
                color="primary"
            />
        </div>
    </div>

    <div class="row mb-none-30 mb-3 gy-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <span>@lang('Order Summary')</span>
                    <br>
                    <small class="text-muted text--small">
                        @lang("order summary presents visual & listing data of order, categories by pair, excluding canceled orders & scroll below to show all pair.")
                    </small>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-7">
                            <div class="order-list">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex flex-wrap gap-2 align-items-center justify-content-between pt-0">
                                        <span>@lang('Pair')</span>
                                         <span class="text-start">@lang('Amount')</span>
                                    </li>
                                    @forelse ($widget['order']['list'] as $order)
                                    <li class="list-group-item d-flex flex-wrap gap-2 align-items-center mb-2 justify-content-between">
                                        <span>{{ @$order->pair->symbol }}</span>
                                         <span class="text-start">
                                            {{ showAmount($order->total_amount)}}  {{ @$order->pair->coin->symbol }}
                                        </span>
                                    </li>
                                    @empty
                                    <li class="list-group-item text-center text-muted">
                                        {{ __($emptyMessage) }}
                                    </li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                        <div class="col-lg-5 d-flex align-items-center">
                            <canvas id="pair-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="row gy-4">
                <div class="col-12 ">
                    <x-widget
                        style="2"
                        link="{{route('admin.order.history')}}"
                        icon="las la-list-alt"
                        icon_style="false"
                        title="Total Orders"
                        value="{{$widget['order_count']['total']}}"
                        color="primary"
                    />
                </div><!-- dashboard-w1 end -->
                <div class="col-12 ">
                    <x-widget
                        style="2"
                        link="{{route('admin.order.open')}}"
                        icon="fa  fa-spinner"
                        icon_style="false"
                        title="Open Orders"
                        value="{{$widget['order_count']['open']}}"
                        color="info"
                    />
                </div>
                <div class="col-12 ">
                    <x-widget
                        style="2"
                        link="{{route('admin.order.history')}}?status={{Status::ORDER_COMPLETED}}"
                        icon="las la-check-circle"
                        icon_style="false"
                        title="Completed Orders"
                        value="{{$widget['order_count']['completed']}}"
                        color="success"
                    />
                </div>

                <div class="col-12">
                    <x-widget
                        style="2"
                        link="{{route('admin.order.history')}}?status={{Status::ORDER_CANCELED}}"
                        icon="las la-times-circle"
                        icon_style="false"
                        title="Canceled Orders"
                        value="{{$widget['order_count']['canceled']}}"
                        color="danger"
                    />
                </div><!-- dashboard-w1 end -->
            </div>
        </div>
    </div>

    <div class="row mb-none-30 mb-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <span>@lang('Deposit Summary')</span>
                    <br>
                    <small class="text-muted text--small">
                        @lang("Deposit summary presents visual & listing data of deposit, categories by currency, excluding rejected deposit & scroll below to show all deposited amount for each currency.")
                    </small>
                </div>
                <div class="cadr-body">
                    <div class="row">
                        <div class="col-lg-7">
                            <div class="deposit-wrapper">
                                <ul class="list-group list-group-flush  p-3 deposit-list">
                                    <li class="list-group-item d-flex flex-wrap gap-2 align-items-center justify-content-between pt-0">
                                        <span class="flex-fill text-start">@lang('Currency')</span>
                                        <span class="flex-fill text-center">@lang('Amount')</span>
                                        <span class="flex-fill text-center">@lang('Amount')({{ __($general->cur_text) }})</span>
                                    </li>
                                    @forelse ($widget['deposit']['list'] as $deposit)
                                    <li class="list-group-item d-flex flex-wrap gap-2 align-items-center mb-2 justify-content-between">
                                        <div class="flex-fill text-start">
                                            <div class="user">
                                                <div class="thumb">
                                                    <img src="{{@$deposit->currency->image_url }}">
                                                </div>
                                                <div class="text-start ms-1">
                                                    <small>{{@$deposit->currency->symbol}}</small> <br>
                                                    <small>{{__(strLimit(@$deposit->currency->name,9))}}</small>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="flex-fill text-center">{{ showAmount($deposit->total_amount)}}</span>
                                        <span class="flex-fill text-center">{{ showAmount($deposit->total_amount * $deposit->currency->rate)}}</span>
                                    </li>
                                    @empty
                                    <li class="list-group-item text-center text-muted">
                                        {{ __($emptyMessage) }}
                                    </li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                        <div class="col-lg-5 d-flex align-items-center">
                            <canvas id="deposit-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <span>@lang('Withdraw Summary')</span>
                    <br>
                    <small class="text-muted text--small">
                        @lang("Withdraw summary presents visual & listing data of withdraw, categories by currency, excluding rejected withdraw & scroll below to show all Withdraw amount for each currency.")
                    </small>
                </div>
                <div class="cadr-body">
                    <div class="row">
                        <div class="col-lg-5 d-flex align-items-center">
                            <canvas id="withdraw" class="mt-3"></canvas>
                        </div>
                        <div class="col-lg-7">
                            <div class="withdraw-wrapper">
                                <ul class="list-group list-group-flush  p-3 withdraw-list">
                                    <li class="list-group-item d-flex flex-wrap gap-2 align-items-center justify-content-between pt-0">
                                        <span class="flex-fill text-start">@lang('Currency')</span>
                                        <span class="flex-fill text-center">@lang('Amount')</span>
                                        <span class="flex-fill text-center">@lang('Amount')({{ __($general->cur_text) }})</span>
                                    </li>
                                    @forelse ($widget['withdraw']['list'] as $withdraw)
                                    <li class="list-group-item d-flex flex-wrap gap-2 align-items-center mb-2 justify-content-between">
                                        <div class="flex-fill text-start">
                                            <div class="user">
                                                <div class="thumb">
                                                    <img src="{{@$withdraw->withdrawCurrency->image_url }}">
                                                </div>
                                                <div class="text-start ms-1">
                                                    <small>{{@$withdraw->withdrawCurrency->symbol}}</small> <br>
                                                    <small>{{__(strLimit(@$withdraw->withdrawCurrency->name,9))}}</small>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="flex-fill text-center">{{ showAmount($withdraw->total_amount)}}</span>
                                        <span class="flex-fill text-center">{{ showAmount($withdraw->total_amount * $withdraw->withdrawCurrency->rate)}}</span>
                                    </li>
                                    @empty
                                    <li class="list-group-item text-center text-muted">
                                        {{ __($emptyMessage) }}
                                    </li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-none-30 mt-5">
        <div class="col-xl-4 col-lg-6 mb-30">
            <div class="card overflow-hidden">
                <div class="card-body">
                    <h5 class="card-title">@lang('Login By Browser') (@lang('Last 30 days'))</h5>
                    <canvas id="userBrowserChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-6 mb-30">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">@lang('Login By OS') (@lang('Last 30 days'))</h5>
                    <canvas id="userOsChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-6 mb-30">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">@lang('Login By Country') (@lang('Last 30 days'))</h5>
                    <canvas id="userCountryChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    @php
        $lastCron = Carbon\Carbon::parse($general->last_cron)->diffInSeconds();
    @endphp

    @if ($lastCron >= 900)
        @include('admin.partials.cron_instruction')
    @endif

@endsection

@push('script')
<script src="{{ asset('assets/admin/js/vendor/apexcharts.min.js') }}"></script>
<script src="{{ asset('assets/admin/js/vendor/chart.js.2.8.0.js') }}"></script>

<script>
    "use strict";

    $(".order-list").scroll(function() {
        if ((parseInt($(this)[0].scrollHeight) - parseInt(this.clientHeight)) == parseInt($(this).scrollTop())) {
            loadOrderList();
        }
    });

    let orderSkip = 6;
    let take      = 20;
    function loadOrderList(){
        $.ajax({
            url: "{{ route('admin.load.data') }}",
            type: "GET",
            dataType: 'json',
            cache: false,
            data:{
                model_name:"Order",
                skip:orderSkip,
                take:take
            },
            success: function (resp) {
                if(!resp.success){
                    return false;
                }
                orderSkip +=parseInt(take);
                let html="";
                $.each(resp.data, function (i, order) {
                    html +=`
                    <li class="list-group-item d-flex flex-wrap gap-2 align-items-center mb-2 justify-content-between">
                        <span>${order.pair.symbol}</span>
                            <span class="text-start">
                            ${getAmount(order.total_amount)} ${order.pair.coin.symbol}
                        </span>
                    </li>
                    `
                });
                $('.order-list ul').append(html);
            }
        });
    };

    $(".deposit-list").scroll(function() {
        if ((parseInt($(this)[0].scrollHeight) - parseInt(this.clientHeight)) == parseInt($(this).scrollTop())) {
            loadDepositList();
        }
    });

    let depositSkip = 6;
    function loadDepositList(){
        $.ajax({
            url: "{{ route('admin.load.data') }}",
            type: "GET",
            dataType: 'json',
            cache: false,
            data:{
                model_name:"Deposit",
                skip:depositSkip,
                take:take
            },
            success: function (resp) {
                if(!resp.success){
                    return false;
                }
                depositSkip +=parseInt(take);
                let html="";
                $.each(resp.data, function (i, deposit) {
                    html +=`
                    <li class="list-group-item d-flex flex-wrap gap-2 align-items-center mb-2 justify-content-between">
                        <div class="flex-fill text-start">
                            <div class="user">
                                <div class="thumb">
                                    <img src="${deposit.currency.image_url }">
                                </div>
                                <div class="text-start ms-1">
                                    <small>${deposit.currency.symbol}</small> <br>
                                    <small>${deposit.currency.name}</small>
                                </div>
                            </div>
                        </div>
                        <span class="flex-fill text-center">${getAmount(deposit.total_amount)}</span>
                        <span class="flex-fill text-center">${getAmount(parseFloat(deposit.total_amount) * parseFloat(deposit.currency.rate))}</span>
                    </li>
                    `
                });
                $('.deposit-list').append(html);
            }
        });
    };

    $(".withdraw-list").scroll(function() {
        if ((parseInt($(this)[0].scrollHeight) - parseInt(this.clientHeight)) == parseInt($(this).scrollTop())) {
            loadWithdrawList();
        }
    });

    let withdrawSkip = 6;
    function loadWithdrawList(){
        $.ajax({
            url: "{{ route('admin.load.data') }}",
            type: "GET",
            dataType: 'json',
            cache: false,
            data:{
                model_name:"Withdrawal",
                skip:withdrawSkip,
                take:take
            },
            success: function (resp) {
                if(!resp.success){
                    return false;
                }
                withdrawSkip +=parseInt(take);
                let html="";
                $.each(resp.data, function (i, withdraw) {
                    html +=`
                    <li class="list-group-item d-flex flex-wrap gap-2 align-items-center mb-2 justify-content-between">
                        <div class="flex-fill text-start">
                            <div class="user">
                                <div class="thumb">
                                    <img src="${withdraw.withdraw_currency.image_url }">
                                </div>
                                <div class="text-start ms-1">
                                    <small>${withdraw.withdraw_currency.symbol}</small> <br>
                                    <small>${withdraw.withdraw_currency.name}</small>
                                </div>
                            </div>
                        </div>
                        <span class="flex-fill text-center">${getAmount(withdraw.total_amount)}</span>
                        <span class="flex-fill text-center">${getAmount(parseFloat(withdraw.total_amount) * parseFloat(withdraw.withdraw_currency.rate))}</span>
                    </li>
                    `
                });
                $('.withdraw-list').append(html);
            }
        });
    };

    var ctx = document.getElementById('deposit-chart');
    var myChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: @json($widget['deposit']['currency_symbol']),
            datasets: [{
                data: @json($widget['deposit']['currency_count']),
                backgroundColor: [
                    '#ff7675',
                    '#6c5ce7',
                    '#ffa62b',
                    '#ffeaa7',
                    '#D980FA',
                    '#fccbcb',
                    '#45aaf2',
                    '#05dfd7',
                    '#FF00F6',
                    '#1e90ff',
                    '#2ed573',
                    '#eccc68',
                    '#ff5200',
                    '#cd84f1',
                    '#7efff5',
                    '#7158e2',
                    '#fff200',
                    '#ff9ff3',
                    '#08ffc8',
                    '#3742fa',
                    '#1089ff',
                    '#70FF61',
                    '#bf9fee',
                    '#574b90'
                ],
                borderColor: [
                    'rgba(231, 80, 90, 0.75)'
                ],
                borderWidth: 0,
            }]
        },
        options: {
            aspectRatio: 1.3,
            responsive: true,
            elements: {
                line: {
                    tension: 0 // disables bezier curves
                }
            },
            scales: {
                xAxes: [{
                    display: false
                }],
                yAxes: [{
                    display: false
                }]
            },
            legend: {
                display: false,
            }
        }
    });

    var ctx = document.getElementById('withdraw');
    var myChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: @json(@$widget['withdraw']['currency_symbol'] ),
            datasets: [{
                data: @json(@$widget['withdraw']['currency_count'] ),
                backgroundColor: [
                    '#ff7675',
                    '#6c5ce7',
                    '#ffa62b',
                    '#ffeaa7',
                    '#D980FA',
                    '#fccbcb',
                    '#45aaf2',
                    '#05dfd7',
                    '#FF00F6',
                    '#1e90ff',
                    '#2ed573',
                    '#eccc68',
                    '#ff5200',
                    '#cd84f1',
                    '#7efff5',
                    '#7158e2',
                    '#fff200',
                    '#ff9ff3',
                    '#08ffc8',
                    '#3742fa',
                    '#1089ff',
                    '#70FF61',
                    '#bf9fee',
                    '#574b90'
                ],
                borderColor: [
                    'rgba(231, 80, 90, 0.75)'
                ],
                borderWidth: 0,
            }]
        },
        options: {
            aspectRatio: 1.3,
            responsive: true,
            elements: {
                line: {
                    tension: 0 // disables bezier curves
                }
            },
            scales: {
                xAxes: [{
                    display: false
                }],
                yAxes: [{
                    display: false
                }]
            },
            legend: {
                display: false,
            }
        }
    });

    var ctx = document.getElementById('pair-chart');
    var myChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: @json($widget['order']['symbol']),
            datasets: [{
                data: @json($widget['order']['count']),
                backgroundColor: [
                    '#ff7675',
                    '#6c5ce7',
                    '#ffa62b',
                    '#ffeaa7',
                    '#D980FA',
                    '#fccbcb',
                    '#45aaf2',
                    '#05dfd7',
                    '#FF00F6',
                    '#1e90ff',
                    '#2ed573',
                    '#eccc68',
                    '#ff5200',
                    '#cd84f1',
                    '#7efff5',
                    '#7158e2',
                    '#fff200',
                    '#ff9ff3',
                    '#08ffc8',
                    '#3742fa',
                    '#1089ff',
                    '#70FF61',
                    '#bf9fee',
                    '#574b90'
                ],
                borderColor: [
                    'rgba(231, 80, 90, 0.75)'
                ],
                borderWidth: 0,
            }]
        },
        options: {
            aspectRatio: 1.2,
            responsive: true,
            elements: {
                line: {
                    tension: 0 // disables bezier curves
                }
            },
            scales: {
                xAxes: [{
                    display: false
                }],
                yAxes: [{
                    display: false
                }]
            },
            legend: {
                display: false,
            }
        }
    });


    var ctx = document.getElementById('userBrowserChart');
    var myChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: @json($chart['user_browser_counter']->keys()),
            datasets: [{
                data: {{ $chart['user_browser_counter']->flatten() }},
                backgroundColor: [
                    '#ff7675',
                    '#6c5ce7',
                    '#ffa62b',
                    '#ffeaa7',
                    '#D980FA',
                    '#fccbcb',
                    '#45aaf2',
                    '#05dfd7',
                    '#FF00F6',
                    '#1e90ff',
                    '#2ed573',
                    '#eccc68',
                    '#ff5200',
                    '#cd84f1',
                    '#7efff5',
                    '#7158e2',
                    '#fff200',
                    '#ff9ff3',
                    '#08ffc8',
                    '#3742fa',
                    '#1089ff',
                    '#70FF61',
                    '#bf9fee',
                    '#574b90'
                ],
                borderColor: [
                    'rgba(231, 80, 90, 0.75)'
                ],
                borderWidth: 0,

            }]
        },
        options: {
            aspectRatio: 1,
            responsive: true,
            maintainAspectRatio: true,
            elements: {
                line: {
                    tension: 0 // disables bezier curves
                }
            },
            scales: {
                xAxes: [{
                    display: false
                }],
                yAxes: [{
                    display: false
                }]
            },
            legend: {
                display: false,
            }
        }
    });

    var ctx = document.getElementById('userOsChart');
    var myChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: @json($chart['user_os_counter']->keys()),
            datasets: [{
                data: {{ $chart['user_os_counter']->flatten() }},
                backgroundColor: [
                    '#ff7675',
                    '#6c5ce7',
                    '#ffa62b',
                    '#ffeaa7',
                    '#D980FA',
                    '#fccbcb',
                    '#45aaf2',
                    '#05dfd7',
                    '#FF00F6',
                    '#1e90ff',
                    '#2ed573',
                    '#eccc68',
                    '#ff5200',
                    '#cd84f1',
                    '#7efff5',
                    '#7158e2',
                    '#fff200',
                    '#ff9ff3',
                    '#08ffc8',
                    '#3742fa',
                    '#1089ff',
                    '#70FF61',
                    '#bf9fee',
                    '#574b90'
                ],
                borderColor: [
                    'rgba(0, 0, 0, 0.05)'
                ],
                borderWidth: 0,

            }]
        },
        options: {
            aspectRatio: 1,
            responsive: true,
            elements: {
                line: {
                    tension: 0 // disables bezier curves
                }
            },
            scales: {
                xAxes: [{
                    display: false
                }],
                yAxes: [{
                    display: false
                }]
            },
            legend: {
                display: false,
            }
        },
    });

    // Donut chart
    var ctx = document.getElementById('userCountryChart');
    var myChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: @json($chart['user_country_counter']->keys()),
            datasets: [{
                data: {{ $chart['user_country_counter']->flatten() }},
                backgroundColor: [
                    '#ff7675',
                    '#6c5ce7',
                    '#ffa62b',
                    '#ffeaa7',
                    '#D980FA',
                    '#fccbcb',
                    '#45aaf2',
                    '#05dfd7',
                    '#FF00F6',
                    '#1e90ff',
                    '#2ed573',
                    '#eccc68',
                    '#ff5200',
                    '#cd84f1',
                    '#7efff5',
                    '#7158e2',
                    '#fff200',
                    '#ff9ff3',
                    '#08ffc8',
                    '#3742fa',
                    '#1089ff',
                    '#70FF61',
                    '#bf9fee',
                    '#574b90'
                ],
                borderColor: [
                    'rgba(231, 80, 90, 0.75)'
                ],
                borderWidth: 0,

            }]
        },
        options: {
            aspectRatio: 1,
            responsive: true,
            elements: {
                line: {
                    tension: 0 // disables bezier curves
                }
            },
            scales: {
                xAxes: [{
                    display: false
                }],
                yAxes: [{
                    display: false
                }]
            },
            legend: {
                display: false,
            }
        }
    });

</script>
@endpush


@push('style')
    <style>
        .user .thumb {
            width: 35px;
            height: 35px;
        }
        .list-group-item {

            border: 1px solid rgba(0,0,0,.045);
        }
        #deposit-chart{
            margin-left: -20px;
        }
        .order-list, .deposit-list, .withdraw-list {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 1.5rem;
        }
    </style>
@endpush
